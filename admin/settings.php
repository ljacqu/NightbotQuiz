<?php

session_start();

require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';
require '../inc/OwnerSettings.php';
require '../inc/Utils.php';

$db = new DatabaseHandler();
$ownerInfo = Adminhelper::getOwnerInfoOrRedirect($db);

AdminHelper::outputHtmlStart('Quiz settings', $ownerInfo);
echo '<p class="crumbs"><a href="index.php">Main</a> &lt; <b>Settings</b></p>
  <h2>Quiz settings</h2>';

$settings = OwnerSettings::createFromDbRow($db->getSettingsByOwnerId($_SESSION['owner']));
$activeOptions = [
  'ON' => 'On',
  'USER_ONLY' => 'User only (!q timer is disabled)',
  'OFF' => 'Off'
];

// Array keys must correspond to the field name in OwnerSettings
$timeouts = [
  'timerUnsolvedQuestionWait' => [
    'text' => 'Timer: unsolved question wait',
    'help' => 'If there is an unsolved question, how many seconds since its creation should !q timer wait before resolving it?',
  ],
  'timerSolvedQuestionWait' => [
    'text' => 'Timer: solved question wait',
    'help' => 'When !q timer is run, how many seconds since a question was _solved_ should be waited before a new question is generated?',
  ],
  'timerLastAnswerWait' => [
    'text' => 'Timer: wait after last answer',
    'help' => 'How many seconds to wait since the last answer of an unsolved question before !q timer solves the question?',
  ],
  'timerLastQuestionQueryWait' => [
    'text' => 'Timer: wait after someone ran !q',
    'help' => 'How many seconds since someone last ran !q should we wait before repeating the question, or creating a new one?'
  ],
  'userNewWait' => [
    'text' => 'Seconds to wait for !q new',
    'help' => 'If a user does !q new, how many seconds since the last question\'s creation must have elapsed?',
  ]
];

