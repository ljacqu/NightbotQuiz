<?php

require './conf/config.php';
require './inc/functions.php';
require './conf/question_types.php';
require './gen/question_type_texts.php';

setJsonHeader();
verifyApiSecret();

if (!isset($_GET['a'])) {
  die(toResultJson('Please provide a guess! Type ' . COMMAND_QUESTION . ' to see the text.'));
}

require './gen/current_state.php';

if (empty($data_lastQuestions)) {
  die(toResultJson('Error: No question was asked so far!'));
}

$currentQuestion = &$data_lastQuestions[0];
if (isset($currentQuestion['solver'])) {
  die(toResultJson('The answer was solved by ' . $currentQuestion['solver']));
}

$givenAnswer = filter_input(INPUT_GET, 'a', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR) ?? '';
$givenAnswer = strtolower(trim($givenAnswer));

if (empty($givenAnswer)) {
  echo toResultJson('Please provide an answer!');
} else {
  $actualAnswers = isset($currentQuestion['type'])
    ? $data_questionTypeTexts[$currentQuestion['type']]['answers']
    : $currentQuestion['answers'];
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
    echo toResultJson('Sorry, that\'s not the right answer');
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
    $solver = preg_replace('~^.*?name=([^&]+)&.*?$~', '\\1', $nightbotUser);
  }
  return $solver ? $solver : '&__unknown';
}
