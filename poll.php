<?php

require './conf/config.php';
require './conf/Configuration.php';
require './inc/DatabaseHandler.php';
require './inc/UserSettings.php';
require './inc/functions.php';
require './inc/Question.php';
require './gen/questions.php';
require './inc/OwnerPollValues.php';
require './inc/SecretValidator.php';
require './inc/QuestionType.php';
require './conf/question_types.php';
require './gen/question_type_texts.php';
require './inc/questiontype/PlaceQuestionType.php';
require './inc/questiontype/CustomQuestionType.php';

setJsonHeader();
$db = new DatabaseHandler();
$pollParameters = SecretValidator::getOwnerValuesForPollOrExit($db);

$variant = filter_input(INPUT_GET, 'variant', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if ($pollParameters->activeMode === 'OFF') {
  die(toResultJson(' '));
} else if ($variant === 'timer' && $pollParameters->activeMode !== 'ON') {
  die(toResultJson(' '));
}


//
// Check if current question is still unsolved, and whether a new question should be created.
//

$lastQuestion = getLastQuestion($pollParameters->ownerId, $db);


if ($lastQuestion !== null && empty($lastQuestion['solved'])) {
  if ($variant === 'timer') {
    $timeSinceLastQuestion = time() - $lastQuestion['created'];
    if ($timeSinceLastQuestion < $pollParameters->timerUnsolvedQuestionWait) {
      // Nightbot doesn't accept empty strings, but seems to trim responses and
      // not show anything if there are only spaces, so make sure to have a space in the response.
      die(toResultJson(' '));
    } else {
      $lastAnswer = (int) file_get_contents('./gen/last_answer.txt'); # TODO: Store where?
      if (time() - $lastAnswer < $pollParameters->timerLastAnswerWait) {
        die(toResultJson(' '));
      }
    }
  } else if ($variant === 'new') {
    $timeSinceLastQuestion = time() - $lastQuestion['created'];
    if ($timeSinceLastQuestion < $pollParameters->userNewWait) {
      $secondsToWait = $pollParameters->userNewWait - $timeSinceLastQuestion;
      die(toResultJson('Please solve the current question, or wait ' . $secondsToWait . 's'));
    }
  } else {
    $questionText = QuestionType::generateQuestionText($lastQuestion['question'], '.'); // TODO: Revise this---
    die(toResultJson($questionText));
  }
} else if ($variant === 'timer' && $lastQuestion !== null) {
  // The first `if` is triggered if there is a last unsolved question; being here means the
  // last question exists, and it was solved
  if ((time() - $lastQuestion['solved']) < $pollParameters->timerSolvedQuestionWait) {
    die(toResultJson(' '));
  }
}

//
// Create new question
//

$newQuestion = drawNewQuestion($pollParameters->ownerId, $pollParameters->historyAvoidLastAnswers, $db);
if ($newQuestion === null) {
  die(toResultJson('Error! Could not find any question. Are your history parameters misconfigured?'));
}

// Handle the previous puzzle in case it was unsolved
$preface = ''; // TODO: Used to return the answer if it was unsolved. If we have allow multiple answers before resolving, we need to revise this.

// Save and return new puzzle
$newQuestionText = QuestionType::generateQuestionText($newQuestion, '.');
$response = connectTexts($newQuestionText, 'Answer with !a');
echo toResultJson(connectTexts($preface, $response));


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

function getLastQuestion(int $ownerId, DatabaseHandler $db): array|null {
  $questionValues = $db->getLastQuestion($ownerId);
  if ($questionValues) {
    $question = new Question($questionValues['type'], $questionValues['question'], $questionValues['answer']);
    return [
      'question' => $question,
      'created' => $questionValues['created'],
      'solved' => $questionValues['solved']
    ];
  }
  return null;
}

function drawNewQuestion(int $ownerId, int $skipPastQuestions, DatabaseHandler $db): Question|null {
  $questionValues = $db->drawNewQuestion($ownerId, $skipPastQuestions);
  if ($questionValues) {
    return new Question($questionValues['type'], $questionValues['question'], $questionValues['answer']);
  }
  return null;
}
