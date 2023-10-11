<?php

session_start();
require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';
$db = new DatabaseHandler();
$ownerInfo = Adminhelper::getOwnerInfoOrRedirect($db);

AdminHelper::outputHtmlStart('Quiz administration', $ownerInfo);
?>

<p class="crumbs"><b>Main</b></p>
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

?>

</body>
</html>

