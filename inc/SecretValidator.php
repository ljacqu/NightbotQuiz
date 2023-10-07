<?php

final class SecretValidator {

  private function __construct() {
  }

  static function getOwnerValuesForPollOrExit(DatabaseHandler $db): OwnerPollValues {
    $secret = self::getSecretOrExit();
    $ownerInfo = $db->getValuesForPollPageBySecret($secret);
    if ($ownerInfo === null) {
      self::exitForInvalidSecret();
    }
    return OwnerPollValues::createFromDbRow($ownerInfo);
  }

  private static function getSecretOrExit() {
    if (!isset($_GET['secret']) || !is_string($_GET['secret'])) {
      die(toResultJson('Error: Missing API secret!'));
    }
    return $_GET['secret'];
  }

  private static function exitForInvalidSecret() {
    die(toResultJson('Error: Invalid API secret!'));
  }
}
