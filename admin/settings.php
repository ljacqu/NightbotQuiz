<?php

require '../conf/Configuration.php';
require '../inc/UserSettings.php';
require '../inc/DatabaseHandler.php';
require '../inc/functions.php';
require '../inc/Question.php';

$db = new DatabaseHandler();
$settings = getSettingsForSecretOrThrow($db);
$secret = $_GET['secret']; // OK to call because the above function validated it

$activeOptions = [
  'ON',
  'USER_ONLY',
  'OFF'
];
$activeOptionsText = [
  'ON' => 'On',
  'USER_ONLY' => 'User only (silent timer)',
  'OFF' => 'Off'
];

// Array keys must correspond to the field name in UserSettings
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
  'userNewWait' => [
    'text' => 'Seconds to wait for !q new',
    'help' => 'If a user does !q new, how many seconds since the last question\'s creation must have elapsed?',
  ]
];

$active = filter_input(INPUT_POST, 'active', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if ($active !== null && in_array($active, $activeOptions, true)) {

  $error = null;
  do {
    $settings->activeMode = $active;

    // History
    // TODO: Validate that this works out with the number of questions!
    $avoidLastAnswers = getNumberIfWithinRange(filter_input(INPUT_POST, 'historyAvoidLastAnswers', FILTER_UNSAFE_RAW), 0, 99);
    if ($avoidLastAnswers !== null) {
      $settings->historyAvoidLastAnswers = $avoidLastAnswers;
    } else {
      $error = 'The value for "history last answers to avoid" is invalid!';
      break;
    }
    $displayLastAnswers = getNumberIfWithinRange(filter_input(INPUT_POST, 'historyDisplayEntries', FILTER_UNSAFE_RAW), 0, 99);
    if ($displayLastAnswers !== null) {
      $settings->historyDisplayEntries = $displayLastAnswers;
    } else {
      $error = 'The value for "history answers to display" is invalid!';
      break;
    }

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


    $updatedSuccess = $db->updateSettingsForSecret($secret, $settings);
    if ($updatedSuccess) {
      echo 'The settings have been updated!';
    } else {
      echo 'An error occurred while updating the settings.';
    }


  } while (false);

  if ($error) {
    echo "<h2>ERROR</h2>$error";
  }

}

function getNumberIfWithinRange($value, $minIncl, $maxIncl) {
  if (is_numeric($value) && $minIncl <= $value && $value <= $maxIncl) {
    return $value;
  }
  return null;
}


// ---------------------
// Display of options
// ---------------------

$activeOptionsHtml = '';
foreach ($activeOptions as $opt) {
  $text = $activeOptionsText[$opt];
  if ($opt === $settings->activeMode) {
    $activeOptionsHtml .= "<option value='$opt' selected='selected'>$text</option>";
  } else {
    $activeOptionsHtml .= "<option value='$opt'>$text</option>";
  }
}



echo <<<HTML
<h2>Change settings</h2>
<form method="post" action="settings.php?secret=$secret">
Hover over the text for more details.
<table>
 <tr class="section">
  <td colspan="2">General</td>
 </tr>
 <tr>
  <td><label for="active">Quiz activity</label></td>
  <td><select name="active" id="active">$activeOptionsHtml</select></td>
 </tr>
  <tr class="section">
  <td colspan="2">History</td>
 </tr>
 <tr>
  <td title="How many past questions/answers should not be repeated?"><label for="hist1">Avoid n last answers</label></td>
  <td><input type="number" id="hist1" name="historyAvoidLastAnswers" value="{$settings->historyAvoidLastAnswers}" min="0" max="99" /></td>
 </tr>
 <tr>
  <td title="How many past questions should be shown on the web page?"><label for="hist2">Past answers shown on web page</label></td>
  <td><input type="number" id="hist2" name="historyDisplayEntries" value="{$settings->historyDisplayEntries}" min="0" max="99" /></td>
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
echo <<<HTML
</table>

<br /><input type="submit" value="Save settings" />
</form>
HTML;
