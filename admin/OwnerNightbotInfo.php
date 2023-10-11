<?php

class OwnerNightbotInfo {

  public ?string $clientId = null;
  public ?string $clientSecret = null;
  public ?string $token = null;
  public ?int $tokenExpires = null;

  function __construct() {

  }

  static function createFromDbValues(array $data): OwnerNightbotInfo {
    $info = new OwnerNightbotInfo();
    $info->clientId = $data['client_id'];
    $info->clientSecret = $data['client_secret'];
    $info->token = $data['token'];
    $info->tokenExpires = $data['token_expires'];
    return $info;
  }
}
