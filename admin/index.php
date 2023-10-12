<?php

session_start();
require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';
$db = new DatabaseHandler();
$ownerInfo = Adminhelper::getOwnerInfoOrRedirect($db);

AdminHelper::outputHtmlStart('Quiz administration', $ownerInfo);
echo '<p class="crumbs"><b>Main</b></p>';

$tokenInfo = $db->getNightbotToken($ownerInfo['id']);
?>


<h2>Quiz administration</h2>
<ul>
  <li><a href="settings.php">Change settings</a></li>
  <li><a href="update.php">Update questions</a></li>
  <li><a href="./timer/">Timer configuration</a></li>
</ul>

<?php
if ($ownerInfo['is_admin']) {
  echo <<<HTML
<h2>System administration</h2>
<ul>
 <li><a href="statistics.php">Statistics</a></li>
 <li><a href="impersonate.php">Impersonate</a></li>
</ul>

HTML;
}

$hasValidToken = !empty($tokenInfo['token_expires']) && (time() < $tokenInfo['token_expires']);
if ($hasValidToken) {
  echo <<<HTML
<button onclick="window.location.href='./timer/timer.php';"
        style="padding: 1em; border-radius: 8px; background-color: #ffe7cf; margin-top: 2em; margin-left: 0.5em">
   Open timer page
</button>
HTML;
}

?>

</body>
</html>

