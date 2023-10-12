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
$variant = Utils::unicodeTrim($variant);
if ($settings->activeMode === 'OFF') {
  die(Utils::toResultJson(' '));
} else if ($variant === 'timer' && $settings->activeMode !== 'ON') {
  die(Utils::toResultJson(' '));
}

$botMessageHash = filter_input(INPUT_GET, 'hash', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

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
        die(Utils::toResultJson(' ', createAdditionalPropertiesForBot($botMessageHash, $lastDraw->question)));
      } else {
        $lastAnswer = $lastDraw->lastAnswer ?? 0;
        if (time() - $lastAnswer < $settings->timerLastAnswerWait) {
          die(Utils::toResultJson(' ', createAdditionalPropertiesForBot($botMessageHash, $lastDraw->question)));
        }
      }
    } else if ($variant === 'new' || $variant === 'silentnew') {
      $timeSinceLastDraw = time() - $lastDraw->created;
      if ($timeSinceLastDraw < $settings->userNewWait) {
        if ($variant === 'silentnew') {
          die(Utils::toResultJson(' ', createAdditionalPropertiesForBot($botMessageHash, $lastDraw->question)));
        } else {
          $secondsToWait = $settings->userNewWait - $timeSinceLastDraw;
          die(Utils::toResultJson('Please solve the current question, or wait ' . $secondsToWait . 's'));
        }
      }
    } else {
      $questionType = QuestionType::getType($lastDraw->question);
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

  // Preface the result with the previous question's answer if it was unsolved
  $preface = '';
  if ($lastDraw !== null && $lastDraw->solved === null) {
    $questionType = QuestionType::getType($lastDraw->question);
    $preface = $questionType->generateResolutionText($lastDraw->question);
  }

  // Save and return new puzzle
  $questionType = QuestionType::getType($newQuestion);
  $newQuestionText = $questionType->generateQuestionText($newQuestion);
  $response = connectTexts($newQuestionText, 'Answer with ' . COMMAND_ANSWER);
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

function createAdditionalPropertiesForBot(?string $botMsgHash, Question $currentQuestion): array {
  if (!$botMsgHash) {
    return [];
  }

  $type = QuestionType::getType($currentQuestion);
  $hash = $type->generateKey($currentQuestion);
  if ($hash === $botMsgHash) {
    return [];
  }
  return [
    'info' => $type->generateQuestionText($currentQuestion),
    'hash' => $hash
  ];
}
