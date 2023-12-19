<?php

session_start();

require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';
require '../../inc/Utils.php';

Utils::setJsonHeader();

$db = new DatabaseHandler();
if (!isset($_SESSION['owner'])) {
  die(Utils::toResultJson('Error: You are not logged in'));
} else if (isset($_SESSION['impersonator'])) {
  die(Utils::toResultJson('Error: You are impersonating a user. The request was blocked.'));
}

$newCountdownWait = filter_input(INPUT_POST, 'seconds', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

if ($newCountdownWait === null) {
  // The setting _can_ be null, but not in this context: when the countdown is started, an empty seconds field is
  // treated as zero because we understand it to mean the user doesn't want to see the countdown anymore.
  die(Utils::toResultJson('Error: Missing countdown value'));
} else {
  $newCountdownWait = (int) $newCountdownWait;
  if ($newCountdownWait < 0 || $newCountdownWait > 900) {
    die(Utils::toResultJson('Error: The countdown time is not in a valid range.'));
  }
}

$db->saveTimerCountdownValue($_SESSION['owner'], $newCountdownWait);
die(Utils::toResultJson('Successfully saved'));
