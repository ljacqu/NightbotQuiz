<?php

session_start();

require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';
require '../../inc/Utils.php';

Utils::setJsonHeader();

$db = new DatabaseHandler();
if (!isset($_SESSION['owner'])) {
  die(Utils::toResultJson('You are not logged in'));
}

$activityMode = filter_input(INPUT_POST, 'mode', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if ($activityMode !== 'ON' && $activityMode !== 'OFF') {
  // USER_ONLY is never sent by any JS script
  die(Utils::toResultJson('Unexpected activity mode "' . $activityMode . '"'));
}

$db->saveQuizActiveMode($_SESSION['owner'], $activityMode);
die(Utils::toResultJson('Successfully saved'));
