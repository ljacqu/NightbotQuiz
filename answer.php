<?php

require './conf/config.php';
require './conf/Configuration.php';
require './inc/UserSettings.php';
require './inc/DatabaseHandler.php';
require './inc/Answer.php';
require './inc/functions.php';
require './conf/question_types.php';
require './gen/question_type_texts.php';
require './inc/SecretValidator.php';
require './inc/OwnerPollValues.php';
require './inc/QuestionService.php';
require './inc/QuestionDraw.php';
require './inc/Question.php';
require './inc/QuestionType.php';

setJsonHeader();
$db = new DatabaseHandler();
$pollParams = SecretValidator::getOwnerValuesForPollOrExit($db);

if ($pollParams->activeMode === 'OFF') {
  die(toResultJson(' '));
}

if (!isset($_GET['a'])) {
  die(toResultJson('Please provide a guess! Type ' . COMMAND_QUESTION . ' to see the text.'));
}

$questionService = new QuestionService($db);

$currentQuestion = $questionService->getLastQuestionDraw($pollParams->ownerId);
if ($currentQuestion === null) {
  die(toResultJson('Error: No question was asked so far!'));
} else if ($currentQuestion->solved !== null) {
  // TODO: This used to include who solved the question
  die(toResultJson('The answer was solved. Run !q for a new question'));
}


$givenAnswer = filter_input(INPUT_GET, 'a', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR) ?? '';
$givenAnswer = strtolower(unicodeTrim($givenAnswer));

if (empty($givenAnswer)) {
  echo toResultJson('Please provide an answer!');
} else {
  $result = QuestionType::processAnswer($currentQuestion->question, $givenAnswer);
  if ($result->invalid) {
    echo toResultJson('Invalid answer! Type ' . COMMAND_QUESTION . ' to see the question again');
  } else {
    $db->saveDrawAnswer($currentQuestion->drawId, extractUser(), $result->answer, $result->isCorrect ? 1 : 0);

    if ($result->resolvesQuestion) {
      if ($result->isCorrect) {
        $congratsOptions = ['Congratulations!', 'Nice!', 'Excellent!', 'Splendid!', 'Perfect!', 'Well done!', 'Awesome!', 'Good job!'];
        $start = $congratsOptions[rand(0, count($congratsOptions) - 1)];
        echo toResultJson($start . ' ' . ucfirst($result->answer) . ' is the right answer');
      } else { // resolves question, but was not correct
        echo toResultJson('Sorry, that was not the right answer');
      }

      $db->setCurrentDrawAsSolved($currentQuestion->drawId);
    }
  }
}


$fh = fopen('./gen/last_answer.txt', 'w');
if ($fh) {
  fwrite($fh, time());
  fclose($fh);
}


// --------------
// Functions
// --------------

function extractUser() {
  $solver = '';
  if (isset($_SERVER[USER_HTTP_HEADER])) {
    $nightbotUser = $_SERVER[USER_HTTP_HEADER];
    $solver = preg_replace('~^.*?displayName=([^&]+)&.*?$~', '\\1', $nightbotUser);
  }
  return $solver ? $solver : '&__unknown';
}
