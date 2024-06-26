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

  function getSettingsForSecret(string $secret): ?array {
    $stmt = $this->conn->prepare(
     'SELECT nq_owner.id, name, active_mode, timer_solve_creates_new_question, debug_mode,
             timer_unsolved_question_wait, timer_solved_question_wait, timer_last_answer_wait, timer_last_question_query_wait, user_new_wait,
             history_display_entries, history_avoid_last_answers, high_score_days, twitch_name, timer_countdown_seconds, repeat_unanswered_question
      FROM nq_settings
      INNER JOIN nq_owner ON nq_owner.settings_id = nq_settings.id
      WHERE secret = :secret;');
    $stmt->bindParam('secret', $secret);

    return self::execAndFetch($stmt);
  }

  function getSettingsByOwnerId(int $ownerId): ?array {
    $stmt = $this->conn->prepare(
     'SELECT nq_owner.id, name, active_mode, timer_solve_creates_new_question, debug_mode,
             timer_unsolved_question_wait, timer_solved_question_wait, timer_last_answer_wait, timer_last_question_query_wait, user_new_wait,
             history_display_entries, history_avoid_last_answers, high_score_days, twitch_name, timer_countdown_seconds, repeat_unanswered_question
      FROM nq_settings
      INNER JOIN nq_owner ON nq_owner.settings_id = nq_settings.id
      WHERE nq_owner.id = :id;');
    $stmt->bindParam('id', $ownerId);

    return self::execAndFetch($stmt);
  }

  function getIndexPageSettingsForOwner(string $ownerName): ?array {
    $stmt = $this->conn->prepare(
      'SELECT nq_owner.id, history_display_entries, high_score_days
       FROM nq_owner
       INNER JOIN nq_settings
               ON nq_settings.id = nq_owner.settings_id
       WHERE name = :name;');
    $stmt->bindParam('name', $ownerName);

    return self::execAndFetch($stmt);
  }

  function getAdminParamsForOwner(int $ownerId): ?array {
    $stmt = $this->conn->prepare(
      'SELECT name, is_admin, active_mode
       FROM nq_owner
       INNER JOIN nq_settings ON nq_settings.id = nq_owner.settings_id
       WHERE nq_owner.id = :id;');
    $stmt->bindParam('id', $ownerId);

    return self::execAndFetch($stmt);
  }

  function getNightbotToken(int $ownerId): ?array {
    $stmt = $this->conn->prepare(
     'SELECT name, token, token_expires
      FROM nq_owner
      LEFT JOIN nq_owner_nightbot
             ON nq_owner_nightbot.owner_id = nq_owner.id
      WHERE nq_owner.id = :ownerId;');
    $stmt->bindParam('ownerId', $ownerId);
    return self::execAndFetch($stmt);
  }

  function getOwnerSecret(int $ownerId): ?string {
    $stmt = $this->conn->prepare('SELECT secret FROM nq_owner WHERE id = :ownerId;');
    $stmt->bindParam('ownerId', $ownerId);
    $result = self::execAndFetch($stmt);
    return $result ? $result['secret'] : null;
  }

  function getValuesForTimerPage(int $ownerId): ?array {
    $stmt = $this->conn->prepare(
      'SELECT twitch_name, timer_countdown_seconds
       FROM nq_settings
       INNER JOIN nq_owner ON nq_owner.settings_id = nq_settings.id
       WHERE nq_owner.id = :ownerId;');
    $stmt->bindParam('ownerId', $ownerId);
    return self::execAndFetch($stmt);
  }

  function hasQuestionCategoriesOrMore(int $ownerId, int $totalNr): bool {
    $st = $this->conn->query(
      "SELECT COUNT(DISTINCT COALESCE(category, id)) > $totalNr AS has_enough
       FROM nq_question
       WHERE owner_id = $ownerId;");
    return (bool) $st->fetch(PDO::FETCH_ASSOC)['has_enough'];
  }

  function getOwnerIdOnCorrectLogin(string $name, string $pass): ?int {
    $stmt = $this->conn->prepare("SELECT id, password FROM nq_owner WHERE name = :name;");
    $stmt->bindValue('name', strtolower($name));

    $hash = self::execAndFetch($stmt);
    return $hash && password_verify($pass, $hash['password'])
      ? $hash['id']
      : null;
  }

  function setPassword(int $ownerId, string $hash): void {
    $stmt = $this->conn->prepare(
      'UPDATE nq_owner SET password = :hash WHERE id = :id;');
    $stmt->bindParam('hash', $hash);
    $stmt->bindParam('id', $ownerId);
    $stmt->execute();
  }

  function getLastQuestionDraw(int $ownerId, bool $mustBeSolved=false): ?array {
    $andIsSolvedIfNeeded = $mustBeSolved ? 'AND solved IS NOT NULL' : '';
    $stmt = $this->conn->prepare(
     "SELECT nq_draw.id, UNIX_TIMESTAMP(created) AS created, UNIX_TIMESTAMP(solved) AS solved,
             question, answer, type,
             UNIX_TIMESTAMP(last_question) AS last_question, UNIX_TIMESTAMP(last_answer) AS last_answer,
             times_question_queried, UNIX_TIMESTAMP(last_question_repeat) AS last_question_repeat
      FROM nq_draw
      INNER JOIN nq_question
              ON nq_question.id = nq_draw.question_id
      LEFT JOIN nq_draw_stats
             ON nq_draw_stats.draw_id = nq_draw.id
      WHERE nq_draw.owner_id = :ownerId
            $andIsSolvedIfNeeded
      ORDER BY solved IS NULL DESC, solved DESC
      LIMIT 1;");
    $stmt->bindParam('ownerId', $ownerId);

    return self::execAndFetch($stmt);
  }

  function drawNewQuestion(int $ownerId, string $ownerName, int $pastQuestionsToSkip): ?array {
    // Step 1: Set draws as solved that might exist for the owner
    $this->conn->exec(
      "UPDATE nq_draw
       SET solved = NOW()
       WHERE owner_id = $ownerId AND solved IS NULL;");

    // Step 2: Find new question to draw
    $stmt = $this->conn->prepare(
     "SELECT nq_question.id, question, answer, type
      FROM nq_question
      LEFT JOIN (
        SELECT COALESCE(category, question_id) AS past
        FROM nq_draw
        INNER JOIN nq_question ON nq_draw.question_id = nq_question.id
        WHERE nq_draw.owner_id = $ownerId
        ORDER BY solved DESC
        LIMIT $pastQuestionsToSkip
      ) past_draws ON past_draws.past = COALESCE(nq_question.category, nq_question.id)
      WHERE owner_id = $ownerId
        AND past IS NULL
      ORDER BY RAND()
      LIMIT 5;");

    if ($ownerName === 'highway') {
      $stmt->execute();
      $newQuestions = $stmt->fetchAll();
      $result = $this->highwaySelectQuestion($newQuestions, $ownerId);
    } else {
      $result = self::execAndFetch($stmt);
    }

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

  // Custom logic to select a potential new question that isn't too repetitive: players prefer to guess on
  // languages that are somewhat familiar to them, so don't draw, for example, too many Cyrillic languages
  private function highwaySelectQuestion(array $rows, int $ownerId): ?array {
    if (empty($rows)) {
      return null;
    }

    $stmt = $this->conn->prepare(
      "SELECT DISTINCT category
       FROM nq_draw
       INNER JOIN nq_question ON nq_question.id = nq_draw.question_id
       WHERE created > NOW() - INTERVAL 1 DAY
         AND nq_draw.owner_id = $ownerId
       ORDER BY created DESC
       LIMIT 10;");
    $stmt->execute();
    $recentLanguages = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $indian   = ['te', 'ta', 'hi', 'kn', 'bn'];
    $cyrillic = ['mn', 'bg', 'uk', 'be', 'ru', 'sr', 'mk', 'ky', 'kk'];
    $african  = ['zu', 'sw', 'yo', 'xh', 'ln'];
    $exotic   = ['ch', 'rm', 'uz', 'km', 'th', 'nv', 'eo', 'ka', 'hy', 'ar', 'fa']; // ill-defined category :)

    $langsToSkip = ['sb']; // Always skip Sorbian for now
    if (array_intersect($indian, $recentLanguages)) {
      array_push($langsToSkip, ...$indian);
    }
    if (array_intersect($cyrillic, $recentLanguages)) {
      array_push($langsToSkip, ...$cyrillic);
    }
    if (array_intersect($african, $recentLanguages)) {
      array_push($langsToSkip, ...$african);
    }
    if (array_intersect($exotic, $recentLanguages)) {
      array_push($langsToSkip, ...$exotic);
    }

    foreach ($rows as $row) {
      $answer = $row['answer'];
      if (!in_array($answer, $langsToSkip)) {
        return $row;
      }
    }
    // Looks like we're supposed to skip all languages, so just return the first one
    // Note: We can only be here if $rows isn't empty, so we can safely get the first entry without any checks
    return $rows[0];
  }

  function setCurrentDrawAsSolved(int $drawId): void {
    $stmt = $this->conn->prepare('UPDATE nq_draw SET solved = NOW() WHERE id = :id');
    $stmt->bindParam('id', $drawId);
    $stmt->execute();
  }

  function saveDrawAnswer(int $drawId, string $userName, string $answer): int {
    $stmt = $this->conn->prepare(
     'INSERT INTO nq_draw_answer (draw_id, created, user, answer)
      VALUES (:drawId, NOW(), :user, :answer)
      ON DUPLICATE KEY UPDATE created = NOW(), answer = VALUES(answer);');
    $stmt->bindParam('drawId', $drawId);
    $stmt->bindParam('user', $userName);
    $stmt->bindParam('answer', $answer);

    $stmt->execute();
    return $stmt->rowCount();
  }

  function getCorrectAnswers(int $drawId, string $correctAnswer): array {
    $stmt = $this->conn->prepare(
     'SELECT COALESCE(SUM(answer = :correctAnswer), 0) AS total_correct,
             COUNT(1) AS total,
             (SELECT user FROM nq_draw_answer WHERE draw_id = :drawId AND answer = :correctAnswer LIMIT 1) AS user
      FROM nq_draw_answer
      WHERE draw_id = :drawId;');
    $stmt->bindParam('correctAnswer', $correctAnswer);
    $stmt->bindParam('drawId', $drawId);
    return self::execAndFetch($stmt);
  }

  function saveQuizActiveMode(int $ownerId, string $activeMode): void {
    $stmt = $this->conn->prepare(
      'UPDATE nq_settings
       SET active_mode = :activeMode
       WHERE id IN (
         SELECT settings_id 
         FROM nq_owner
         WHERE id = :ownerId
       );');
    $stmt->bindParam('activeMode', $activeMode);
    $stmt->bindParam('ownerId', $ownerId);

    $stmt->execute();
  }

  function saveTimerCountdownValue(int $ownerId, ?int $timerCountdown): void {
    $stmt = $this->conn->prepare(
      'UPDATE nq_settings
       SET timer_countdown_seconds = :timerCountdown
       WHERE id IN (
         SELECT settings_id 
         FROM nq_owner
         WHERE id = :ownerId
       );');
    $stmt->bindValue('timerCountdown', $timerCountdown);
    $stmt->bindValue('ownerId', $ownerId);

    $stmt->execute();
  }

  function getTopScores(int $ownerId, int $limitInDays): array {
    $stmt = $this->conn->prepare("
      SELECT user,
       COUNT(1) AS total,
       COALESCE(SUM(corr = 1), 0) AS correct
      FROM (
        SELECT user, (nq_draw_answer.answer = nq_question.answer) AS corr
        FROM nq_draw_answer
        INNER JOIN nq_draw ON nq_draw.id = nq_draw_answer.draw_id
        INNER JOIN nq_question ON nq_question.id = nq_draw.question_id
        WHERE draw_id IN (
          SELECT id
          FROM nq_draw
          WHERE DATEDIFF(NOW(), created) <= $limitInDays
        )
        AND nq_draw.owner_id = $ownerId
        AND nq_draw.solved IS NOT NULL
      ) draw_answers
      GROUP BY user
      ORDER BY correct DESC, total ASC
      LIMIT 50;");

    $stmt->execute();
    return $stmt->fetchAll();
  }

  function getLastDraws(int $ownerId, int $maxEntries): array {
    $stmt = $this->conn->prepare(
     "SELECT nq_draw.id, UNIX_TIMESTAMP(solved) AS solved, question, answer, type
      FROM nq_draw
      INNER JOIN nq_question
              ON nq_question.id = nq_draw.question_id
      WHERE nq_draw.owner_id = $ownerId
      ORDER BY solved IS NULL DESC, solved DESC
      LIMIT $maxEntries;");
    $stmt->execute();
    return $stmt->fetchAll();
  }

  function getUserAnswersOnDraws(array $drawIds, array $userNames): array {
    $drawIdPlaceholders = self::repeatCommaSeparated('?', count($drawIds));
    $userPlaceholders   = self::repeatCommaSeparated('?', count($userNames));

    $stmt = $this->conn->prepare("
      SELECT draw_id, user, answer
      FROM nq_draw_answer
      WHERE draw_id IN ($drawIdPlaceholders)
        AND LOWER(user) IN ($userPlaceholders)");

    $i = 1;
    foreach ($drawIds as $drawId) {
      $stmt->bindValue($i++, $drawId);
    }
    foreach ($userNames as $userName) {
      $stmt->bindValue($i++, strtolower($userName));
    }
    $stmt->execute();
    return $stmt->fetchAll();
  }

  function saveLastAnswerTimestamp(int $drawId): void {
    $stmt = $this->conn->prepare(
      'INSERT INTO nq_draw_stats (draw_id, last_answer)
       VALUES (:drawId, NOW())
       ON DUPLICATE KEY UPDATE last_answer = NOW();');

    $stmt->bindParam('drawId', $drawId);
    $stmt->execute();
  }

  function saveLastQuestionQuery(int $drawId, bool $updateLastRepeatTimestamp): void {
    $repeatValue = $updateLastRepeatTimestamp ? 'NOW()' : 'NULL';
    $stmt = $this->conn->prepare(
      "INSERT INTO nq_draw_stats (draw_id, last_question, times_question_queried, last_question_repeat)
       VALUES (:drawId, NOW(), 1, $repeatValue)
       ON DUPLICATE KEY UPDATE last_question = NOW(),
                               times_question_queried = COALESCE(times_question_queried, 0) + 1,
                               last_question_repeat = COALESCE($repeatValue, last_question_repeat);");

    $stmt->bindParam('drawId', $drawId);
    $stmt->execute();
  }

  function getOwnerInfoForOverviewPage(int $ownerId): array {
    $stmt = $this->conn->prepare(
     'SELECT active_mode, client_id, token_expires
      FROM nq_owner
      INNER JOIN nq_settings
              ON nq_settings.id = nq_owner.settings_id
      LEFT JOIN nq_owner_nightbot
             ON nq_owner_nightbot.owner_id = nq_owner.id
      WHERE nq_owner.id = :ownerId;');
    $stmt->bindParam('ownerId', $ownerId);
    return self::execAndFetch($stmt);
  }

  function getQuestionStatsForOwner(int $ownerId): array {
    $stmt = $this->conn->prepare(
     "SELECT COUNT(DISTINCT nq_question.id) AS sum_questions,
             COUNT(DISTINCT COALESCE(nq_question.category, nq_question.id)) AS sum_categories,
             COUNT(DISTINCT nq_draw.id) AS sum_draws,
             COUNT(DISTINCT nq_draw_answer.id) AS sum_draw_answers
      FROM nq_question
      LEFT JOIN nq_draw ON nq_draw.owner_id = $ownerId
      LEFT JOIN nq_draw_answer ON nq_draw_answer.draw_id = nq_draw.id
      WHERE nq_question.owner_id = $ownerId;");
    return self::execAndFetch($stmt);
  }

  function getConfigurableStatFields(int $ownerId): array {
    $stmt = $this->conn->prepare('SELECT data_url, public_page_url FROM nq_owner_stats
      WHERE id IN (SELECT stats_id FROM nq_owner WHERE id = :ownerId);');
    $stmt->bindParam('ownerId', $ownerId);
    return self::execAndFetch($stmt);
  }

  function saveConfigurableStatFields(int $ownerId, ?string $dataUrl, ?string $publicPageUrl): void {
    $stmt = $this->conn->prepare(
      'UPDATE nq_owner_stats
      SET data_url = :dataUrl,
          public_page_url = :publicPageUrl
      WHERE id IN (SELECT stats_id FROM nq_owner WHERE id = :ownerId);');
    $stmt->bindValue('dataUrl', $dataUrl);
    $stmt->bindValue('publicPageUrl', $publicPageUrl);
    $stmt->bindParam('ownerId', $ownerId);
    $stmt->execute();
  }

  function updateSettingsForOwnerId(int $ownerId, OwnerSettings $stgs): bool {
    $stmt = $this->conn->prepare(
     'UPDATE nq_settings SET
        active_mode = :active_mode,
        timer_solve_creates_new_question = :timer_solve_creates_new_question,
        debug_mode = :debug_mode,
        timer_countdown_seconds = :timer_countdown_seconds,
        repeat_unanswered_question = :repeat_unanswered_question,
        timer_unsolved_question_wait = :timer_unsolved_question_wait,
        timer_solved_question_wait = :timer_solved_question_wait,
        timer_last_answer_wait = :timer_last_answer_wait,
        timer_last_question_query_wait = :timer_last_question_query_wait,
        user_new_wait = :user_new_wait,
        history_display_entries = :history_display_entries,
        history_avoid_last_answers = :history_avoid_last_answers,
        high_score_days = :high_score_days,
        twitch_name = :twitch_name
      WHERE id IN (
        SELECT settings_id FROM nq_owner WHERE id = :ownerId
      );');

    $stmt->bindParam('active_mode', $stgs->activeMode);
    $stmt->bindParam('timer_solve_creates_new_question', $stgs->timerSolveCreatesNewQuestion);
    $stmt->bindParam('debug_mode', $stgs->debugMode);
    $stmt->bindValue('timer_countdown_seconds', $stgs->timerCountdownSeconds);
    $stmt->bindParam('repeat_unanswered_question', $stgs->repeatUnansweredQuestion);
    $stmt->bindParam('timer_unsolved_question_wait', $stgs->timerUnsolvedQuestionWait);
    $stmt->bindParam('timer_solved_question_wait', $stgs->timerSolvedQuestionWait);
    $stmt->bindParam('timer_last_answer_wait', $stgs->timerLastAnswerWait);
    $stmt->bindParam('timer_last_question_query_wait', $stgs->timerLastQuestionQueryWait);
    $stmt->bindParam('user_new_wait', $stgs->userNewWait);
    $stmt->bindParam('history_display_entries', $stgs->historyDisplayEntries);
    $stmt->bindParam('history_avoid_last_answers', $stgs->historyAvoidLastAnswers);
    $stmt->bindParam('high_score_days', $stgs->highScoreDays);
    $stmt->bindParam('twitch_name', $stgs->twitchName);
    $stmt->bindParam('ownerId', $ownerId);

    $stmt->execute();
    return $stmt->rowCount() > 0;
  }

  function createNewOwner(string $name, string $pass, bool $isAdmin=false): void {
    $stgs = OwnerSettings::createWithDefaults();
    $stmt = $this->conn->prepare('INSERT INTO nq_settings (
        active_mode,
        timer_solve_creates_new_question,
        debug_mode,
        timer_countdown_seconds,
        repeat_unanswered_question,
        timer_unsolved_question_wait,
        timer_solved_question_wait,
        timer_last_answer_wait,
        timer_last_question_query_wait,
        user_new_wait,
        history_display_entries,
        history_avoid_last_answers,
        high_score_days,
        twitch_name)
      VALUES (
        :active_mode,
        :timer_solve_creates_new_question,
        :debug_mode,
        :timer_countdown_seconds,
        :repeat_unanswered_question,
        :timer_unsolved_question_wait,
        :timer_solved_question_wait,
        :timer_last_answer_wait,
        :timer_last_question_query_wait,
        :user_new_wait,
        :history_display_entries,
        :history_avoid_last_answers,
        :high_score_days,
        :twitch_name);');
    $stmt->bindParam('active_mode', $stgs->activeMode);
    $stmt->bindParam('timer_solve_creates_new_question', $stgs->timerSolveCreatesNewQuestion);
    $stmt->bindParam('debug_mode', $stgs->debugMode);
    $stmt->bindValue('timer_countdown_seconds', $stgs->timerCountdownSeconds);
    $stmt->bindParam('repeat_unanswered_question', $stgs->repeatUnansweredQuestion);
    $stmt->bindParam('timer_unsolved_question_wait', $stgs->timerUnsolvedQuestionWait);
    $stmt->bindParam('timer_solved_question_wait', $stgs->timerSolvedQuestionWait);
    $stmt->bindParam('timer_last_answer_wait', $stgs->timerLastAnswerWait);
    $stmt->bindParam('timer_last_question_query_wait', $stgs->timerLastQuestionQueryWait);
    $stmt->bindParam('user_new_wait', $stgs->userNewWait);
    $stmt->bindParam('history_display_entries', $stgs->historyDisplayEntries);
    $stmt->bindParam('history_avoid_last_answers', $stgs->historyAvoidLastAnswers);
    $stmt->bindParam('high_score_days', $stgs->highScoreDays);
    $stmt->bindParam('twitch_name', $stgs->twitchName);

    $stmt->execute();

    $query = $this->conn->query('SELECT LAST_INSERT_ID();');
    $query->execute();
    $settingsId = $query->fetch(PDO::FETCH_NUM)[0];

    $stmt = $this->conn->prepare('INSERT INTO nq_owner_stats VALUES ();');
    $stmt->execute();

    $query = $this->conn->query('SELECT LAST_INSERT_ID();');
    $query->execute();
    $statsId = $query->fetch(PDO::FETCH_NUM)[0];

    $secret = substr(md5(microtime()), 0, 17);
    $passHash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $this->conn->prepare(
     "INSERT INTO nq_owner (name, secret, settings_id, stats_id, password, is_admin)
      VALUES (:name, :secret, :settingsId, :statsId, :passHash, :isAdmin);");
    $stmt->bindValue('name', strtolower($name));
    $stmt->bindParam('secret', $secret);
    $stmt->bindParam('settingsId', $settingsId);
    $stmt->bindParam('statsId', $statsId);
    $stmt->bindParam('passHash', $passHash);
    $stmt->bindParam('isAdmin', $isAdmin);
    $stmt->execute();
  }

  function getSystemStatistics(): array {
    $query = $this->conn->query(
      'SELECT nq_owner.id, nq_owner.name,
       sum_questions, sum_drawn_questions, sum_draws, sum_draw_answers, sum_orphaned_draws, sum_orphaned_answers
      FROM nq_owner
      LEFT JOIN (
        SELECT owner_id, COUNT(1) AS sum_questions
        FROM nq_question
        GROUP BY owner_id
      ) stats_questions
        ON stats_questions.owner_id = nq_owner.id
      LEFT JOIN (
        SELECT owner_id, COUNT(1) AS sum_draws,
               COUNT(DISTINCT question_id) AS sum_drawn_questions
        FROM nq_draw
        GROUP BY owner_id
      ) stats_draws
        ON stats_draws.owner_id = nq_owner.id
      LEFT JOIN (
        SELECT owner_id, COUNT(1) AS sum_draw_answers
        FROM nq_draw_answer
        INNER JOIN nq_draw
                ON nq_draw.id = nq_draw_answer.draw_id
        GROUP BY owner_id
      ) stats_draw_answers
        ON stats_draw_answers.owner_id = nq_owner.id
      LEFT JOIN (
        SELECT owner_id, COUNT(1) AS sum_orphaned_draws
        FROM nq_draw
        WHERE question_id NOT IN (SELECT id FROM nq_question)
        GROUP BY owner_id
      ) stats_orph_draws
        ON stats_orph_draws.owner_id = nq_owner.id
      LEFT JOIN (
        SELECT owner_id, COUNT(1) AS sum_orphaned_answers
        FROM nq_draw_answer
        INNER JOIN nq_draw ON nq_draw.id = nq_draw_answer.draw_id
        WHERE question_id NOT IN (SELECT id FROM nq_question)
        GROUP BY owner_id
      ) stats_orph_answers
        ON stats_orph_answers.owner_id = nq_owner.id
      ;');

    return $query->fetchAll();
  }

  function countQuestionsByType(): array {
    $query = $this->conn->query(
     'SELECT type, COUNT(1) AS total,
                   COUNT(DISTINCT category) AS total_categories
      FROM nq_question
      GROUP BY type;');

    return $query->fetchAll();
  }

  function getOwnerNightbotInfo(int $ownerId): ?array {
    $stmt = $this->conn->prepare(
     'SELECT client_id, client_secret, token, token_expires
      FROM nq_owner_nightbot
      WHERE owner_id = :ownerId');
    $stmt->bindParam('ownerId', $ownerId);
    return self::execAndFetch($stmt);
  }

  function updateNightbotClientInfo(int $ownerId, OwnerNightbotInfo $nightbotInfo): void {
    $stmt = $this->conn->prepare(
      'INSERT INTO nq_owner_nightbot (owner_id, client_id, client_secret)
       VALUES (:ownerId, :clientId, :clientSecret)
       ON DUPLICATE KEY UPDATE client_id = VALUES(client_id), client_secret = VALUES(client_secret);');

    $stmt->bindParam('ownerId', $ownerId);
    $stmt->bindParam('clientId', $nightbotInfo->clientId);
    $stmt->bindParam('clientSecret', $nightbotInfo->clientSecret);
    $stmt->execute();
  }

  function updateNightbotTokenInfo(int $ownerId, OwnerNightbotInfo $nightbotInfo, string $refreshToken): void {
    $stmt = $this->conn->prepare(
      'INSERT INTO nq_owner_nightbot (owner_id, token, token_expires, refresh_token)
       VALUES (:ownerId, :token, :tokenExpires, :refreshToken)
       ON DUPLICATE KEY UPDATE token = VALUES(token), token_expires = VALUES(token_expires), refresh_token = VALUES(refresh_token);');

    $stmt->bindParam('ownerId', $ownerId);
    $stmt->bindParam('token', $nightbotInfo->token);
    $stmt->bindParam('tokenExpires', $nightbotInfo->tokenExpires);
    $stmt->bindParam('refreshToken', $refreshToken);
    $stmt->execute();
  }

  function getAllOwners(): array {
    $query = $this->conn->query('SELECT nq_owner.id, nq_owner.name FROM nq_owner');
    return $query->fetchAll();
  }

  function deleteDemoAnswers(int $ownerId): int {
    $stmt = $this->conn->prepare(
     "DELETE FROM nq_draw_answer
      WHERE user LIKE 'demo%'
        AND draw_id IN (
          SELECT id FROM nq_draw WHERE owner_id = :ownerId);");
    $stmt->bindParam('ownerId', $ownerId);
    $stmt->execute();
    return $stmt->rowCount();
  }

  function deleteEmptyDraw(int $drawId): bool {
    $stmt = $this->conn->prepare(
      "DELETE FROM nq_draw_stats
       WHERE draw_id = :drawId 
         AND NOT EXISTS (
            SELECT 1
            FROM nq_draw_answer
            WHERE draw_id = :drawId
         )");
    $stmt->bindParam('drawId', $drawId);
    $stmt->execute();

    $stmt = $this->conn->prepare(
      "DELETE FROM nq_draw
       WHERE id = :drawId
          AND NOT EXISTS (
            SELECT 1
            FROM nq_draw_answer
            WHERE draw_id = :drawId
      );");
    $stmt->bindParam('drawId', $drawId);
    $stmt->execute();
    return $stmt->rowCount() > 0;
  }

  function deleteEmptyDraws(int $ownerId): int {
    $stmt = $this->conn->prepare(
      "DELETE FROM nq_draw_stats
       WHERE draw_id IN (
           SELECT id
           FROM nq_draw
           WHERE owner_id = :ownerId
             AND id NOT IN (SELECT draw_id FROM nq_draw_answer)
       );");
    $stmt->bindParam('ownerId', $ownerId);
    $stmt->execute();

    $stmt = $this->conn->prepare(
     "DELETE FROM nq_draw
      WHERE id NOT IN (
          SELECT draw_id
          FROM nq_draw_answer
      )
        AND owner_id = :ownerId
        AND solved IS NOT NULL;");
    $stmt->bindParam('ownerId', $ownerId);
    $stmt->execute();
    return $stmt->rowCount();
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
        ON DUPLICATE KEY UPDATE question = VALUES(question), answer = VALUES(answer),
          type = VALUES(type), category = VALUES(category);";
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
        timer_solve_creates_new_question boolean NOT NULL,
        timer_unsolved_question_wait int NOT NULL,
        timer_solved_question_wait int NOT NULL,
        timer_last_answer_wait int NOT NULL,
        timer_last_question_query_wait int NOT NULL,
        user_new_wait int NOT NULL,
        history_display_entries int NOT NULL,
        history_avoid_last_answers int NOT NULL,
        high_score_days int NOT NULL,
        debug_mode int NOT NULL,
        repeat_unanswered_question int NOT NULL,
        twitch_name varchar(128),
        timer_countdown_seconds int,
        PRIMARY KEY (id)
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_owner_stats (
        id int NOT NULL AUTO_INCREMENT,
        data_url varchar(200),
        public_page_url varchar(200),
        PRIMARY KEY (id)
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_owner (
        id int NOT NULL AUTO_INCREMENT,
        name varchar(50) NOT NULL,
        secret varchar(50) NOT NULL,
        settings_id int NOT NULL,
        stats_id int NOT NULL,
        password varchar(255) NOT NULL,
        is_admin boolean NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (settings_id) REFERENCES nq_settings(id),
        FOREIGN KEY (stats_id) REFERENCES nq_owner_stats(id),
        UNIQUE KEY nq_owner_secret_uq (secret) USING BTREE,
        UNIQUE KEY nq_owner_name (name) USING BTREE
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_owner_nightbot (
        id int NOT NULL AUTO_INCREMENT,
        owner_id int NOT NULL,
        client_id varchar(64),
        client_secret varchar(64),
        token varchar(64),
        token_expires int(11) UNSIGNED,
        refresh_token varchar(64),
        PRIMARY KEY (id),
        FOREIGN KEY (owner_id) REFERENCES nq_owner(id),
        UNIQUE KEY nq_owr_nightbot_owner_uq (owner_id) USING BTREE
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

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_draw_stats (
        id int NOT NULL AUTO_INCREMENT,
        draw_id int NOT NULL,
        last_question datetime,
        last_answer datetime,
        times_question_queried int,
        last_question_repeat datetime,
        PRIMARY KEY (id),
        FOREIGN KEY (draw_id) REFERENCES nq_draw(id),
        UNIQUE KEY nq_draw_stats_draw_id_uq (draw_id)
      ) ENGINE = InnoDB;');

    $this->conn->exec('CREATE TABLE IF NOT EXISTS nq_draw_answer (
        id int NOT NULL AUTO_INCREMENT,
        draw_id int NOT NULL,
        created datetime NOT NULL,
        user varchar(100) NOT NULL,
        answer varchar(200) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (draw_id) REFERENCES nq_draw(id),
        UNIQUE KEY nq_draw_user_uq (draw_id, user)
      ) ENGINE = InnoDB;');
  }

  function initOwnerIfEmpty(): ?string {
    $query = $this->conn->query('SELECT EXISTS (SELECT 1 FROM nq_owner);');
    $query->execute();
    $hasOwner = $query->fetch(PDO::FETCH_NUM)[0];

    if (!$hasOwner) {
      try {
        $this->conn->beginTransaction();

        $randomPass = bin2hex(random_bytes(12));
        $this->createNewOwner('admin', $randomPass, true);

        $this->conn->commit();
        return $randomPass;
      } catch (Exception $e) {
        $this->rollBackIfNeeded();
        throw $e;
      }
    }
    return null;
  }
}
