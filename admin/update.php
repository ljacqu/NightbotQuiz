<?php

session_start();

require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/constants.php';
require '../inc/DatabaseHandler.php';
require '../inc/OwnerSettings.php';
require '../inc/Question.php';
require '../inc/QuestionValues.php';
require '../inc/SecretValidator.php';
require '../inc/Utils.php';

require '../owner/Updater.php';
require '../inc/questiontype/QuestionType.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db);
AdminHelper::outputHtmlStart('Nightbot quiz update', $ownerInfo);
echo '<p class="crumbs"><a href="index.php">Main</a> &lt; <b>Update</b></p>';

$settings = OwnerSettings::createFromDbRow($db->getSettingsByOwnerId($ownerInfo['id']));

if (!isset($_POST['update'])) {
  echo '<h2>Questions update</h2>
    <p>
    Press the button below to update the quiz\'s data.
    <br />Note that if questions are rephrased or deleted, the answering history of that question will be lost.</p>
    <form method="post">
     <input type="hidden" name="update" value="go" />
     <input type="submit" value="Update" class="action" />
    </form>';

  $stats = $db->getConfigurableStatFields($ownerInfo['id']);
  if (!empty($stats['data_url'])) {
    $dataUrlEsc = htmlspecialchars($stats['data_url'], ENT_QUOTES);
    echo '<h2 style="margin-top: 2em">Question data</h2>
      <a href="' . $dataUrlEsc . '" target="_blank">' . $dataUrlEsc . '</a>';
  }
} else {
  $updater = Updater::of($settings->ownerName);
  $questions = $updater->generateQuestions();

  if (empty($questions)) {
    echo 'Error! No questions could be generated';

  } else {
    echo '<h2>Saving questions</h2>';
    $questionValues = createQuestionValues($questions);

    echo '<!-- Begin DB call -->';

    try {
      $db->startTransaction();
      $updateInfo = $db->updateQuestions($settings->ownerId, $questionValues);
      $db->commit();
    } catch (Exception $e) {
      $db->rollBackIfNeeded();
      throw $e;
    }

    echo '<b style="color: #090">✓ Success</b>: Saved a total of ' . count($questions) . ' questions :)<ul>
    <li>Updated ' . $updateInfo['updated'] . ' questions</li>
    <li>Deleted ' . $updateInfo['deleted'] . ' questions</li></ul>';

    if (!$db->hasQuestionCategoriesOrMore($settings->ownerId, $settings->historyAvoidLastAnswers)) {
      echo '<h2>Warning</h2>';
      echo 'Your history settings define to avoid the last ' . $settings->historyAvoidLastAnswers . ', but there are fewer questions! Please adjust the value in the settings.';
    }
  }
}

/**
 * Creates QuestionValues objects which fully define a question for persistence. Validates that all questions
 * have a unique key.
 * 
 * @param Question[] $questions the questions to transform 
 * @return QuestionValues[] fully defined questions for persisting
 */
function createQuestionValues(array $questions): array {
  $questionsByKey = [];
  $questionValues = [];

  foreach ($questions as $question) {
    $questionType = QuestionType::getType($question);

    $key = $questionType->generateKey($question);
    $category = $questionType->generateCategory($question);

    if (isset($questionsByKey[$key])) {
      throw new Exception('Question "' . $question->question . '" with key ' . $key . ' is a duplicate!');
    }
    $questionsByKey[$key] = true;
    $questionValues[] = new QuestionValues($question, $key, $category);
  }
  return $questionValues;
}
?>
</body>
</html>
