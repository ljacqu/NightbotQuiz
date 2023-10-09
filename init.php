<?php

require 'Configuration.php';
require './inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$db->initTables();

echo 'Finished initialization';

$createdUser = $db->initOwnerIfEmpty();
if ($createdUser) {
  echo '<br />Created initial user';
}
