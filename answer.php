<?php

require './conf/config.php';
require './conf/Configuration.php';
require './inc/UserSettings.php';
require './inc/DatabaseHandler.php';
require './inc/functions.php';
require './conf/question_types.php';
require './gen/question_type_texts.php';

setJsonHeader();
$db = new DatabaseHandler();
$settings = getSettingsForSecretOrThrow($db);

if ($settings->activeMode === 'OFF') {
  die(toResultJson(' '));
}

if (!isset($_GET['a'])) {
  die(toResultJson('Please provide a guess! Type ' . COMMAND_QUESTION . ' to see the text.'));
}

require './gen/current_state.php';

if (empty($data_lastQuestions)) {
  die(toResultJson('Error: No question was asked so far!'));
}

$currentQuestion = &$data_lastQuestions[0];
if (isset($currentQuestion['solver'])) {
  if ($currentQuestion['solver'][0] === '!' || $currentQuestion['solver'][0] === '&') {
    die(toResultJson('The answer was already solved. Run !q for a new question'));
  }
  die(toResultJson('The answer was solved by ' . $currentQuestion['solver']));
}

$givenAnswer = filter_input(INPUT_GET, 'a', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR) ?? '';
$givenAnswer = strtolower(unicodeTrim($givenAnswer));

if (empty($givenAnswer)) {
  echo toResultJson('Please provide an answer!');
} else {
  $actualAnswers = getPossibleAnswers($currentQuestion, $data_questionTypeTexts);
  $answerIsMatch = array_search($givenAnswer, $actualAnswers, true) !== false;
  if ($answerIsMatch) {
    $currentQuestion['solver'] = extractUser();
    $currentQuestion['solved'] = time();

    updateCurrentState($data_lastQuestions);
    $congratsOptions = ['Congratulations!', 'Nice!', 'Excellent!', 'Splendid!', 'Perfect!', 'Well done!', 'Awesome!', 'Good job!'];
    $start = $congratsOptions[rand(0, count($congratsOptions) - 1)];
    echo toResultJson($start . ' ' . ucfirst($actualAnswers[0]) . ' is the right answer');
    exit;

  } else {
    $wrongAnswerInfo = processInvalidAnswer($currentQuestion['type'], $givenAnswer, $data_questionTypeTexts);
    if ($wrongAnswerInfo['solved']) {
      $currentQuestion['solver'] = '!' . extractUser();
      $currentQuestion['solved'] = time();
      updateCurrentState($data_lastQuestions);
      echo toResultJson('Sorry, that was not the right answer');
      exit; // Question is solved; no need to update the last answer timestamp

    } else if ($wrongAnswerInfo['invalid']) {
      echo toResultJson('Invalid answer! Type ' . COMMAND_QUESTION . ' to see the question again');
    } else { // !$wrongAnswer['solved'] && !$wrongAnswer['invalid']
      echo toResultJson('Woops, that\'s not the right answer');
    }
  }
}

$fh = fopen('./gen/last_answer.txt', 'w');
if ($fh) {
  fwrite($fh, time());
  fclose($fh);
}


// --------------
// Functions
// --------------

function extractUser() {
  $solver = '';
  if (isset($_SERVER[USER_HTTP_HEADER])) {
    $nightbotUser = $_SERVER[USER_HTTP_HEADER];
    $solver = preg_replace('~^.*?displayName=([^&]+)&.*?$~', '\\1', $nightbotUser);
  }
  return $solver ? $solver : '&__unknown';
}
