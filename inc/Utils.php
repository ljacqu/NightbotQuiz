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

  static function toResultJson($text) {
    return json_encode(['result' => $text], JSON_FORCE_OBJECT);
  }

  // From https://stackoverflow.com/a/4167053
  // For some reason, certain users (maybe using Twitch extensions?) write stuff like
  // "xho 󠀀", which has a zero-width space at the end. PHP's trim() does not remove it.
  static function unicodeTrim($text) {
    return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $text);
  }

  static function setJsonHeader() {
    header('Content-type: application/json; charset=utf-8');
  }

  static function extractUser() {
    $solver = '';
    if (isset($_SERVER[self::USER_HTTP_HEADER])) {
      $nightbotUser = $_SERVER[self::USER_HTTP_HEADER];
      $solver = preg_replace('~^.*?displayName=([^&]+)&.*?$~', '\\1', $nightbotUser);
    }
    return $solver ? $solver : '&__unknown';
  }
}
