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

$query = filter_input(INPUT_GET, 'options', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

// [r]etain question draw even if it had zero answers
$retainEmptyDraw = strpos($query, 'r') !== false;
// [v]erbose output
$outputInfoInResult = strpos($query, 'v') !== false;

$questionService = new QuestionService($db);
try {
  $db->startTransaction();
  $lastDraw = $questionService->getLastQuestionDraw($settings->ownerId);
  if ($lastDraw === null) {
    echo Utils::toResultJson('No question has been chosen yet');
  } else {
    $resolutionText = $questionService->createResolutionText($lastDraw, true);

    if (empty($resolutionText) && !$retainEmptyDraw) {
      $db->deleteEmptyDraw($lastDraw->drawId);
      $info = 'Deleted last question (it had no answers).';
    } else if (empty($lastDraw->solved)) {
      $db->setCurrentDrawAsSolved($lastDraw->drawId);
      $info = 'Marked last question as solved.';
    } else {
      $resolutionText = '';
      $info = 'Last question did not have to be resolved/deleted.';
    }

    if ($outputInfoInResult) {
      echo Utils::toResultJson(Utils::connectTexts($resolutionText, $info));
    } else {
      $resolutionText = $resolutionText ?? ' '; // Nightbot does not like empty strings
      echo Utils::toResultJson($resolutionText, ['info' => $info]);
    }
  }
  $db->commit();
} catch (Exception $e) {
  $db->rollBackIfNeeded();
}
