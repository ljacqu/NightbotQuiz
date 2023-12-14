<?php

class Configuration {

  // ------
  // Database connection details
  // ------

  /** Database host. */
  const DB_HOST = 'localhost';
  /** Database name. */
  const DB_NAME = 'nightbot_quiz';
  /** Database user. */
  const DB_USER = 'root';
  /** Database password. */
  const DB_PASS = '';

  // ------
  // Twitch application details
  // ------
  // Register a client at https://dev.twitch.tv/console/apps
  const TWITCH_CLIENT_ID = 'edar5nou1utwq98xydungifgv4vipt'; // TODO: NO COMMIT
  const TWITCH_CLIENT_SECRET = 'wxb6ge8xfpi8stlw2tm1k4c94miztn';


  private function __construct() {
  }
}
