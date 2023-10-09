<?php

class MedcamUpdater extends Updater {

  function generateQuestions(): array {
    // Load place question texts and validate them
    echo '<h2>Texts</h2>';
    $textsUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/texts.ini';
    echo "<div class='link'>Using $textsUrl</div>";

    $iniFileContents = $this->readFileOrThrow($textsUrl);
    $iniTexts = parse_ini_string($iniFileContents);
    if ($iniTexts === false) {
      die("Failed to read '$textsUrl'. Please make sure it has valid INI syntax.");
    }
    $placeTexts = $this->generatePlaceQuestionTexts($iniTexts);

    $fh = fopen('../gen/qt_place_texts.json', 'w') or die('Failed to write to qt_place_texts.json');
    fwrite($fh, json_encode($placeTexts));
    fclose($fh);
    echo '✓ Saved the question texts successfully.';

    $questions = [];

    // Real places
    echo '<h2>Real place questions</h2>';
    $realPlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_real_names.txt';
    echo "<div class='link'>Using $realPlacesUrl</div>";
    $realPlaceQuestions = $this->generatePlaceQuestions($realPlacesUrl, 'yes');
    self::addEntriesToArray($questions, $realPlaceQuestions);
    echo '✓ Loaded ' . count($realPlaceQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($realPlaceQuestions);

    // Fake places
    echo '<h2>Fake place questions</h2>';
    $fakePlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_fake_names.txt';
    echo "<div class='link'>Using $fakePlacesUrl</div>";
    $fakePlaceQuestions = $this->generatePlaceQuestions($fakePlacesUrl, 'no');
    self::addEntriesToArray($questions, $fakePlaceQuestions);
    echo '✓ Loaded ' . count($fakePlaceQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($fakePlaceQuestions);

    // ------
    // Generate custom questions
    // ------
    echo '<h2>Custom questions</h2>';
    $customQuestionsUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/questions.txt';
    echo "<div class='link'>Using $customQuestionsUrl</div>";
    $customQuestions = $this->generateCustomQuestions($customQuestionsUrl);
    self::addEntriesToArray($questions, $customQuestions);
    echo '✓ Loaded ' . count($customQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($customQuestions);

    return $questions;
  }

  private static function addEntriesToArray(array &$arr, array $entries): void {
    foreach ($entries as $entry) {
      $arr[] = $entry;
    }
  }

  private function generatePlaceQuestionTexts($iniData) {
    $placeQuestion = $this->validateIsTextWithinLength('place_question', $iniData, 10, 100);
    if (strpos($placeQuestion, '%place%') === false) {
      die("The text for key 'place_question' must have the placeholder %place% in order to include the place name!");
    }

    return [ 'question' => $placeQuestion ];
  }

  private function generatePlaceQuestions(string $file, $answer): array {
    $lines = explode("\n", $this->readFileOrThrow($file));

    $questions = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if (!empty($line)) {
        $question = new Question('PLACE', $line, $answer);
        $questions[] = $question;
      }
    }
    return $questions;
  }

  private function generateCustomQuestions($file) {
    $contents = $this->readFileOrThrow($file);

    $questions = [];
    $questionText = null;

    foreach (explode("\n", $contents) as $line) {
      $line = trim($line);
      if (empty($line)) {
        if ($questionText !== null) {
          throw new Exception('A question "' . $questionText . '" was followed by an empty line');
        }
      } else {
        if ($questionText === null) {
          $questionText = $line;
        } else {
          $question = new Question('custom', $questionText, $line);
          $question->validateCustomAnswers();
          $questions[] = $question;
          $questionText = null;
        }
      }
    }

    if ($questionText !== null) {
      throw new Exception('The final question "' . $questionText . '" appears not to have any answers');
    }
    return $questions;
  }
}
