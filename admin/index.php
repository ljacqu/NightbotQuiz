<?php

require '../inc/functions.php';
require '../inc/UserSettings.php';
require '../conf/Configuration.php';
require '../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$settings = getSettingsForSecretOrThrow($db);

$secret = $_GET['secret']; // getSettingsForSecretOrThrow validated that the value can be trusted

echo <<<HTML
<ul>
  <li><a href="settings.php?secret=$secret">Change settings</a></li>
  <li><a href="update.php?secret=$secret">Update questions</a></li>
</ul>
HTML;
