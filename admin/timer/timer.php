<?php

session_start();

require '../AdminHelper.php';
require '../OwnerNightbotInfo.php';
require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';
require '../../inc/Utils.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db, '../');
$nightbotInfo = AdminHelper::getOwnerNightbotInfo($db, $ownerInfo['id']);

// Deny this page if impersonating a user to avoid unintended changes to someone else's quiz
if (isset($_SESSION['impersonator'])) {
  AdminHelper::outputHtmlStart('Timer', $ownerInfo, '../');
  echo '<p class="crumbs"><a href="../index.php">Main</a> &lt; <a href="index.php">Timer</a> &lt; <b>Timer page</b></p>';
  echo '<h2>Timer denied</h2>You are currently impersonating a user! The timer has been blocked to avoid accidentally changing another user\'s quiz data';
  echo '</body></html>';
  exit;
}

$twitchName = $db->getTwitchName($ownerInfo['id']) ?? '';
$answerDisplay = empty($twitchName) ? ' display: none;' : '';
?>

<!DOCTYPE html>
<html>
<head>
  <title>Quiz - timer</title>
  <link rel="stylesheet" href="../admin.2.css" />
  <style>
  body {
    font-size: 12pt;
  }
  .error {
    color: #f00;
    background-color: #fcc;
  }
  #result {
    border: 1px solid #999;
    background-color: #ccc;
    padding: 5px;
    margin: 12px;
    display: inline-block;
    font-size: 12pt;
  }
  </style>
</head>
<body>
  <p class="crumbs"><a href="../">Main</a> &lt; <a href="index.php">Timer</a> &lt; <b>Timer page</b></p>
  <h2>Quiz timer</h2>
  Last message:
  <div id="result"><span style="color: #333; font-style: italic; font-size: 0.9em">No response with text received yet</span></div>
  <div>Last request: <span id="time"></span></div>
  <div id="pollerror" class="error" style="display: none">Error during last call: <span id="pollerrormsg"></span> </div>
  <div>Last Nightbot message: <span id="msg"></span></div>

  <div id="time-elapsed-error" class="error" style="display: none; padding: 30px">
    <b>The timer has been stopped automatically.</b> It has been running for more than 6 hours.
    Please <a href="timer.php">reload the page</a> if you want it to continue.
  </div>

  <div style="margin-top: 1em">
     <input type="checkbox" checked="checked" name="pause" id="pause" onchange="quizTimer.togglePause();" /> <label for="pause">Pause timer (press P)</label>
  </div>

  <div>
    <button class="action" style="background-color: #9cf; font-size: 1.1em" onclick="quizTimer.callPollFile('');" title="Runs !q and sends the result to Nightbot">
        Show question again
    </button>
    <button class="action" style="background-color: #9c3; font-size: 1.1em" onclick="quizTimer.callPollFile('silentnew');" title="Runs !q silentnew and sends the result to Nightbot">
        Force new question
    </button>
  </div>

  <div id="answerresponse" style="margin-left: 0.5em; padding-top: 1em; <?= $answerDisplay ?>">&nbsp;</div>
  <div id="answerbuttons" style="padding-top: 0.1em"></div>

  <div>
    <button class="action" style="background-color: #f66; font-size: 1.1em" onclick="quizTimer.stop();" title="Resolves/deletes the last question and stops the timer">Stop timer</button>
    <span id="solvehelp">
      &nbsp; Stops the timer, solves the last question and
      <input type="checkbox" id="solvedeleteifempty" checked="checked" class="smart-checkbox" data-text-id="solvedeletelabel" />
        <label for="solvedeleteifempty" id="solvedeletelabel">deletes it if had no answers</label>
    </span>
    <span id="solveresult"></span>
    <span id="solveerror" class="error"></span>
  </div>

  <?php
  if (isTokenExpired($nightbotInfo)) {
    echo '<h2>No Nightbot token</h2>
      <div class="error" style="padding: 9px">No valid Nightbot token has been found!
      Please go to <a href="obtain_token.php">obtain token</a> to generate a new one.</div>';
  }

  $apiSecret = $db->getOwnerSecret($ownerInfo['id']);
  echo <<<HTML
  <script src="timer.3.js"></script>
  <script>
    const secret = '$apiSecret';
    const twitchName = '$twitchName';
    initializeTimer(secret, twitchName);
  </script>
  <script src="checkbox_handler.js"></script>
</body>
</html>
HTML;

function isTokenExpired(OwnerNightbotInfo $nightbotInfo): bool {
  return empty($nightbotInfo->tokenExpires) || time() > $nightbotInfo->tokenExpires;
}
