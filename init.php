<?php

require 'Configuration.php';
require './inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$db->initTables();

echo 'Finished initialization';

$createdUser = $db->initOwnerIfEmpty();
if ($createdUser) {
  echo '<br />Created initial user (change <a href="https://bcrypt-generator.com/">bcrypt hash</a> in table manually)';
}
