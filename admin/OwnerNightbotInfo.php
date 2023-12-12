<?php

class OwnerNightbotInfo {

  public ?string $clientId = null;
  public ?string $clientSecret = null;
  public ?string $token = null;
  public ?int $tokenExpires = null;

  static function createFromDbValues(array $data): OwnerNightbotInfo {
    $info = new OwnerNightbotInfo();
    $info->clientId     = $data['nb_client_id'];
    $info->clientSecret = $data['nb_client_secret'];
    $info->token        = $data['nb_token'];
    $info->tokenExpires = $data['nb_token_expires'];
    return $info;
  }
}
