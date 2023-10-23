<?php

final class Utils {

  /**
   * HTTP header name, as transformed by PHP, that Nightbot sends for identifying the
   * originator of the request. The actual header name is 'Nightbot-User'.
   * See https://docs.nightbot.tv/variables/urlfetch
   */
  private const USER_HTTP_HEADER = 'HTTP_NIGHTBOT_USER';

  private function __construct() {
  }

  static function toResultJson(string $text, ?array $additionalProperties=null): string {
    if ($additionalProperties) {
      $additionalProperties['result'] = $text;
      return json_encode($additionalProperties, JSON_FORCE_OBJECT);
    }
    return json_encode(['result' => $text], JSON_FORCE_OBJECT);
  }

  // From https://stackoverflow.com/a/4167053
  // For some reason, certain users (maybe using Twitch extensions?) write stuff like
  // "xho 󠀀", which has a zero-width space at the end. PHP's trim() does not remove it.
  static function unicodeTrim(?string $text): ?string {
    return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $text);
  }

  static function connectTexts($text1, $text2) {
    if (empty($text1)) {
      return $text2;
    } else if (empty($text2)) {
      return $text1;
    }

    $lastCharacter = mb_substr(trim($text1), -1, 1, 'UTF-8');
    if (IntlChar::ispunct($lastCharacter)) {
      return trim($text1) . ' ' . trim($text2);
    }
    return trim($text1) . '. ' . trim($text2);
  }

  static function setJsonHeader(): void {
    header('Content-type: application/json; charset=utf-8');
  }

  static function extractUser(): ?string {
    if (isset($_SERVER[self::USER_HTTP_HEADER])) {
      $nightbotUser = $_SERVER[self::USER_HTTP_HEADER];
      return preg_replace('~^.*?displayName=([^&]+)&.*?$~', '\\1', $nightbotUser);
    }
    return null;
  }
}
