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
  echo executePollRequest($variant, $botMessageHash, $questionService, $settings);
  $db->commit();
} catch (Exception $e) {
  $db->rollBackIfNeeded();
  throw $e;
}

function executePollRequest(?string $variant, ?string $botMessageHash,
                            QuestionService $questionService, OwnerSettings $settings): string {
  $lastDraw = $questionService->getLastQuestionDraw($settings->ownerId);
  $newQuestionRequested = false;

  if ($variant === 'timer') {
    if (timerShouldBeSilent($lastDraw, $settings)) {
      return Utils::toResultJson(' ', createAdditionalPropertiesForBot($botMessageHash, $lastDraw->question));
    }
    $newQuestionRequested = $settings->timerSolveCreatesNewQuestion;
  } else if ($variant === 'new' || $variant === 'silentnew') {
    $error = createErrorForNewVariantsIfNeeded($botMessageHash, $variant, $lastDraw, $settings);
    if ($error) {
      return $error;
    }
    $newQuestionRequested = true;
  }

  if ($newQuestionRequested || $lastDraw === null || !empty($lastDraw->solved)) {
    return drawNewQuestion($lastDraw, $botMessageHash, $questionService, $settings);
  } else if ($variant === 'timer' && empty($lastDraw->solved)) {
    $questionService->setCurrentDrawAsResolved($lastDraw->drawId);
    return Utils::toResultJson(createResolutionText($lastDraw, $questionService));
  }

  $questionType = QuestionType::getType($lastDraw->question);
  $questionText = $questionType->generateQuestionText($lastDraw->question);
  return Utils::toResultJson($questionText);
}

function timerShouldBeSilent(?QuestionDraw $lastDraw, OwnerSettings $settings): bool {
  if ($lastDraw === null) {
    return false;
  }

  if (!empty($lastDraw->solved)) {
    return (time() - $lastDraw->solved) < $settings->timerSolvedQuestionWait;
  }

  $timeSinceLastDraw = time() - $lastDraw->created;
  if ($timeSinceLastDraw < $settings->timerUnsolvedQuestionWait) {
    return true;
  }
  $lastAnswer = $lastDraw->lastAnswer ?? 0;
  return (time() - $lastAnswer < $settings->timerLastAnswerWait);
}

function createErrorForNewVariantsIfNeeded(?string $botMessageHash, string $variant,
                                           ?QuestionDraw $lastDraw, OwnerSettings $settings): ?string {
  if ($lastDraw !== null) {
    $timeSinceLastDraw = time() - $lastDraw->created;
    if ($timeSinceLastDraw < $settings->userNewWait) {
      if ($variant === 'silentnew') {
        return Utils::toResultJson(' ', createAdditionalPropertiesForBot($botMessageHash, $lastDraw->question));
      } else {
        $secondsToWait = $settings->userNewWait - $timeSinceLastDraw;
        return Utils::toResultJson('Please solve the current question, or wait ' . $secondsToWait . 's');
      }
    }
  }
  return null;
}

function createResolutionText(QuestionDraw $lastDraw, QuestionService $questionService): string {
  $questionType = QuestionType::getType($lastDraw->question);
  $solutionText = $questionType->generateResolutionText($lastDraw->question);

  $stats = $questionService->getCorrectAnswers($lastDraw);
  $statText = '';
  if ($stats['total'] > 0) {
    if ($stats['total_correct'] == 0) {
      $statText = 'No one guessed the right answer.';
    } else if ($stats['total_correct'] == 1) {
      $statText = 'One person guessed it right!';
    } else {
      $statText = 'Correct guesses: ' . $stats['total_correct'] . '/' . $stats['total'] . '.';
    }
  }

  return connectTexts($solutionText, $statText);
}

function drawNewQuestion(?QuestionDraw $lastDraw, ?string $botMessageHash, QuestionService $questionService,
                         OwnerSettings $settings): string {
  $newQuestion = $questionService->drawNewQuestion($settings->ownerId, $settings->historyAvoidLastAnswers);
  if ($newQuestion === null) {
    return Utils::toResultJson('Error! Could not find any question. Are your history parameters misconfigured?');
  }

  // Preface the result with the previous question's answer if it was unsolved
  $preface = '';
  if ($lastDraw !== null && $lastDraw->solved === null) {
    $preface = createResolutionText($lastDraw, $questionService);
  }

  // Save and return new puzzle
  $questionType = QuestionType::getType($newQuestion);
  $newQuestionText = $questionType->generateQuestionText($newQuestion);
  $response = connectTexts($newQuestionText, 'Answer with ' . COMMAND_ANSWER);
  return Utils::toResultJson(connectTexts($preface, $response),
    createAdditionalPropertiesForBot($botMessageHash, $newQuestion));
}

function connectTexts($text1, $text2) {
  if (empty($text1)) {
    return $text2;
  } else if (empty($text2)) {
    return $text1;
  }

  $lastCharacter = mb_substr(trim($text1), -1, 1, 'UTF-8');
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
