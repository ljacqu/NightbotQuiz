<?php
error_reporting(E_ALL);

require './conf/config.php';
require './inc/functions.php';
require './inc/Question.php';

// Check the text lines
$questions = readPossibleQuestions();
echo 'Validated ' . count($questions) . ' questions.';

// Check some configurations
if (HISTORY_AVOID_LAST_N_QUESTIONS > HISTORY_KEEP_ENTRIES) {
  echo '<br />Note: HISTORY_AVOID_LAST_N_QUESTIONS is larger than HISTORY_KEEP_ENTRIES';
}
if (HISTORY_AVOID_LAST_N_QUESTIONS >= count($questions)) {
  echo '<br />Error: HISTORY_AVOID_LAST_N_QUESTIONS is larger than the total number of questions';
}
