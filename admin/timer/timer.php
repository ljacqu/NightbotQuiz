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

$timerValues = $db->getValuesForTimerPage($ownerInfo['id']) ?? '';
$answerDisplay = empty($timerValues['twitch_name']) ? ' display: none;' : '';

switch ($ownerInfo['active_mode']) {
  case 'ON':
    $activeModeText = 'On';
    break;
  case 'OFF':
    $activeModeText = 'Off';
    break;
  case 'USER_ONLY':
    $activeModeText = 'User only (silent timer)';
    break;
  default:
    throw new Exception("Unexpected active mode: " . $ownerInfo['active_mode']);
}
$activeModeWarningDisplay = $activeModeText === 'On' ? 'display: none;' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Quiz - timer</title>
  <link rel="stylesheet" href="../admin.css?acx=2" />
  <?php
  if ($_SERVER['HTTP_HOST'] !== 'localhost') {
    echo '<link rel="icon" href="../../indexpage/favicon.ico" />';
  }
  ?>
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
  <div id="quiz-settings-off" style="border: 1px solid #f00; padding: 1em; margin: 1em; <?php echo $activeModeWarningDisplay ?>">
    The quiz activity is currently set to <b><?php echo $activeModeText ?></b>—the timer will not have any effect.
    <button id="quiz-activity-on-btn">Turn quiz on</button> <a href="../settings.php">Go to settings</a>
    <span id="quiz-activity-on-error"></span>
  </div>
  <div id="countdown-section" style="display: none">
    <div id="cd-seconds-param-section">
      <label for="cd-seconds-param">Start quiz after</label>
      <input id="cd-seconds-param" type="text" min="0" max="900" style="width: 5ch" value="<?php echo $timerValues['timer_countdown_seconds']; ?>" /> seconds
    </div>

    <div id="countdown-display" style="display: none; margin-left: 3em">
      <span style="font-size: 2em">Starting quiz in
        <span id="countdown-time" style="color: #090"></span>
      </span>
      <br /><br /><a href="#">Cancel</a>
    </div>

    <button class="action" id="cd-start-btn" style="background-color: #af7; font-size: 1.1em" title="Count down the number of seconds, then start the quiz">
      Start
    </button>
    <button class="action" id="cd-start-directly-btn" style="background-color: #ff7" title="Ignores the countdown and start the quiz immediately">
      Start quiz now
    </button>
    <button class="action" id="cd-start-paused-btn" style="background-color: #f97" title="Shows the timer page; the quiz is paused">
      See quiz paused
    </button>
    <p style="font-size: 0.9em">
      Keyboard shortcuts: <b>C</b> (or Enter) starts the countdown, <b>P</b> switches to the quiz in paused mode.
      <br />Don't want this countdown page? Set the countdown time to 0 and press "Start" to not see this again
            (you will directly see the quiz page in the future).
    </p>
  </div>

  <div id="timer-controls-section" style="display: none">
    Last message:
    <div id="result"><span style="color: #333; font-style: italic; font-size: 0.9em">No response with text received yet</span></div>
    <div>Last request: <span id="time"></span></div>
    <div id="pollerror" class="error" style="display: none">Error during last call: <span id="pollerrormsg"></span></div>
    <div>Last Nightbot response: <span id="msg"></span></div>

    <div id="time-elapsed-error" class="error" style="display: none; padding: 30px">
      <b>The timer has been stopped automatically.</b> It has been running for more than 6 hours.
      Please <a href="timer.php">reload the page</a> if you want it to continue.
    </div>

    <div style="margin-top: 1em">
       <input type="checkbox" checked="checked" name="pause" id="pause" /> <label for="pause">Pause timer (press P)</label>
    </div>
    <div>
      <input type="checkbox" id="stop-after-question" />
      <label for="stop-after-question" title="This timer will stop when the current question is solved">Stop after the current question (press S)</label>
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
      <button class="action" style="background-color: #f66" id="stop-directly-btn" title="Resolves/deletes the last question and stops the timer immediately">Stop timer now</button>
      <span id="solvehelp">
        &nbsp; Stops the timer, solves the last question and
        <input type="checkbox" id="solvedeleteifempty" checked="checked" class="smart-checkbox" data-text-id="solvedeletelabel" />
          <label for="solvedeleteifempty" id="solvedeletelabel">deletes it if had no answers</label>
      </span>
      <span id="solveresult"></span>
      <span id="solveerror" class="error"></span>
    </div>
    <div id="turn-quiz-off-section" style="display: none; margin-top: 2em">
      <p style="margin-left: 0.5em">The quiz has been stopped.
        <a href="?">Reload</a> &middot; <a href="../index.php">Main</a> &middot; <a href="../settings.php">Settings</a>
      </p>
      <button id="quiz-activity-off-btn" style="background-color: #fcc; margin-top: 0.2em" class="action">Turn quiz activity off</button>
      <span id="quiz-activity-off-result"></span>
    </div>
  </div>

  <?php
  if (isTokenExpired($nightbotInfo)) {
    echo '<h2>No Nightbot token</h2>
      <div class="error" style="padding: 9px">No valid Nightbot token has been found!
      Please go to <a href="obtain_token.php">obtain token</a> to generate a new one.</div>';
  }

  $apiSecret = $db->getOwnerSecret($ownerInfo['id']);
  echo <<<HTML
  <script src="timer.js?acx=2"></script>
  <script>
    quizTimer.secret = '$apiSecret';
    quizTimer.twitchName = '{$timerValues['twitch_name']}';
  </script>
  <script src="timer_countdown.js?acx=2"></script>
  <script src="checkbox_handler.js?acx=2"></script>
  <script src="quiz_activity_helper.js?acx=2"></script>
</body>
</html>
HTML;

function isTokenExpired(OwnerNightbotInfo $nightbotInfo): bool {
  return empty($nightbotInfo->tokenExpires) || time() > $nightbotInfo->tokenExpires;
}
