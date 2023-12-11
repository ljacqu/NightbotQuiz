<?php

require '../Configuration.php';
require '../inc/constants.php';
require '../inc/DatabaseHandler.php';
require '../inc/OwnerSettings.php';
require '../inc/SecretValidator.php';
require '../inc/Utils.php';

require '../inc/questiontype/QuestionType.php';

Utils::setJsonHeader();
$db = new DatabaseHandler();
$settings = SecretValidator::getOwnerSettingsOrExit($db);

if ($settings->activeMode === 'OFF') {
  die(Utils::toResultJson(' '));
}

$typeName = filter_input(INPUT_GET, 'questiontype', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
$typeName = Utils::unicodeTrim($typeName);

if (!$typeName) {
  die(Utils::toResultJson('No question type provided'));
}

try {
  $questionType = QuestionType::getTypeByName($typeName);
} catch (Exception $e) {
  die(Utils::toResultJson('Error: ' . $e->getMessage()));
}

$possibilities = $questionType->getAllPossibleAnswers();
echo json_encode(['result' => 'Providing all possibilities', 'possibilities' => $possibilities]);
