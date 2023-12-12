<?php

session_start();
require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';
$db = new DatabaseHandler();
$ownerInfo = Adminhelper::getOwnerInfoOrRedirect($db);

AdminHelper::outputHtmlStart('Quiz administration', $ownerInfo);
echo '<p class="crumbs"><b>Main</b></p>';
?>

<ul>
  <li><a href="change_pass.php">Change password</a></li>
</ul>

<h2>Quiz administration</h2>
<ul class="overview">
  <li><a href="overview.php">Overview</a></li>
  <li><a href="settings.php">Settings</a></li>
  <li><a href="update.php">Update questions</a></li>
  <li><a href="./timer/">Timer configuration</a></li>
  <li><a href="test_calls.php">Test quiz commands</a></li>
</ul>

<?php
if ($ownerInfo['is_admin']) {
  echo <<<HTML
<h2>System administration</h2>
<ul class="overview">
 <li><a href="statistics.php">Statistics</a></li>
 <li><a href="impersonate.php">Impersonate</a></li>
 <li><a href="create_owner.php">Create user</a></li>
</ul>

HTML;
}

$tokenInfo = $db->getNightbotToken($ownerInfo['id']);
$hasValidToken = !empty($tokenInfo['nb_token_expires']) && (time() < $tokenInfo['nb_token_expires']);
if ($hasValidToken) {
  echo <<<HTML
<button onclick="window.location.href='./timer/timer.php';" class="action">
   Open timer page
</button>
HTML;
}

?>

</body>
</html>

