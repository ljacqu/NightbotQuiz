<?php

require '../conf/config.php';
require '../conf/Configuration.php';
require '../inc/UserSettings.php';
require '../inc/DatabaseHandler.php';
require '../inc/functions.php';
require '../inc/Question.php';
require '../inc/QuestionType.php';
require '../conf/question_types.php';

require '../owner/Updater.php';
require '../owner/medcam/MedUpdater.php';

$db = new DatabaseHandler();
$ownerInfo = getOwnerInfoForSecretOrThrow($db);
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
    $questionsByKey = [];
    foreach ($questions as $question) {
      $key = QuestionType::generateKey($question);
      if (isset($questionsByKey[$key])) {
        throw new Exception('Question "' . $question->question . '" with key ' . $key . ' is a duplicate!');
      }
      $questionsByKey[$key] = $question;
    }

    echo '<!-- Begin DB call -->';
    $updateInfo = $db->updateQuestions($ownerInfo['id'], $questionsByKey);

    echo '<b style="color: #090">✓ Success</b>: Saved a total of ' . count($questions) . ' questions :)<ul>
    <li>Updated ' . $updateInfo['updated'] . ' questions</li>
    <li>Deleted ' . $updateInfo['deleted'] . ' questions</li></ul>';
  }
}
?>
</body>
</html>