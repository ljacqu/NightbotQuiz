<?php

class MedUpdater extends Updater {

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
    $data_questionTypeTexts = $this->generatePlaceQuestionTexts($iniTexts);

    $fh = fopen('../gen/question_type_texts.php', 'w') or die('Failed to write to question_type_texts.php');
    fwrite($fh, '<?php $data_questionTypeTexts = ' . var_export($data_questionTypeTexts, true) . ';');
    fclose($fh);
    echo '✓ Saved the question texts successfully.';


    // Real places
    echo '<h2>Real place questions</h2>';
    $realPlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_real_names.txt';
    echo "<div class='link'>Using $realPlacesUrl</div>";
    $realPlaceQuestions = $this->generatePlaceQuestions($realPlacesUrl, 'yes');
    self::addEntriesToArray($questions, $realPlaceQuestions);
    echo '✓ Loaded ' . count($realPlaceQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($realPlaceQuestions, $data_questionTypeTexts);

    // Fake places
    echo '<h2>Fake place questions</h2>';
    $fakePlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_fake_names.txt';
    echo "<div class='link'>Using $fakePlacesUrl</div>";
    $fakePlaceQuestions = $this->generatePlaceQuestions($fakePlacesUrl, 'no');
    self::addEntriesToArray($questions, $fakePlaceQuestions);
    echo '✓ Loaded ' . count($fakePlaceQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($fakePlaceQuestions, $data_questionTypeTexts);

    // ------
    // Generate custom questions
    // ------
    echo '<h2>Custom questions</h2>';
    $customQuestionsUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/questions.txt';
    echo "<div class='link'>Using $customQuestionsUrl</div>";
    $customQuestions = $this->generateCustomQuestions($customQuestionsUrl);
    self::addEntriesToArray($questions, $customQuestions);
    echo '✓ Loaded ' . count($customQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($customQuestions, $data_questionTypeTexts);

    return $questions;
  }

  private static function addEntriesToArray(&$arr, $entries) {
    foreach ($entries as $entry) {
      $arr[] = $entry;
    }
  }

  private function generatePlaceQuestionTexts($iniData) {
    $placeQuestion = $this->validateIsTextWithinLength('place_question', $iniData, 10, 100);
    if (strpos($placeQuestion, '%place%') === false) {
      die("The text for key 'place_question' must have the placeholder %place% in order to include the place name!");
    }

    $questionTexts = [
      'PLACE' => [
        'question' => $placeQuestion
      ],
      // TODO: REMOVE THESE PLACE TYPES
      'REAL_PLACE' => [
        'question' => $placeQuestion,
        'answers' => $this->validateIsNonEmptyCsvList('real_place_possible_answers', $iniData),
        'isolatedAnswer' => $this->validateIsTextWithinLength('real_place_isolated_answer', $iniData, 1, 100),
        'resolutionText' => $this->validateIsTextWithinLength('real_place_resolution_text', $iniData, 1, 100)
      ],
      'FAKE_PLACE' => [
        'question' => $placeQuestion,
        'answers' => $this->validateIsNonEmptyCsvList('fake_place_possible_answers', $iniData),
        'isolatedAnswer' => $this->validateIsTextWithinLength('fake_place_isolated_answer', $iniData, 1, 100),
        'resolutionText' => $this->validateIsTextWithinLength('fake_place_resolution_text', $iniData, 1, 100)
      ]
    ];

    return $questionTexts;
  }

  private function generatePlaceQuestions($file, $answer) {
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
          $currentQuestion->setAnswersFromCommaSeparatedText($line); // TODO
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
}
