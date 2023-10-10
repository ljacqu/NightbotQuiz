<?php

class DatabaseHandler {

  private PDO $conn;

  function __construct() {
    $host = Configuration::DB_HOST;
    $name = Configuration::DB_NAME;
    $this->conn = new PDO(
      "mysql:host={$host};dbname={$name}", Configuration::DB_USER, Configuration::DB_PASS,
      [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);
    $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  }

  function startTransaction(): void {
    $this->conn->beginTransaction();
  }

  function commit(): void {
    $this->conn->commit();
  }

  function rollBackIfNeeded(): void {
    if ($this->conn->inTransaction()) {
      $this->conn->rollBack();
    }
  }

  function getOwnerInfoBySecret(string $secret): ?array {
    $stmt = $this->conn->prepare('
      SELECT id, name
      FROM nq_owner
      WHERE secret = :secret');
    $stmt->bindParam('secret', $secret);

    return self::execAndFetch($stmt);
  }

  function getSettingsForSecret(string $secret): ?array {
    $stmt = $this->conn->prepare('
      SELECT nq_owner.id, name, active_mode,
             timer_unsolved_question_wait, timer_solved_question_wait, timer_last_answer_wait, user_new_wait,
             history_display_entries, history_avoid_last_answers
      FROM nq_settings
      INNER JOIN nq_owner ON nq_owner.settings_id = nq_settings.id
      WHERE secret = :secret;');
    $stmt->bindParam('secret', $secret);

    return self::execAndFetch($stmt);
  }

  function getIndexPageSettingsForOwner(string $ownerName): ?array {
    $stmt = $this->conn->prepare(
      'SELECT nq_owner.id, history_display_entries
       FROM nq_owner
       INNER JOIN nq_settings
               ON nq_settings.id = nq_owner.settings_id
       WHERE name = :name;');
    $stmt->bindParam('name', $ownerName);

    return self::execAndFetch($stmt);
  }

  function hasQuestionCategoriesOrMore(int $ownerId, int $totalNr): bool {
    $st = $this->conn->query(
      "SELECT COUNT(DISTINCT COALESCE(category, id)) >= $totalNr AS has_enough
       FROM nq_question
       WHERE owner_id = $ownerId;");
    return (bool) $st->fetch(PDO::FETCH_ASSOC)['has_enough'];
  }

  function getLastQuestionDraw(int $ownerId): ?array {
    $stmt = $this->conn->prepare('
      SELECT nq_draw.id, UNIX_TIMESTAMP(created) AS created, UNIX_TIMESTAMP(solved) AS solved,
             question, answer, type, UNIX_TIMESTAMP(last_answer) AS last_answer
      FROM nq_draw
      INNER JOIN nq_question
              ON nq_question.id = nq_draw.question_id
      LEFT JOIN (
         SELECT draw_id, MAX(created) AS last_answer
         FROM nq_draw_answer
         GROUP BY draw_id
      ) answers
              ON answers.draw_id = nq_draw.id
      WHERE nq_draw.owner_id = :ownerId
      ORDER BY solved IS NULL DESC, solved DESC
      LIMIT 1;');
    $stmt->bindParam('ownerId', $ownerId);

    return self::execAndFetch($stmt);
  }

  function drawNewQuestion(int $ownerId, int $pastQuestionsToSkip): ?array {
    // Step 1: Set draws as solved that might exist for the owner
    $this->conn->exec(
      "UPDATE nq_draw
       SET solved = NOW()
       WHERE owner_id = $ownerId AND solved IS NULL;");

    // Step 2: Find new question to draw
    $stmt = $this->conn->prepare(
     "WITH last_draws AS (
          SELECT COALESCE(category, question_id)
          FROM nq_draw
          INNER JOIN nq_question
                  ON nq_question.id = nq_draw.question_id
          WHERE nq_draw.owner_id = $ownerId
          ORDER BY solved DESC
          LIMIT $pastQuestionsToSkip
      )
      SELECT id, question, answer, type
      FROM nq_question
      WHERE COALESCE(category, id) NOT IN (SELECT * FROM last_draws)
        AND owner_id = $ownerId
      ORDER BY RAND()
      LIMIT 1;");
    $result = self::execAndFetch($stmt);

    // Step 3: Save as draw (if question is available)
    if ($result) {
      $stmt = $this->conn->prepare('INSERT INTO nq_draw (question_id, owner_id, created)
        VALUES (:questionId, :ownerId, NOW());');
      $stmt->bindParam('questionId', $result['id']);
      $stmt->bindParam('ownerId', $ownerId);
      $stmt->execute();
    }

    // Return question data
    return $result;
  }

  function setCurrentDrawAsSolved(int $drawId) {
    $stmt = $this->conn->prepare('UPDATE nq_draw SET solved = NOW() WHERE id = :id');
    $stmt->bindParam('id', $drawId);
    $stmt->execute();
  }

  function saveDrawAnswer(int $drawId, string $userName, string $answer, ?float $score): void {
    $stmt = $this->conn->prepare('
      INSERT INTO nq_draw_answer (draw_id, created, user, answer, score)
      VALUES (:drawId, NOW(), :user, :answer, :score)
      AS new_data(draw_id, created, user, answer, score)
      ON DUPLICATE KEY UPDATE created = NOW(), answer = new_data.answer, score = new_data.score;');
    $stmt->bindParam('drawId', $drawId);
    $stmt->bindParam('user', $userName);
    $stmt->bindParam('answer', $answer);
    $stmt->bindParam('score', $score);

    $stmt->execute();
  }

  function getLastDraws(int $ownerId, int $maxEntries): array {
    $stmt = $this->conn->prepare(
     "SELECT (solved IS NOT NULL) as is_solved, question, answer, type
      FROM nq_draw
      INNER JOIN nq_question
              ON nq_question.id = nq_draw.question_id
      WHERE nq_draw.owner_id = $ownerId
      ORDER BY solved IS NULL DESC, solved DESC
      LIMIT $maxEntries;");
    $stmt->execute();
    return $stmt->fetchAll();
  }

  function updateSettingsForSecret(string $secret, OwnerSettings $stgs): bool {
    $stmt = $this->conn->prepare('
      UPDATE nq_settings SET
        active_mode = :active_mode,
        timer_unsolved_question_wait = :timer_unsolved_wait,
        timer_solved_question_wait = :timer_solved_wait,
        timer_last_answer_wait = :timer_last_answer_wait,
        user_new_wait = :user_new_wait,
        history_display_entries = :history_display_entries,
        history_avoid_last_answers = :history_avoid_last_answers
      WHERE id IN (
        SELECT settings_id FROM nq_owner WHERE secret = :secret
      );');

    $stmt->bindParam('active_mode', $stgs->activeMode);
    $stmt->bindParam('timer_unsolved_wait', $stgs->timerUnsolvedQuestionWait);
    $stmt->bindParam('timer_solved_wait', $stgs->timerSolvedQuestionWait);
    $stmt->bindParam('timer_last_answer_wait', $stgs->timerLastAnswerWait);
    $stmt->bindParam('user_new_wait', $stgs->userNewWait);
    $stmt->bindParam('history_display_entries', $stgs->historyDisplayEntries);
    $stmt->bindParam('history_avoid_last_answers', $stgs->historyAvoidLastAnswers);
    $stmt->bindParam('secret', $secret);

    $stmt->execute();
    return $stmt->rowCount() > 0;
  }

  /**
   * Updates the database to contain exactly the given questions for the specified owner,
   * i.e. inserts/updates questions (based on the key) and deletes any questions from the DB that were not provided.
   *
   * @param int $ownerId the owner ID
   * @param QuestionValues[] $questions the question definitions the database should have
   * @return array<string, int> keys "updated" and "deleted" with the number of rows per category
   */
  function updateQuestions(int $ownerId, array $questions): array {
    // Step 1: Insert/update all provided questions
    $updateQueryValues = self::repeatCommaSeparated("($ownerId, ?, ?, ?, ?, ?)", count($questions));
    $updateQuery = "INSERT INTO nq_question (owner_id, ukey, question, answer, type, category)
        VALUES $updateQueryValues
        AS new_data(owner_id, ukey, question, answer, type, category)
        ON DUPLICATE KEY UPDATE question = new_data.question, answer = new_data.answer,
          type = new_data.type, category = new_data.category;";
    $updateStmt = $this->conn->prepare($updateQuery);

    $i = 1;
    foreach ($questions as $question) {
      $updateStmt->bindParam($i++, $question->key);
      $updateStmt->bindParam($i++, $question->question);
      $updateStmt->bindParam($i++, $question->answer);
      $updateStmt->bindParam($i++, $question->questionType);
      $updateStmt->bindParam($i++, $question->category);
    }
    $updateStmt->execute();
    $updated = $updateStmt->rowCount();

    // Step 2: Delete all questions that are not present
    $deleteQueryValues = self::repeatCommaSeparated('?', count($questions));
    $deleteQuery = "DELETE FROM nq_question
      WHERE owner_id = ?
        AND ukey NOT IN ($deleteQueryValues)";
    $deleteStmt = $this->conn->prepare($deleteQuery);
    $deleteStmt->bindValue(1, $ownerId);

    $i = 2;
    foreach ($questions as $question) {
      $deleteStmt->bindValue($i++, $question->key);
    }
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();

    // Step 3: Resolve the current question draw if it references an unknown question
    $stmt = $this->conn->prepare(
     "UPDATE nq_draw
      SET solved = NOW()
      WHERE solved IS NULL
        AND owner_id = $ownerId
        AND question_id NOT IN (SELECT id FROM nq_question);");
    $stmt->execute();

    // Return statistics
    return [
      'updated' => $updated,
      'deleted' => $deleted
    ];
  }

  private static function repeatCommaSeparated(string $token, int $iterations): string {
    return implode(',', array_fill(0, $iterations, $token));
  }

  private static function execAndFetch(PDOStatement $stmt): ?array {
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result === false ? null : $result;
  }

  function initTables(): void {
    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_settings (
        id int NOT NULL AUTO_INCREMENT,
        active_mode varchar(25) NOT NULL,
        timer_unsolved_question_wait int NOT NULL,
        timer_solved_question_wait int NOT NULL,
        timer_last_answer_wait int NOT NULL,
        user_new_wait int NOT NULL,
        history_display_entries int NOT NULL,
        history_avoid_last_answers int NOT NULL,
        PRIMARY KEY (id)
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_owner (
        id int NOT NULL AUTO_INCREMENT,
        name varchar(50) NOT NULL,
        secret varchar(50) NOT NULL,
        settings_id int NOT NULL,
        is_admin boolean NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (settings_id) REFERENCES nq_settings(id),
        UNIQUE KEY nq_user_secret_uq (secret) USING BTREE
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_question (
        id int NOT NULL AUTO_INCREMENT,
        owner_id int NOT NULL,
        ukey varchar(32) NOT NULL,
        question varchar(200) NOT NULL,
        answer varchar(200) NOT NULL,
        type varchar(50) NOT NULL,
        category varchar(200),
        PRIMARY KEY (id),
        FOREIGN KEY (owner_id) REFERENCES nq_owner(id),
        UNIQUE KEY nq_question_owner_ukey_uq (owner_id, ukey)
      ) ENGINE = InnoDB;');

    // Note: No foreign key on question_id; the question might be deleted, in which case we'll skip
    // the draw by using an INNER JOIN on the question, so the row won't be included.
    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_draw (
        id int NOT NULL AUTO_INCREMENT,
        question_id int NOT NULL,
        owner_id int NOT NULL,
        created datetime NOT NULL,
        solved datetime,
        PRIMARY KEY (id),
        FOREIGN KEY (owner_id) REFERENCES nq_owner(id)
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_draw_answer (
        id int NOT NULL AUTO_INCREMENT,
        draw_id int NOT NULL,
        created datetime NOT NULL,
        user varchar(100) NOT NULL,
        answer varchar(200) NOT NULL,
        score decimal(5,2),
        PRIMARY KEY (id),
        FOREIGN KEY (draw_id) REFERENCES nq_draw(id),
        UNIQUE KEY nq_draw_user_uq (draw_id, user)
      ) ENGINE = InnoDB;');
  }

  function initOwnerIfEmpty(): bool {
    $query = $this->conn->query('SELECT EXISTS (SELECT 1 FROM nq_owner);');
    $query->execute();
    $hasOwner = $query->fetch()[0];

    if (!$hasOwner) {
      try {
        $this->conn->beginTransaction();

        $this->conn->exec('INSERT INTO nq_settings
               (active_mode, timer_unsolved_question_wait, timer_solved_question_wait, timer_last_answer_wait, user_new_wait, history_display_entries, history_avoid_last_answers)
        VALUES ("ON",                                 180,                        180,                    120,            90,                       0,                          0)');
        $query = $this->conn->query('SELECT LAST_INSERT_ID();');
        $query->execute();
        $settingsId = $query->fetch()[0];

        $secret = substr(md5(microtime()), 0, 17);
        $this->conn->exec('INSERT INTO nq_owner (name, secret, settings_id, is_admin)
        VALUES ("admin", "' . $secret . '", ' . $settingsId . ', true);');

        $this->conn->commit();
        return true;
      } catch (Exception $e) {
        $this->rollBackIfNeeded();
        throw $e;
      }
    }
    return false;
  }
}