$active = filter_input(INPUT_POST, 'active', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if ($active !== null && isset($activeOptions[$active])) {

  $error = null;
  do {
    $oldActiveValue = $settings->activeMode;
    $settings->activeMode = $active;
    $settings->timerSolveCreatesNewQuestion = !!(filter_input(INPUT_POST, 'timerSolveCreatesQuestion', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR));
    $settings->debugMode = !!(filter_input(INPUT_POST, 'debug', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR)) ? 1 : 0;

    $twitchName = filter_input(INPUT_POST, 'twitchName', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
    if (!preg_match('/^[a-zA-Z0-9_]{0,128}$/', $twitchName)) {
      $error = 'Twitch name may only have characters A-Z, 0-9 and underscores.';
      break;
    }
    $settings->twitchName = $twitchName;

    $timerCountdownSeconds = filter_input(INPUT_POST, 'timerCountdownSeconds', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
    if (!empty($timerCountdownSeconds)) {
      if (!getNumberIfWithinRange($timerCountdownSeconds, 0, 900)) {
        $error = 'Timer countdown must be empty, or in the range [0, 900].';
        break;
      }
    } else if ($timerCountdownSeconds === '0') {
      // empty('0') is true, so this case gets its own branch :)
      $timerCountdownSeconds = 0;
    } else {
      $timerCountdownSeconds = null;
    }
    $settings->timerCountdownSeconds = $timerCountdownSeconds;

    // History
    $avoidLastAnswers = getNumberIfWithinRange(filter_input(INPUT_POST, 'historyAvoidLastAnswers', FILTER_UNSAFE_RAW), 0, 99);
    if ($avoidLastAnswers === null) {
      $error = 'The value for "history last answers to avoid" is invalid!';
      break;
    } else if (!$db->hasQuestionCategoriesOrMore($settings->ownerId, $avoidLastAnswers)) {
      $error = 'Number of past questions to avoid is larger than the total number of questions!';
      break;
    } else {
      $settings->historyAvoidLastAnswers = $avoidLastAnswers;
    }

    $displayLastAnswers = getNumberIfWithinRange(filter_input(INPUT_POST, 'historyDisplayEntries', FILTER_UNSAFE_RAW), 0, 99);
    if ($displayLastAnswers === null) {
      $error = 'The value for "history answers to display" is invalid!';
      break;
    } else {
      $settings->historyDisplayEntries = $displayLastAnswers;
    }

    $highScoreDays = getNumberIfWithinRange(filter_input(INPUT_POST, 'highScoreDays', FILTER_UNSAFE_RAW), -1, 999);
    if ($highScoreDays === null) {
      $error = 'The value for "high score days" is invalid!';
      break;
    }
    $settings->highScoreDays = $highScoreDays;

    // Timeouts
    foreach ($timeouts as $key => $def) {
      $newTimeoutValue = getNumberIfWithinRange(filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW), 0, 999);
      if ($newTimeoutValue !== null) {
        $settings->{$key} = $newTimeoutValue;
      } else {
        $error = 'The value for "' . $def['text'] . '" is invalid!';
        break 2; // yikes
      }
    }

    // Save
    $updatedSuccess = $db->updateSettingsForOwnerId($ownerInfo['id'], $settings);
    echo '<p>The settings have been updated!</p>';
    if ($oldActiveValue !== $settings->activeMode) {
      // Remove quiz activity symbol in the header because the quiz activity mode was changed - it would be misleading.
      echo '<script>document.getElementById("activity_symbol").remove();</script>';
    }

    // Admin extensions
    if (isAdminOrImpersonator($ownerInfo)) {
      $dataUrl = filter_input(INPUT_POST, 'dataurl', FILTER_SANITIZE_URL);
      $db->saveQuestionDataUrl($ownerInfo['id'], $dataUrl);
    }
  } while (false);

  if ($error) {
    echo "<h2>ERROR</h2>$error";
  }

}

function getNumberIfWithinRange($value, int $minIncl, int $maxIncl): ?int {
  if (is_numeric($value) && $minIncl <= $value && $value <= $maxIncl) {
    return (int) $value;
  }
  return null;
}


// ---------------------
// Display of options
// ---------------------

$activeOptionsHtml = '';
foreach ($activeOptions as $key => $text) {
  if ($key === $settings->activeMode) {
    $activeOptionsHtml .= "<option value='$key' selected='selected'>$text</option>";
  } else {
    $activeOptionsHtml .= "<option value='$key'>$text</option>";
  }
}


if (!empty($error) || isset($updatedSuccess)) {
  // There was output before, so add another title to separate the form
  echo '<h2>Change settings</h2>';
}

$timerSolveCreatesQuestionChecked = $settings->timerSolveCreatesNewQuestion ? 'checked="checked"' : '';
$debugModeChecked = $settings->outputDebug() ? 'checked="checked"' : '';
$debugWarningDisplay = empty($debugModeChecked) ? 'none' : 'inline';
$twitchNameEscaped = htmlspecialchars($settings->twitchName, ENT_QUOTES);
echo <<<HTML
<form method="post" action="settings.php">
<p>You can change various parameters of your quiz here. Hover over the text for more details.</p>
<table>
 <tr class="section">
  <td colspan="2">General</td>
 </tr>
 <tr>
  <td title="General on/off switch for the commands"><label for="active">Quiz activity</label></td>
  <td><select name="active" id="active">$activeOptionsHtml</select></td>
 </tr>
 <tr>
 <td title="Your full Twitch username; used for answering questions via timer page. Leave empty to disable."><label for="twitchname">Twitch name</label></td>
 <td><input type="text" id="twitchname" name="twitchName" pattern="\w{0,128}" value="$twitchNameEscaped" /></td>
</tr>
 <tr>
  <td title="When !q timer solves a question, should it immediately create a new question? Leave unchecked if you want some time between solution and a new question">
    <label for="timersolvecreate">Timer solves and creates a new question</label>
  </td>
  <td><input type="checkbox" id="timersolvecreate" name="timerSolveCreatesQuestion" $timerSolveCreatesQuestionChecked /></td>
 </tr>
 <tr>
   <td title="Should !q timer reply with the timeout name that makes it silent when it would not have any text?"><label for="debug">!q timer: Debug reason if silent</label></td>
   <td><input type="checkbox" id="debug" name="debug" $debugModeChecked onchange="document.getElementById('debugwarning').style.display = (this.checked) ? 'inline' : 'none'; " />
       <span id="debugwarning" style="display: $debugWarningDisplay; font-size: 0.9em; color: #600">This might send debug text to your stream!</span></td>
 </tr>
 <tr>
   <td title="Timer countdown in seconds. You can change the value in the timer. Set to 0 to disable the countdown.">
     <label for="timerCountdownSeconds">Timer: Countdown seconds</label></td>
   <td><input type="number" id="timerCountdownSeconds" name="timerCountdownSeconds" value="{$settings->timerCountdownSeconds}" min="0" max="900" /></td>
 </tr>
 <tr class="section">
  <td colspan="2">History</td>
 </tr>
 <tr>
  <td title="How many past questions/answers should not be repeated?"><label for="hist1">Avoid <em>n</em> last answers</label></td>
  <td><input type="number" id="hist1" name="historyAvoidLastAnswers" value="{$settings->historyAvoidLastAnswers}" min="0" max="99" /></td>
 </tr>
 <tr>
  <td title="How many past questions should be shown on the web page?"><label for="hist2">Past answers shown on web page</label></td>
  <td><input type="number" id="hist2" name="historyDisplayEntries" value="{$settings->historyDisplayEntries}" min="0" max="99" /></td>
 </tr>
 <tr>
  <td title="The high score on the web page is based on the questions of the past number of days. Set -1 to hide the high score."><label for="highscoredays">High score from <em>n</em> days</label></td>
  <td><input type="number" id="highscoredays" name="highScoreDays" value="{$settings->highScoreDays}" min="-1" max="999" /></td>
</tr>
 <tr class="section">
  <td colspan="2">Timeouts</td>
 </tr>
 <tr class="title">
  <td>Timeout</td>
  <td>Value (seconds)</td>
</tr>
HTML;

$counter = 1;
foreach ($timeouts as $timeoutName => $timeout) {
  $id = 'timeout_' . $counter;
  echo " <tr>
  <td title='" . htmlspecialchars($timeout['help'], ENT_QUOTES) . "'><label for='$id'>{$timeout['text']}</label></td>
  <td><input type='number' min='0' max='999' name='{$timeoutName}' id='$id' value='{$settings->{$timeoutName}}' /></td>
 </tr>";
  ++$counter;
}

if (isAdminOrImpersonator($ownerInfo)) {
  $dataUrl = $db->getQuestionDataUrl($ownerInfo['id']);
  $dataUrlEsc = htmlspecialchars($dataUrl);

  echo <<<HTML
  <tr class="section">
    <td colspan="2">Admin</td>
  </tr>
  <tr>
    <td title="Included as link on the update questions page"><label for="dataurl">Data URL</label></td>
    <td><input type="text" id="dataurl" name="dataurl" value="$dataUrlEsc" /></td>
  </tr>
HTML;
}

echo <<<HTML
</table>

<br /><input type="submit" value="Save settings" />
</form>

</body>
</html>
HTML;

function isAdminOrImpersonator(array $ownerInfo): bool {
  return $ownerInfo['is_admin'] || !empty($ownerInfo['impersonator']);
}
