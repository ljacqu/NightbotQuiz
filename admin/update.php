<?php

require '../Configuration.php';
require '../inc/constants.php';
require '../inc/DatabaseHandler.php';
require '../inc/Question.php';
require '../inc/QuestionValues.php';
require '../inc/SecretValidator.php';
require '../inc/Utils.php';

require '../owner/Updater.php';
require '../inc/questiontype/QuestionType.php';

$db = new DatabaseHandler();
$ownerInfo = SecretValidator::getOwnerInfoForSecretOrExit($db);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Nightbot quiz updater</title>
  <style>
body {
  font-family: Arial;
  font-size: 10pt;
  margin: 20px;
}
h2 {
  color: #c30;
  margin-bottom: 0;
  margin-top: 1.25em;
}
div.link {
  font-size: 0.8em;
  color: #666;
  font-family: Consolas, monospace;
  margin-bottom: 1em;
}
.lastquestion {
  font-style: italic;
}
  </style>
</head>

<?php
echo '<body><h1>Nightbot quiz update</h1>';

if (!isset($_POST['update'])) {
  echo '<h2>Questions update</h2>
    Press the button below to update the quiz\'s data.
    <br />Note that if questions are rephrased or deleted, the answering history of that question will be lost.
    <form method="post">
     <input type="hidden" name="update" value="go" />
     <br /><input type="submit" value="Update" />
    </form>';
} else {
  $updater = Updater::of($ownerInfo['name']);
  $questions = $updater->generateQuestions();

  if (empty($questions)) {
    echo 'Error! No questions could be generated';

  } else {
    echo '<h2>Saving questions</h2>';
    $questionValues = createQuestionValues($questions);

    echo '<!-- Begin DB call -->';

    try {
      $db->startTransaction();
      $updateInfo = $db->updateQuestions($ownerInfo['id'], $questionValues);
      $db->commit();
    } catch (Exception $e) {
      $db->rollBackIfNeeded();
      throw $e;
    }

    echo '<b style="color: #090">✓ Success</b>: Saved a total of ' . count($questions) . ' questions :)<ul>
    <li>Updated ' . $updateInfo['updated'] . ' questions</li>
    <li>Deleted ' . $updateInfo['deleted'] . ' questions</li></ul>';
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
