<?php

require '../Configuration.php';
require '../inc/constants.php';
require '../inc/Answer.php';
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

if ($settings->activeMode === 'OFF') {
  die(Utils::toResultJson(' '));
}

if (!isset($_GET['a'])) {
  die(Utils::toResultJson('Please provide a guess! Type ' . COMMAND_QUESTION . ' to see the text.'));
}

$questionService = new QuestionService($db);

try {
  $db->startTransaction();

  $currentQuestion = $questionService->getLastQuestionDraw($settings->ownerId);
  if ($currentQuestion === null) {
    die(Utils::toResultJson('Error: No question was asked so far!'));
  } else if ($currentQuestion->solved !== null) {
    die(Utils::toResultJson('The question was solved. Run ' . COMMAND_QUESTION . ' for a new question'));
  }

  $givenAnswer = filter_input(INPUT_GET, 'a', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR) ?? '';
  $givenAnswer = strtolower(Utils::unicodeTrim($givenAnswer));

  if (empty($givenAnswer)) {
    echo Utils::toResultJson('Please provide an answer!');
  } else {
    $questionType = QuestionType::getType($currentQuestion->question);
    $result = $questionType->processAnswer($currentQuestion->question, $givenAnswer);
    $db->saveLastAnswerTimestamp($currentQuestion->drawId);
    $user = Utils::extractUser();
    if (!$user) {
      echo Utils::toResultJson('Error: cannot get user from request');
    } else if ($result->invalidError !== null) {
      echo Utils::toResultJson($result->invalidError === Answer::INVALID_USE_DEFAULT_ERROR
          ? "@$user Invalid answer! Type " . COMMAND_QUESTION . " to see the question again"
          : "@$user " . $result->invalidError);
    } else {
      // MySQL reports 2 rows changed if the answer was updated, 1 if it's new
      $modifiedRows = $db->saveDrawAnswer($currentQuestion->drawId, $user, $result->answer);
      $responseText = $result->customResponse
                      ?? createEasterEggTextForSavedAnswer($user, $result)
                      ?? createTextForSavedAnswer($modifiedRows, $givenAnswer, $result);
      echo Utils::toResultJson(str_replace('%name%', $user, $responseText));
    }
  }

  $db->commit();
} catch (Exception $e) {
  $db->rollBackIfNeeded();
  throw $e;
}

function createTextForSavedAnswer(int $saveResponse, string $userAnswer, Answer $answer): string {
  $textAnswer = $answer->answerForText ?? $answer->answer;
  if ($saveResponse === 2) {
    return rand(0, 1) === 1
      ? "%name% is now guessing $textAnswer"
      : "%name% changed their guess to $textAnswer";
  } 

  if (rand(0, 1) === 1 && $userAnswer === strtolower($textAnswer)) {
    return "@%name% Got your guess, thanks!";
  } else {
    return "%name% guessed $textAnswer";
  }
}

// Easter egg responses fit better in Answer->customResponse so that they can be handled by each question type
// individually, but here we have some exceptions where we want to have user-specific responses. The question type
// doesn't know who the answer is from when evaluating a response.
function createEasterEggTextForSavedAnswer(string $user, Answer $answer): ?string {
  if (strtolower($user) === 'drakelingx' && ($answer->answer === 'zh' || $answer->answer === 'ja')) {
    $lang = $answer->answerForText;
    return Utils::getRandomText("$user knows this is $lang",
      "$user says this is $lang",
      "@$user Thanks! Now we all know the answer ;)",
      "$user informs chat that this is $lang");
  } else if (strtolower($user) === 'sonomedcam' && ($answer->answer === 'gd' || $answer->answer === 'ga')) {
    return Utils::getRandomText(
      "@$user Aye lad, got your guess! Thanks",
      "@$user Mòran taing airson do chuideachadh!",
      "@$user Got your guess—tapadh leat!");
  }
  return null;
}
