<?php

function toResultJson($text) {
  return json_encode(['result' => $text], JSON_FORCE_OBJECT);
}

/**
 * Selects a new question, avoiding past questions as configured.
 *
 * @param Question[] $questions
 * @param array[] $lastQuestions
 * @return Question|null new question to select
 */
function selectQuestion($questions, $lastQuestions) {
  $skipTexts = [];

  $cnt = 1;
  foreach ($lastQuestions as $pastQuestion) {
    if ($cnt <= HISTORY_AVOID_LAST_N_QUESTIONS) {
      $skipTexts[] = $pastQuestion['line'];
    } else {
      break;
    }
    ++$cnt;
  }

  $actualChoices = array_filter($questions, function ($question) use ($skipTexts) {
    return !in_array($question->question, $skipTexts, true);
  });

  if (empty($actualChoices)) {
    return null;
  }
  return $actualChoices[ array_rand($actualChoices, 1) ];
}

function createQuestionRecord(Question $question) {
  return [
    'line' => $question->question,
    'answers' => $question->answers,
    'textanswer' => $question->textAnswer,
    'created' => time()
  ];
}

function verifyApiSecret() {
  if (!isset($_GET['secret'])) {
    die(toResultJson('Error: Missing API secret!'));
  } else if ($_GET['secret'] !== API_SECRET) {
    die(toResultJson('Error: Invalid API secret!'));
  } else if (API_SECRET === 'setme') {
    die(toResultJson('Error: Update the API secret in config.php'));
  }
}

function setJsonHeader() {
  header('Content-type: application/json; charset=utf-8');
}

function updateCurrentState($data_lastQuestions) {
  $fh = fopen('./conf/current_state.php', 'w') or die(toResultJson('Error: failed to update the current state :( Please try again!'));
  fwrite($fh, '<?php $data_lastQuestions = ' . var_export($data_lastQuestions, true) . ';');
  fclose($fh);
}
