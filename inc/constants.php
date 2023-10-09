<?php

/**
 * HTTP header name, as transformed by PHP, that Nightbot sends for identifying the
 * originator of the request. The actual header is 'Nightbot-User'.
 * See https://docs.nightbot.tv/variables/urlfetch
 */
define('USER_HTTP_HEADER', 'HTTP_NIGHTBOT_USER');


// ---------------
// Command names
// ---------------
define('COMMAND_QUESTION', '!q');
define('COMMAND_ANSWER', '!a');

