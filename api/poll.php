<?php

require '../Configuration.php';
require '../inc/constants.php';
require '../inc/DatabaseHandler.php';
require '../inc/OwnerSettings.php';
require '../inc/Question.php';
require '../inc/QuestionDraw.php';
require '../inc/QuestionService.php';
require '../inc/SecretValidator.php';
require '../inc/Utils.php';

require '../inc/questiontype/QuestionType.php';

Utils::setJsonHeader();
$db = new DatabaseHandler();
$settings = SecretValidator::getOwnerSettingsOrExit($db);

$variant = filter_input(INPUT_GET, 'variant', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if ($settings->activeMode === 'OFF') {
  die(Utils::toResultJson(' '));
} else if ($variant === 'timer' && $settings->activeMode !== 'ON') {
  die(Utils::toResultJson(' '));
}


//
// Check if current question is still unsolved, and whether a new question should be created.
//

$questionService = new QuestionService($db);

try {
  $db->startTransaction();

  $lastDraw = $questionService->getLastQuestionDraw($settings->ownerId);


  if ($lastDraw !== null && empty($lastDraw->solved)) {
    if ($variant === 'timer') {
      $timeSinceLastDraw = time() - $lastDraw->created;
      if ($timeSinceLastDraw < $settings->timerUnsolvedQuestionWait) {
        // Nightbot doesn't accept empty strings, but seems to trim responses and
        // not show anything if there are only spaces, so make sure to have a space in the response.
        die(Utils::toResultJson(' '));
      } else {
        $lastAnswer = $lastDraw->lastAnswer ?? 0;
        if (time() - $lastAnswer < $settings->timerLastAnswerWait) {
          die(Utils::toResultJson(' '));
        }
      }
    } else if ($variant === 'new') {
      $timeSinceLastDraw = time() - $lastDraw->created;
      if ($timeSinceLastDraw < $settings->userNewWait) {
        $secondsToWait = $settings->userNewWait - $timeSinceLastDraw;
        die(Utils::toResultJson('Please solve the current question, or wait ' . $secondsToWait . 's'));
      }
    } else {
      $questionType = QuestionType::getType($lastDraw->question->questionTypeId);
      $questionText = $questionType->generateQuestionText($lastDraw->question);
      die(Utils::toResultJson($questionText));
    }
  } else if ($variant === 'timer' && $lastDraw !== null) {
    // The first `if` is triggered if there is a last unsolved question; being here means the
    // last question exists, and it was solved
    if ((time() - $lastDraw->solved) < $settings->timerSolvedQuestionWait) {
      die(Utils::toResultJson(' '));
    }
  }

  //
  // Create new question
  //

  $newQuestion = $questionService->drawNewQuestion($settings->ownerId, $settings->historyAvoidLastAnswers);
  if ($newQuestion === null) {
    die(Utils::toResultJson('Error! Could not find any question. Are your history parameters misconfigured?'));
  }

  // Handle the previous puzzle in case it was unsolved
  $preface = ''; // TODO: Used to return the answer if it was unsolved. If we have allow multiple answers before resolving, we need to revise this.

  // Save and return new puzzle
  $questionType = QuestionType::getType($lastDraw->question->questionTypeId);
  $newQuestionText = $questionType->generateQuestionText($newQuestion);
  $response = connectTexts($newQuestionText, 'Answer with !a');
  echo Utils::toResultJson(connectTexts($preface, $response));

  $db->commit();
} catch (Exception $e) {
  $db->rollBackIfNeeded();
  throw $e;
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
