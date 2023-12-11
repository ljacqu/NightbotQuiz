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
    $timeoutReasonToSkip = timerShouldBeSilent($lastDraw, $settings);
    if (!empty($timeoutReasonToSkip)) {
      $result = $settings->outputDebug() ? 'Debug: Skip because of ' . $timeoutReasonToSkip : ' ';
      return Utils::toResultJson($result, createAdditionalPropertiesForBot($botMessageHash, $lastDraw->question));
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
    return Utils::toResultJson($questionService->createResolutionText($lastDraw));
  }

  $questionType = QuestionType::getType($lastDraw->question);
  $questionText = $questionType->generateQuestionText($lastDraw->question);
  $questionService->saveLastQuestionQuery($settings->ownerId, $lastDraw->drawId);
  return Utils::toResultJson($questionText);
}

function timerShouldBeSilent(?QuestionDraw $lastDraw, OwnerSettings $settings): ?string {
  if ($lastDraw === null) {
    return null;
  }

  if (!empty($lastDraw->solved)) {
    return isWithinTimeoutPeriod($lastDraw->solved, $settings->timerSolvedQuestionWait)
      ? 'timerSolvedQuestionWait'
      : null;
  }

  if (isWithinTimeoutPeriod($lastDraw->created, $settings->timerUnsolvedQuestionWait)) {
    return 'timerUnsolvedQuestionWait';
  }
  if (isWithinTimeoutPeriod($lastDraw->lastQuestionQuery, $settings->timerLastQuestionQueryWait)) {
    return 'timerLastQuestionQueryWait';
  }
  if (isWithinTimeoutPeriod($lastDraw->lastAnswer, $settings->timerLastAnswerWait)) {
    return 'timerLastAnswerWait';
  }
  return null;
}

function isWithinTimeoutPeriod(?int $timestamp, int $timeout): bool {
  return !empty($timestamp) && (time() - $timestamp) <= $timeout;
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
        return Utils::toResultJson('Please wait ' . $secondsToWait . 's. Provide a guess for the current question with ' . COMMAND_ANSWER);
      }
    }
  }
  return null;
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
    $preface = $questionService->createResolutionText($lastDraw);
  }

  // Save and return new puzzle
  $questionType = QuestionType::getType($newQuestion);
  $newQuestionText = $questionType->generateQuestionText($newQuestion);
  $response = Utils::connectTexts($newQuestionText, 'Answer with ' . COMMAND_ANSWER);
  return Utils::toResultJson(Utils::connectTexts($preface, $response),
    createAdditionalPropertiesForBot($botMessageHash, $newQuestion));
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
    'hash' => $hash,
    'type' => $currentQuestion->questionType
  ];
}
