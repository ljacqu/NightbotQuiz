<?php

session_start();

require '../AdminHelper.php';
require '../OwnerNightbotInfo.php';
require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db, '../');

AdminHelper::outputHtmlStart('Browser timer', $ownerInfo, '../');
$nightbotInfo = AdminHelper::getOwnerNightbotInfo($db, $ownerInfo['id']);

// Does not mean it's valid, but means it was set up at some point
$hasTokenInfo = !empty($nightbotInfo->tokenExpires);

$timerFirst = $hasTokenInfo ? '<li><b><a href="timer.php">Open the timer</a></b></li>' : '';
$timerLast  = $hasTokenInfo ? '' : '<li><a href="timer.php">Open the timer</a></li>';

echo <<<HTML
<p class="crumbs"><a href="../">Main</a> &lt; <b>Timer</b></p>
<h2>Browser timer</h2>
<p>This section allows you to configure and run a timer that shows you the current question
and polls the quiz at regular intervals so actions are triggered.</p>

<ul>
  $timerFirst
  <li><a href="app_configuration.php">Configure Nightbot client details</a></li>
  <li><a href="obtain_token.php">Obtain token</a></li>
  $timerLast
</ul>


</body></html>
HTML;
