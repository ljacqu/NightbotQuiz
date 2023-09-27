<?php

require '../conf/config.php';
require '../inc/Question.php';
require '../data/question_types.php';

$questions = [];

// ------
// Generate place questions
// ------

// Load place question texts and validate them
$textsUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/texts.ini';
$iniFileContents = readFileOrThrow($textsUrl);
$iniTexts = parse_ini_string($iniFileContents);
if ($iniTexts === false) {
  die("Failed to read '$textsUrl'. Please make sure it has valid INI syntax.");
}
$data_questionTypeTexts = generatePlaceQuestionTexts($iniTexts);

$fh = fopen('../conf/question_type_texts.php', 'w') or die('Failed to write to question_type_texts.php');
fwrite($fh, '<?php $data_questionTypeTexts = ' . var_export($data_questionTypeTexts, true) . ';');
fclose($fh);
echo '<br />Updated question texts.';


// Real places
echo '<br />Generating questions for real place names';
$realPlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_real_names.txt';
$realPlaceQuestions = generatePlaceQuestions($realPlacesUrl, 'REAL_PLACE');
addEntriesToArray($questions, $realPlaceQuestions);
echo ': created ' . count($realPlaceQuestions) . ' questions';

// Fake places
echo '<br />Generating questions for fake place names';
$fakePlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_fake_names.txt';
$fakePlaceQuestions = generatePlaceQuestions($fakePlacesUrl, 'FAKE_PLACE');
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

function generatePlaceQuestionTexts($iniData) {
  $placeQuestion = validateIsTextWithinLength('place_question', $iniData, 10, 100);
  if (strpos($placeQuestion, '%place%') === false) {
    die("The text for key 'place_question' must have the placeholder %place% in order to include the place name!");
  }

  $questionTexts = [
    'REAL_PLACE' => [
      'question' => $placeQuestion,
      'answers' => validateIsNonEmptyCsvList('real_place_possible_answers', $iniData),
      'isolatedAnswer' => validateIsTextWithinLength('real_place_isolated_answer', $iniData, 1, 100),
      'resolutionText' => validateIsTextWithinLength('real_place_resolution_text', $iniData, 1, 100)
    ],
    'FAKE_PLACE' => [
      'question' => $placeQuestion,
      'answers' => validateIsNonEmptyCsvList('fake_place_possible_answers', $iniData),
      'isolatedAnswer' => validateIsTextWithinLength('fake_place_isolated_answer', $iniData, 1, 100),
      'resolutionText' => validateIsTextWithinLength('fake_place_resolution_text', $iniData, 1, 100)
    ]
  ];

  return $questionTexts;
}

function generatePlaceQuestions($file, $questionType) {
  $lines = explode("\n", readFileOrThrow($file));

  $questions = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if (!empty($line)) {
      $questions[] = new Question($questionType, $line);
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

function validateIsTextWithinLength($key, $texts, $minLength, $maxLength) {
  if (!isset($texts[$key])) {
    die("No text for key '$key' found!");
  }

  $obj = $texts[$key];
  if (is_scalar($obj)) {
    $str = (string) $obj;
    if (strlen($str) < $minLength) {
      die("Expected the text for '$key' to have at least $minLength characters");
    } else if ($maxLength && strlen($str) > $maxLength) {
      die("Expected the text for '$key' to have at most $maxLength characters");
    }
    return $str;
  } else {
    die("The entry for '$key' is not a string!");
  }
}

function validateIsNonEmptyCsvList($key, $texts) {
  $text = validateIsTextWithinLength($key, $texts, 0, null);
  $array = [];
  foreach (explode(',', $text) as $entry) {
    if (empty($entry)) {
      die('Found empty text entry in text for "' . $key . '"');
    }
    $array[] = trim(strtolower($entry));
  }
  return $array;
}
