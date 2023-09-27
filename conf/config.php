<?php

/**
 * Secret text that needs to be passed to all PHP pages as ?secret, e.g. poll.php?secret=YourSecret (see readme).
 * This is required for all pages that are meant to be called only by Nightbot, whose requests we trust.
 */
define('API_SECRET', 's');
/**
 * HTTP header name, as transformed by PHP, that Nightbot sends for identifying the
 * originator of the request. The actual header is 'Nightbot-User'.
 * See https://docs.nightbot.tv/variables/urlfetch
 */
define('USER_HTTP_HEADER', 'HTTP_NIGHTBOT_USER');

/**
 * When poll.php is called by an automatic timer (!q timer), how many seconds from a question's creation should it
 * wait before solving the question and generating a new one?
 */
define('TIMER_UNSOLVED_QUESTION_WAIT_SECONDS', 180);
/**
 * When poll.php is called by an automatic timer (!q timer) and the last question was solved, how many seconds SINCE
 * the last question was solved should we wait before generating a new question? This wait time is useful if you use
 * regular_poll.php to call !q timer very frequently and don't want Nightbot to seem too aggressive with posting new
 * questions as soon as someone answers a previous question. If you use a timer with large intervals, this setting can be 0.
 */
define('TIMER_SOLVED_QUESTION_WAIT_SECONDS', 180);
/**
 * When there's an unsolved question and someone tried an answer, how many seconds should we wait for !q timer before
 * the question is resolved and a new answer is created?
 */
define('TIMER_LAST_ANSWER_WAIT_SECONDS', 60);

/**
 * If "!q new" is called and there is an unsolved riddle, how many seconds from its creation
 * should we wait before solving the riddle and generating a new one?
 */
define('USER_POLL_WAIT_SECONDS', 120);

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

