<?php

require './conf/config.php';
require './conf/Configuration.php';
require './inc/DatabaseHandler.php';
require './inc/UserSettings.php';
require './inc/functions.php';
require './inc/Question.php';
require './gen/questions.php';
require './conf/question_types.php';
require './gen/question_type_texts.php';

setJsonHeader();
$db = new DatabaseHandler();
$settings = getSettingsForSecretOrThrow($db);

require './gen/current_state.php';

//
// Check if current question is still unsolved, and whether a new question should be created.
//

$lastQuestion = null;
if (!empty($data_lastQuestions)) {
  $lastQuestion = &$data_lastQuestions[0];
}

$variant = filter_input(INPUT_GET, 'variant', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

if ($settings->activeMode === 'OFF') {
  die(toResultJson(' '));
} else if ($variant === 'timer' && $settings->activeMode !== 'ON') {
  die(toResultJson(' '));
}

if ($lastQuestion !== null && empty($lastQuestion['solver'])) {
  if ($variant === 'timer') {
    $timeSinceLastQuestion = time() - $lastQuestion['created'];
    if ($timeSinceLastQuestion < TIMER_UNSOLVED_QUESTION_WAIT_SECONDS) {
      // Nightbot doesn't accept empty strings, but seems to trim responses and
      // not show anything if there are only spaces, so make sure to have a space in the response.
      die(toResultJson(' '));
    } else {
      $lastAnswer = (int) file_get_contents('./gen/last_answer.txt');
      if (time() - $lastAnswer < TIMER_LAST_ANSWER_WAIT_SECONDS) {
        die(toResultJson(' '));
      }
    }
  } else if ($variant === 'new') {
    $timeSinceLastQuestion = time() - $lastQuestion['created'];
    $secondsToWait = USER_POLL_WAIT_SECONDS - $timeSinceLastQuestion;
    if ($timeSinceLastQuestion < USER_POLL_WAIT_SECONDS) {
      die(toResultJson('Please solve the current question, or wait ' . $secondsToWait . 's'));
    }
  } else {
    $questionText = createQuestionText($lastQuestion, $data_questionTypeTexts);
    die(toResultJson($questionText));
  }
} else if ($variant === 'timer' && $lastQuestion !== null) {
  // The first `if` is triggered if there is a last unsolved question; being here means the
  // last question exists, and it was solved
  if ((time() - $lastQuestion['solved']) < TIMER_SOLVED_QUESTION_WAIT_SECONDS) {
    die(toResultJson(' '));
  }
}


//
// Create new question
//

$newQuestion = selectQuestion($data_questions, $data_lastQuestions);
if ($newQuestion === null) {
  die(toResultJson('Error! Could not find any question. Are your history parameters misconfigured?'));
}
$newQuestionEntry = createQuestionRecord($newQuestion);

$newSize = array_unshift($data_lastQuestions, $newQuestionEntry);

// Trim old puzzles
while ($newSize > HISTORY_KEEP_ENTRIES) {
  array_pop($data_lastQuestions);
  --$newSize;
}

// Handle the previous puzzle in case it was unsolved
$preface = '';
if ($lastQuestion && !isset($lastQuestion['solver'])) {
  $preface = createResolutionText($lastQuestion, $data_questionTypeTexts);
  $lastQuestion['solver'] = '&__unsolved';
  $lastQuestion['solved'] = time();
}

// Save and return new puzzle
updateCurrentState($data_lastQuestions);
$newQuestionText = createQuestionText($newQuestionEntry, $data_questionTypeTexts);
$response = connectTexts($newQuestionText, 'Answer with !a');
echo toResultJson(connectTexts($preface, $response));


function returnLastQuestionIfUnsolved($data_lastQuestions) {
  if (!empty($data_lastQuestions)) {
    $lastQuestion = $data_lastQuestions[0];
    if (!isset($lastQuestion['solver'])) {
      return $lastQuestion;
    }
  }
  return null;
}

function connectTexts($text1, $text2) {
  if (empty($text1)) {
    return $text2;
  } else if (empty($text2)) {
    return $text1;
  }

  $lastCharacter = mb_substr($text1, -1, 1, 'UTF-8');
  if (IntlChar::ispunct($lastCharacter)) {
    return trim($text1) . ' ' . trim($text2);
  }
  return trim($text1) . '. ' . trim($text2);
}