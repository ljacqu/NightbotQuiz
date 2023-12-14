<?php

class OwnerTwitchInfo {

  public ?string $token = null;
  public ?int $tokenExpires = null;
  public ?string $refreshToken = null;

  static function createFromDbValues(array $data): OwnerTwitchInfo {
    $info = new OwnerTwitchInfo();
    $info->token        = $data['tw_token'];
    $info->tokenExpires = $data['tw_token_expires'];
    $info->refreshToken = $data['tw_refresh_token'];
    return $info;
  }
}
