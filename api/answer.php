<?php

require '../Configuration.php';
require '../inc/constants.php';
require '../inc/Answer.php';
require '../inc/DatabaseHandler.php';
require '../inc/OwnerSettings.php';
require '../inc/Question.php';
require '../inc/QuestionDraw.php';
require '../inc/QuestionService.php';
require '../inc/SecretValidator.php';
require '../inc/Utils.php';

require '../inc/questiontype/QuestionType.php';

Utils::setJsonHeader();
$db = new DatabaseHandler();
$settings = SecretValidator::getOwnerSettingsOrExit($db);

if ($settings->activeMode === 'OFF') {
  die(Utils::toResultJson(' '));
}

if (!isset($_GET['a'])) {
  die(Utils::toResultJson('Please provide a guess! Type ' . COMMAND_QUESTION . ' to see the text.'));
}

$questionService = new QuestionService($db);

try {
  $db->startTransaction();

  $currentQuestion = $questionService->getLastQuestionDraw($settings->ownerId);
  if ($currentQuestion === null) {
    die(Utils::toResultJson('Error: No question was asked so far!'));
  } else if ($currentQuestion->solved !== null) {
    die(Utils::toResultJson('The answer was solved. Run ' . COMMAND_QUESTION . ' for a new question'));
  }

  $givenAnswer = filter_input(INPUT_GET, 'a', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR) ?? '';
  $givenAnswer = strtolower(Utils::unicodeTrim($givenAnswer));

  if (empty($givenAnswer)) {
    echo Utils::toResultJson('Please provide an answer!');
  } else {
    $questionType = QuestionType::getType($currentQuestion->question);
    $result = $questionType->processAnswer($currentQuestion->question, $givenAnswer);
    $user = Utils::extractUser();

    if ($result->invalid) {
      echo Utils::toResultJson("@$user Invalid answer! Type " . COMMAND_QUESTION . " to see the question again");
    } else {
      $db->saveDrawAnswer($currentQuestion->drawId, $user, $result->answer, $result->isCorrect ? 1 : 0);

      if ($result->resolvesQuestion) {
        $db->setCurrentDrawAsSolved($currentQuestion->drawId);

        if ($result->isCorrect) {
          $start = drawRandomText(['Congratulations!', 'Nice!', 'Excellent!', 'Splendid!', 'Perfect!', 'Well done!', 'Awesome!', 'Good job!']);
          $textAnswer = $result->answerForText ?? $result->answer;
          echo Utils::toResultJson($start . ' ' . ucfirst($textAnswer) . ' is the right answer');
        } else { // resolves question, but was not correct
          echo Utils::toResultJson('Sorry, that was not the right answer');
        }
      } else {
        $textAnswer = $result->answerForText ?? $result->answer;
        echo Utils::toResultJson("$user guessed $textAnswer");
      }
    }
  }

  $db->commit();
} catch (Exception $e) {
  $db->rollBackIfNeeded();
  throw $e;
}

function drawRandomText(array $choices): string {
  return $choices[rand(0, count($choices) - 1)];
}
