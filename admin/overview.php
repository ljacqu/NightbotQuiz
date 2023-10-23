<?php

session_start();

require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/constants.php';
require '../inc/DatabaseHandler.php';
require '../inc/Question.php';
require '../inc/QuestionDraw.php';
require '../inc/QuestionService.php';

require '../inc/questiontype/QuestionType.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db);

AdminHelper::outputHtmlStart('Overview', $ownerInfo);
?>
<p class="crumbs"><a href="index.php">Main</a> &lt; <b>Overview</b></p>
<h2>Overview</h2>
<p>This page gives you a small overview about your quiz data. Parameters can be configured in <a href="settings.php">settings</a>.</p>

<?php
$questionService = new QuestionService($db);
$draw = $questionService->getLastQuestionDraw($ownerInfo['id']);
if ($draw === null || !empty($draw->solved)) {
  $drawText = 'none';
} else {
  $questionType = QuestionType::getType($draw->question);
  $drawText = '<span style="color: #333; font-size: 0.9em; font-style: italic">' . htmlspecialchars($questionType->generateQuestionText($draw->question)) . '</span>';
}

$overviewInfo = $db->getOwnerInfoForOverviewPage($ownerInfo['id']);
switch ($overviewInfo['active_mode']) {
  case 'ON':
    $modeText = '<span style="color: #090">On</span>';
    break;
  case 'USER_ONLY':
    $modeText = '<span style="color: #770" title="!q and !a work, but !q timer is silent">User only (silent timer)</span>';
    break;
  case 'OFF':
    $modeText = '<span style="color: #900" title="All commands are silent">Off</span>';
    break;
  default:
    $modeText = htmlspecialchars($overviewInfo['active_mode']);
}

$questionStats = $db->getQuestionStatsForOwner($ownerInfo['id']);
if ($questionStats['sum_categories'] === $questionStats['sum_questions']) {
  $sumCategoriesElem = '';
} else {
  $sumCategoriesElem = '<li>Total question categories: ' . $questionStats['sum_categories'] . '</li>';
}

echo <<<HTML
<ul>
  <li><span title="Displays the current unsolved question">Current question</span>: $drawText</li>
  <li>Quiz activity: <b>$modeText</b></li>
  </ul>
  <b>Question data</b>
  <ul>
  <li>Total questions: {$questionStats['sum_questions']}</li>
  $sumCategoriesElem
  <li>Total draws: {$questionStats['sum_draws']}</li>
  <li>Total answers: {$questionStats['sum_draws']}</li>
</ul>
HTML;

echo '<h2>Commands</h2>';

if (isset($_GET['cmd'])) {
  $secret = $db->getOwnerSecret($ownerInfo['id']);
  echo '<p>Set up your <a href="https://nightbot.tv/commands/custom">Nightbot commands</a> with the texts as given below.
    <br />These commands include your API secret—do not share this with anyone! (Except for Nightbot :))</p>';
  echo '<p>Click on the boxes to copy the text.</p>';

  echo '<p><b>Command for ' . COMMAND_QUESTION . '</b></p>';
  $pollUrl = buildApiFolderLink() . "poll.php?secret=$secret&variant=" . '$(querystring)';
  echo "<div class='command' style='background-color: #ffe9cc; padding: 1em' onclick='selectAndCopyText(this);'>
    $(eval const api = $(urlfetch json $pollUrl); api.result)</div>";

  echo '<p><b>Command for ' . COMMAND_ANSWER . '</b></p>';
  $answerUrl = buildApiFolderLink() . "answer.php?secret=$secret&a=" . '$(querystring)';
  echo "<div class='command' style='background-color: #ffe9cc; padding: 1em' onclick='selectAndCopyText(this);'>
    $(eval const api = $(urlfetch json $answerUrl); api.result)</div>";

  echo '<p><b>Command for !solve</b></p>';
  $solveUrl = buildApiFolderLink() . "solve.php?secret=$secret&options=" . '$(querystring)';
  echo "<div class='command' style='background-color: #ffe9cc; padding: 1em' onclick='selectAndCopyText(this);'>
    $(eval const api = $(urlfetch json $solveUrl); api.result)</div>";
  echo '<p>!solve is an optional command you can add to solve/delete the current question to clean things up at the end of the stream—set it to be <b>available for mods only</b>.
           Note that the timer page provided by this app also has a button for this command, which might be better suited.
           <br />By default, this command deletes the current question silently if it had zero answers; you can choose to retain the current question
            by doing <code>!solve r</code>.  If the last question has answers, the question is solved and the results are returned to chat. If !solve 
            deletes the question or there is nothing to do (e.g. question is already solved), it does not respond with anything as to minimize your
            chat\'s disruption. To receive an answer in all cases, use <code>!solve v</code> (for "verbose"). Combine both flags with <code>!solve rv</code></p>';
} else {
  echo '<p>This section shows you how to set up your commands for Nightbot. It includes your API secret, which you should not share with anyone else!
        <br /><a href="?cmd">Show command setup</a></p>';
}

// If client ID is not set up, token is irrelevant
if (!empty($overviewInfo['client_id'])) {
  echo '<h2>Nightbot token</h2><p>';
  if (empty($overviewInfo['token_expires'])) {
    echo 'No token for sending messages has been saved.';
  } else {
    $expiryDate = date('Y-m-d, H:i', $overviewInfo['token_expires']);
    if (time() < $overviewInfo['token_expires']) {
      echo '<b style="color: green">&check;</b> A valid token for sending messages to Nightbot is stored. It will expire on ' . $expiryDate . '.';
    } else {
      echo 'The token for Nightbot has <b>expired</b> on ' . $expiryDate . '. You can obtain a new one in the timer settings.';
    }
  }

  echo '</p>
    <p>See <a href="./timer/">timer settings</a> for details.</p>';
}

echo '<script src="selecttext.js"></script>';

function buildApiFolderLink(): string {
  $link = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  return preg_replace('~/admin/\\w+\.php$~', '/api/', $link);
}
?>
</body>
</html>
