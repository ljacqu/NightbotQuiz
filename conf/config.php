<?php

/**
 * HTTP header name, as transformed by PHP, that Nightbot sends for identifying the
 * originator of the request. The actual header is 'Nightbot-User'.
 * See https://docs.nightbot.tv/variables/urlfetch
 */
define('USER_HTTP_HEADER', 'HTTP_NIGHTBOT_USER');


// --------------------------
// History (past questions)
// --------------------------
/**
 * Number of past questions to keep stored. They will be shown in index.php.
 */
define('HISTORY_KEEP_ENTRIES', 50);
/**
 * Number of past questions to avoid regenerating. This uses the past questions that are stored,
 * so the actual number of questions is min(HISTORY_AVOID_LAST_N_QUESTIONS, HISTORY_KEEP_ENTRIES).
 * Run validate_data.php to ensure that this configuration is in harmony with your questions.
 */
define('HISTORY_AVOID_LAST_N_QUESTIONS', 3);



// ---------------
// Command names
// ---------------
define('COMMAND_QUESTION', '!q');
define('COMMAND_ANSWER', '!a');

