<?php

require './conf/Configuration.php';
require './inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$db->initTables();

echo 'Finished initialization';

$createdUser = $db->initUserIfEmpty();
if ($createdUser) {
  echo '<br />Created initial user';
}
