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
  if ($question->questionTypeId !== 'custom') {
    return [
      'line' => $question->question,
      'type' => $question->questionTypeId,
      'created' => time()
    ];
  }

  return [
    'line' => $question->question,
    'answers' => $question->answers,
    'textanswer' => $question->textAnswer,
    'created' => time()
  ];
}

// From https://stackoverflow.com/a/4167053
// For some reason, certain users (maybe using Twitch extensions?) write stuff like
// "xho 󠀀", which has a zero-width space at the end. PHP's trim() does not remove it.
function unicodeTrim($text) {
  return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $text);
}

function getSettingsForSecretOrThrow(DatabaseHandler $db): UserSettings {
  if (!isset($_GET['secret']) || !is_string($_GET['secret'])) {
    die(toResultJson('Error: Missing API secret!'));
  }

  $settings = $db->getSettingsForSecret($_GET['secret']);
  if ($settings === null) {
    die(toResultJson('Error: Invalid API secret!'));
  }
  return UserSettings::createFromDbRow($settings);
}

function setJsonHeader() {
  header('Content-type: application/json; charset=utf-8');
}

function updateCurrentState($data_lastQuestions) {
  $fh = fopen('./gen/current_state.php', 'w') or die(toResultJson('Error: failed to update the current state :( Please try again!'));
  fwrite($fh, '<?php $data_lastQuestions = ' . var_export($data_lastQuestions, true) . ';');
  fclose($fh);
}
