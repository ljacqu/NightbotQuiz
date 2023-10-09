<?php
if (!isset($owner) || !is_string($owner)) {
  // Include this page from another PHP page with $owner set to the owner name
  die('Error: No owner name was provided');
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
$pageParams = $db->getIndexPageSettingsForOwner($owner);
if ($pageParams === null) {
  throw new Exception('Unknown owner "' . $owner . '"');
}

$htmlPageGenerator = HtmlPageGenerator::of($owner, $pageParams['id'], $db);
$title     = $htmlPageGenerator->getPageTitle();
$preface   = $htmlPageGenerator->generatePreface();
$questions = $htmlPageGenerator->generateQuestionsTable($pageParams['history_display_entries']);
$appendix  = $htmlPageGenerator->generateAppendix();

$template = file_get_contents(__DIR__ . '/template.html');

echo str_replace(
  ['{title}', '{preface}', '{questions}', '{appendix}'],
  [ $title,    $preface,    $questions,    $appendix],
  $template);
