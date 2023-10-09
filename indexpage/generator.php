<?php
if (!isset($ownerInfo) && !isset($_GET['secret'])) {
  die('No owner ID present - exiting');
}

require __DIR__ . '/../Configuration.php';
require __DIR__ . '/../inc/constants.php';
require __DIR__ . '/../inc/DatabaseHandler.php';
require __DIR__ . '/../inc/SecretValidator.php';
require __DIR__ . '/../inc/Utils.php';
require __DIR__ . '/../inc/Question.php';

require __DIR__ . '/../owner/HtmlPageGenerator.php';
require __DIR__ . '/../inc/questiontype/QuestionType.php';

$db = new DatabaseHandler();
if (isset($_GET['secret'])) {
  if (isset($ownerId)) {
    echo '<!-- owner ID is set -->';
  } else {
    $ownerInfo = SecretValidator::getOwnerInfoForSecretOrExit($db);
    $ownerId = $ownerInfo['id'];
  }
}


$htmlPageGenerator = HtmlPageGenerator::of($ownerInfo['name'], $ownerInfo['id'], $db);
$title     = $htmlPageGenerator->getPageTitle();
$preface   = $htmlPageGenerator->generatePreface();
$questions = $htmlPageGenerator->generateQuestionsTable(5);
$appendix  = $htmlPageGenerator->generateAppendix();

$template = file_get_contents(__DIR__ . '/template.html');

echo str_replace(
  ['{title}', '{preface}', '{questions}', '{appendix}'],
  [ $title,    $preface,    $questions,    $appendix],
  $template);
