<?php

class DatabaseHandler {

  private $conn;
  private $name;

  function __construct() {
    $host = Configuration::DB_HOST;
    $this->name = Configuration::DB_NAME;
    $this->conn = new PDO(
      "mysql:host={$host};dbname={$this->name}", Configuration::DB_USER, Configuration::DB_PASS,
      [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);
  }

  function getOwnerInfoBySecret(string $secret): array|null {
    $stmt = $this->conn->prepare('
      SELECT id, name
      FROM nq_owner
      WHERE secret = :secret');
    $stmt->bindParam('secret', $secret);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result === false ? null : $result;
  }

  function getSettingsForSecret(string $secret): array|null {
    $stmt = $this->conn->prepare('
      SELECT name, active_mode, timer_unsolved_question_wait, timer_solved_question_wait, timer_last_answer_wait,
             user_new_wait, history_display_entries, history_avoid_last_answers
      FROM nq_settings
      INNER JOIN nq_owner
      WHERE secret = :secret;');
    $stmt->bindParam('secret', $secret);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result === false ? null : $result;
  }

  function updateSettingsForSecret(string $secret, UserSettings $stgs): bool {
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

  // TODO: TRANSACTION
  function updateQuestions(int $ownerId, array $questions): array {

    // Step 1: Insert/update all provided questions
    $updateQueryValues = implode(',',
      array_fill(0, count($questions), "($ownerId, ?, ?, ?, ?)"));
    $updateQuery = "INSERT INTO nq_question (owner_id, ukey, question, answer, type)
        VALUES $updateQueryValues
        AS new_data(owner_id, ukey, question, answer, type)
        ON DUPLICATE KEY UPDATE answer = new_data.answer, type = new_data.type,
          question = new_data.question;";
    $updateStmt = $this->conn->prepare($updateQuery);

    $i = 1;
    foreach ($questions as $key => $question) {
      $updateStmt->bindValue($i++, $key);
      $updateStmt->bindValue($i++, $question->question);
      $updateStmt->bindValue($i++, $question->answer);
      $updateStmt->bindValue($i++, $question->questionTypeId);
    }
    $updateStmt->execute();
    $updated = $updateStmt->rowCount();

    // Step 2: Delete all questions that are not present
    $deleteQueryValues = self::createPlaceholdersList(count($questions));
    $deleteQuery = "DELETE FROM nq_question
      WHERE owner_id = ?
        AND ukey NOT IN ($deleteQueryValues)";
    $deleteStmt = $this->conn->prepare($deleteQuery);
    $deleteStmt->bindValue(1, $ownerId);

    $i = 2;
    foreach ($questions as $key => $question) {
      $deleteStmt->bindValue($i++, $key);
    }
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();

    // Return statistics
    return [
      'updated' => $updated,
      'deleted' => $deleted
    ];
  }

  // Returns, for instance, "?,?,?" for a size of 3
  private static function createPlaceholdersList($size) {
    return implode(',', array_fill(0, $size, '?'));
  }

  function initTables() {
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
        PRIMARY KEY (id),
        FOREIGN KEY (owner_id) REFERENCES nq_owner(id),
        UNIQUE KEY nq_question_owner_ukey_uq (owner_id, ukey)
      ) ENGINE = InnoDB;');

    // TODO: No foreign key on question_id for now; if a question gets deleted, do we want to delete the
    // draw entries? If we use an inner join, the rows here will just not be shown...
    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_draw (
        id int NOT NULL AUTO_INCREMENT,
        question_id int NOT NULL,
        user_id int NOT NULL,
        created datetime NOT NULL,
        solved datetime,
        PRIMARY KEY (id)
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_draw_answer (
        id int NOT NULL AUTO_INCREMENT,
        draw_id int NOT NULL,
        user varchar(100) NOT NULL,
        is_correct boolean NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (draw_id) REFERENCES nq_draw(id),
        UNIQUE KEY nq_draw_user_uq (draw_id, user)
      ) ENGINE = InnoDB;');
  }

  function initOwnerIfEmpty() {
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
        if ($this->conn->inTransaction()) {
          $this->conn->rollBack();
        }
        throw $e;
      }
    }
    return false;
  }
}
