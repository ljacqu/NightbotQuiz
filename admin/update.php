<?php

require '../conf/config.php';
require '../inc/QuestionType.php';
require '../inc/Question.php';
require '../data/question_types.php';

$questions = [];

// ------
// Generate place questions
// ------

// Load question template and validate it
$placeQuestionTemplate = readFileOrThrow('https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_question.txt');
$placeQuestionTemplate = trim($placeQuestionTemplate);
if (strlen($placeQuestionTemplate) < 10 || strlen($placeQuestionTemplate) > 100) {
  throw new Exception('Error! Place question template has a text length of ' . strlen($placeQuestionTemplate) . '; expected something in the range [10, 100].');
} else if (strpos($placeQuestionTemplate, '%place%') === false) {
  throw new Exception('Error! Place question template does not have the placeholder "%place%"');
}

// Real places
echo '<br />Generating questions for real place names';
$realPlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_real_names.txt';
$realPlaceQuestions = generatePlaceQuestions($realPlacesUrl, $placeQuestionTemplate, 'REAL_PLACE');
addEntriesToArray($questions, $realPlaceQuestions);
echo ': created ' . count($realPlaceQuestions) . ' questions';

// Fake places
echo '<br />Generating questions for fake place names';
$fakePlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_fake_names.txt';
$fakePlaceQuestions = generatePlaceQuestions($fakePlacesUrl, $placeQuestionTemplate, 'FAKE_PLACE');
addEntriesToArray($questions, $fakePlaceQuestions);
echo ': created ' . count($fakePlaceQuestions) . ' questions';

// ------
// Generate custom questions
// ------
echo '<br />Generating custom questions';
$customQuestionsUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/questions.txt';
$customQuestions = generateCustomQuestions($customQuestionsUrl);
addEntriesToArray($questions, $customQuestions);
echo ': created ' . count($customQuestions) . ' questions';

// ------
// Save questions
// ------

$fh = fopen('../conf/questions.php', 'w') or die('Failed to open the questions file');
fwrite($fh, '<?php $data_questions = ' . var_export($questions, true) . ';');
fclose($fh);

echo '<br /><b>Success</b>: Saved a total of ' . count($questions) . ' questions';

// ------
// Functions
// ------

function generatePlaceQuestions($file, $questionTemplate, $questionType) {
  $lines = explode("\n", readFileOrThrow($file));

  $questions = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if (!empty($line)) {
      $questionText = str_replace('%place%', $line, $questionTemplate);
      $questions[] = new Question($questionType, $questionText);
    }
  }
  return $questions;
}

function generateCustomQuestions($file) {
  $contents = readFileOrThrow($file);

  $questions = [];
  $currentQuestion = null;
  foreach (explode("\n", $contents) as $line) {
    $line = trim($line);
    if (empty($line)) {
      if ($currentQuestion !== null) {
        throw new Exception('A question "' . $currentQuestion->question . '" was followed by an empty line');
      }
    } else {
      if ($currentQuestion === null) {
        $currentQuestion = new Question('custom', $line);
      } else {
        $currentQuestion->setAnswersFromCommaSeparatedText($line);
        $questions[] = $currentQuestion;
        $currentQuestion = null;
      }
    }
  }

  if ($currentQuestion !== null) {
    if (empty($currentQuestion->answers)) {
      throw new Exception('The final question "' . $currentQuestion->question . '" appears not to have any answers');
    } else {
      $questions[] = $currentQuestion;
    }
  }
  return $questions;
}

function readFileOrThrow($fileLocation) {
  $contents = file_get_contents($fileLocation);
  if ($contents === false) {
    throw new Exception('Could not read file "' . $fileLocation . "'");
  }
  return $contents;
}

function addEntriesToArray(&$arr, $entries) {
  foreach ($entries as $entry) {
    $arr[] = $entry;
  }
}
