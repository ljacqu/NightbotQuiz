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

$showHighScore = isset($_GET['highscore']);

$htmlPageGenerator = HtmlPageGenerator::of($owner, $pageParams['id'], $db);
$title     = $htmlPageGenerator->getPageTitle();
$preface   = $htmlPageGenerator->generatePreface();
$questions = $showHighScore
  ? $htmlPageGenerator->generateHighScoreTable($pageParams['high_score_days'])
  : $htmlPageGenerator->generateQuestionsTable($pageParams, getUsersToShow());
$appendix  = $htmlPageGenerator->generateAppendix();

$template = file_get_contents(__DIR__ . '/template.html');

echo str_replace(
  ['{title}', '{preface}', '{questions}', '{appendix}'],
  [ $title,    $preface,    $questions,    $appendix],
  $template);

function getUsersToShow(): array {
  $usersQuery = filter_input(INPUT_GET, 'users', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  if ($usersQuery) {
    $result = [];
    foreach (explode(',', $usersQuery) as $user) {
      if (!empty($user)) {
        $result[$user] = true;
      }
    }
    return array_keys($result);
  }
  return [];
}
