<?php

require './conf/config.php';
require './inc/functions.php';

setJsonHeader();
verifyApiSecret();

if (!isset($_GET['a'])) {
  die(toResultJson('Please provide a guess! Type ' . COMMAND_QUESTION . ' to see the text.'));
}

require './conf/current_state.php';

if (empty($data_lastQuestions)) {
  die(toResultJson('Error: No question was asked so far!'));
}

$currentQuestion = &$data_lastQuestions[0];
if (isset($currentQuestion['solver'])) {
  die(toResultJson('The answer was solved by ' . $currentQuestion['solver']));
}

$answer = filter_input(INPUT_GET, 'a', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR) ?? '';
$answer = strtolower(trim($answer));

if (empty($answer)) {
  echo toResultJson('Please provide an answer!');
} else {
  $answerIsMatch = array_search($answer, $currentQuestion['answers'], true);
  if ($answerIsMatch) {
    $currentQuestion['solver'] = extractUser();
    $currentQuestion['solved'] = time();

    updateCurrentState($data_lastQuestions);
    $congratsOptions = ['Congratulations!', 'Nice!', 'Excellent!', 'Splendid!', 'Perfect!', 'Well done!', 'Awesome!', 'Good job!'];
    $start = $congratsOptions[rand(0, count($congratsOptions) - 1)];
    echo toResultJson($start . ' ' . $currentQuestion['textanswer'] . ' is the right answer');
    exit;

  } else {
    echo toResultJson('Sorry, that\'s not the right answer');
  }
}

$fh = fopen('./conf/last_answer.php', 'w');
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
