<?php

require '../Configuration.php';
require '../inc/DatabaseHandler.php';
require '../inc/OwnerSettings.php';
require '../inc/SecretValidator.php';
require '../inc/Utils.php';

$db = new DatabaseHandler();
SecretValidator::getOwnerSettingsOrExit($db);
$secret = $_GET['secret']; // SecretValidator validated that the value can be trusted

echo <<<HTML
<ul>
  <li><a href="settings.php?secret=$secret">Change settings</a></li>
  <li><a href="update.php?secret=$secret">Update questions</a></li>
</ul>
HTML;
