<?php

function toResultJson($text) {
  return json_encode(['result' => $text], JSON_FORCE_OBJECT);
}

// From https://stackoverflow.com/a/4167053
// For some reason, certain users (maybe using Twitch extensions?) write stuff like
// "xho 󠀀", which has a zero-width space at the end. PHP's trim() does not remove it.
function unicodeTrim($text) {
  return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $text);
}

function getSettingsForSecretOrThrow(DatabaseHandler $db): UserSettings {
  if (!isset($_GET['secret']) || !is_string($_GET['secret'])) {
    die(toResultJson('Error: Missing API secret!'));
  }

  $settings = $db->getSettingsForSecret($_GET['secret']);
  if ($settings === null) {
    die(toResultJson('Error: Invalid API secret!'));
  }
  return UserSettings::createFromDbRow($settings);
}

function getOwnerInfoForSecretOrThrow(DatabaseHandler $db): array {
  if (!isset($_GET['secret']) || !is_string($_GET['secret'])) {
    die(toResultJson('Error: Missing API secret!'));
  }

  $ownerInfo = $db->getOwnerInfoBySecret($_GET['secret']);
  if ($ownerInfo === null) {
    die(toResultJson('Error: Invalid API secret!'));
  }
  return $ownerInfo;
}

function setJsonHeader() {
  header('Content-type: application/json; charset=utf-8');
}
