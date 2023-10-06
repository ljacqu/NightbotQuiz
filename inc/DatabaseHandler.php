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

  function getSettingsForSecret(string $secret): array|null {
    $stmt = $this->conn->prepare('SELECT * FROM nq_settings WHERE id IN (SELECT settings_id FROM nq_user WHERE secret = :secret);');
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
        SELECT settings_id FROM nq_user WHERE secret = :secret
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

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_user (
        id int NOT NULL AUTO_INCREMENT,
        name varchar(50) NOT NULL,
        secret varchar(50) NOT NULL,
        settings_id int NOT NULL,
        is_admin boolean NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY nq_user_secret_uq (secret) USING BTREE,
        FOREIGN KEY (settings_id) REFERENCES nq_settings(id)
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_question (
        id int NOT NULL AUTO_INCREMENT,
        user_id int NOT NULL,
        question varchar(200) NOT NULL,
        answer varchar(200) NOT NULL,
        type varchar(50) NOT NULL,
        PRIMARY KEY (id)
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
        UNIQUE KEY nq_draw_user_uq (draw_id, user),
        FOREIGN KEY (draw_id) REFERENCES nq_draw(id)
      ) ENGINE = InnoDB;');
  }

  function initUserIfEmpty() {
    $query = $this->conn->query('SELECT EXISTS (SELECT 1 FROM nq_user);');
    $query->execute();
    $hasUser = $query->fetch()[0];

    if (!$hasUser) {
      try {
        $this->conn->beginTransaction();

        $this->conn->exec('INSERT INTO nq_settings
               (active_mode, timer_unsolved_question_wait, timer_solved_question_wait, timer_last_answer_wait, user_new_wait, history_display_entries, history_avoid_last_answers)
        VALUES ("ON",                                 180,                        180,                    120,            90,                       0,                          0)');
        $query = $this->conn->query('SELECT LAST_INSERT_ID();');
        $query->execute();
        $settingsId = $query->fetch()[0];

        $secret = substr(md5(microtime()), 0, 17);
        $this->conn->exec('INSERT INTO nq_user (name, secret, settings_id, is_admin)
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

  // -------------
  // OLD
  // --------------
  function getRating($user, $level) {
    $stmt = $this->conn->prepare('SELECT rating FROM tr_level_rating WHERE user = :user AND level = :level;');
    $stmt->bindParam('user', $user);
    $stmt->bindParam('level', $level);
    $stmt->execute();
    return $stmt->fetch();
  }

  function getRatings($user) {
    $stmt = $this->conn->prepare('SELECT level, rating FROM tr_level_rating WHERE user = :user');
    $stmt->bindParam('user', $user);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function getAverage($level) {
    $stmt = $this->conn->prepare('SELECT AVG(rating) as avg, COUNT(rating) as cnt FROM tr_level_rating WHERE level = :level;');
    $stmt->bindParam('level', $level);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  function getAverages() {
    $stmt = $this->conn->prepare('SELECT level, avg(rating) as avg, count(rating) as cnt FROM tr_level_rating group by (level);');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function addOrUpdateRating($user, $level, $rating) {
    $stmt = $this->conn->prepare('
      INSERT INTO tr_level_rating (level, user, rating, date)
      VALUES (:level, :user, :rating, NOW())
      ON DUPLICATE KEY UPDATE rating = :rating, date = NOW();');

    $stmt->bindParam('user', $user);
    $stmt->bindParam('level', $level);
    $stmt->bindParam('rating', $rating);
    $stmt->execute();
  }


}
