<?php

final class SecretValidator {

  private function __construct() {
  }

  static function getOwnerSettingsOrExit(DatabaseHandler $db): OwnerSettings {
    $secret = self::getSecretOrExit();
    $settings = $db->getSettingsForSecret($secret);
    if ($settings === null) {
      self::exitForInvalidSecret();
    }
    return OwnerSettings::createFromDbRow($settings);
  }

  static function getOwnerInfoForSecretOrExit(DatabaseHandler $db): array {
    $secret = self::getSecretOrExit();
    $ownerInfo = $db->getOwnerInfoBySecret($secret);
    if ($ownerInfo === null) {
      self::exitForInvalidSecret();
    }
    return $ownerInfo;
  }

  private static function getSecretOrExit(): string {
    if (!isset($_GET['secret']) || !is_string($_GET['secret'])) {
      die(Utils::toResultJson('Error: Missing API secret!'));
    }
    return $_GET['secret'];
  }

  private static function exitForInvalidSecret() {
    die(Utils::toResultJson('Error: Invalid API secret!'));
  }
}
