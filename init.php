<?php

require 'Configuration.php';
require './inc/DatabaseHandler.php';
require './inc/OwnerSettings.php';

$db = new DatabaseHandler();
$db->initTables();

echo 'Finished initialization';

$passOfNewUser = $db->initOwnerIfEmpty();
if ($passOfNewUser) {
  echo '<br />Created initial user with password <code>' . $passOfNewUser . '</code>';
}
