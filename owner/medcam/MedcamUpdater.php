<?php

class MedcamUpdater extends Updater {

  function generateQuestions(): array {
    $this->saveTexts();
    $countriesByCode = $this->saveCountries();

    return array_merge(
      $this->createRealPlaceQuestions(),
      $this->createFakePlaceQuestions(),
      $this->createCountryQuestions($countriesByCode),
      $this->createCustomQuestions());
  }

  private function saveTexts(): void {
    echo '<h2>Texts</h2>';
    $textsUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/texts.ini';
    echo "<div class='link'>Using $textsUrl</div>";

    $iniFileContents = $this->readFileOrThrow($textsUrl);
    $iniTexts = parse_ini_string($iniFileContents);
    if ($iniTexts === false) {
      die("Failed to read '$textsUrl'. Please make sure it has valid INI syntax.");
    }
    $placeTexts = $this->generatePlaceQuestionTexts($iniTexts);
    $this->writeJsonOrFail('../gen/qt_place_texts.json', $placeTexts);
    echo '✓ Saved the texts for place questions successfully.';

    $countryTexts = $this->generateCountryQuestionTexts($iniTexts);
    $this->writeJsonOrFail('../gen/qt_country_texts.json', $countryTexts);
    echo '<br />✓ Saved the texts for country questions successfully.';
  }

  private function saveCountries(): array {
    echo '<h2>Country definitions</h2>';
    $countryUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/countries.txt';
    echo "<div class='link'>Using $countryUrl</div>";

    $lines = explode("\n", $this->readFileOrThrow($countryUrl));
    $countriesByCode = [];
    $allAliases = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if (empty($line) || $line[0] === '#') {
        continue;
      }

      $countryValues = $this->createCountryValues($line);
      $code = $countryValues['code'];
      unset($countryValues['code']);
      if (isset($allAliases[$code])) {
        throw new Exception('The alias "' . $code . '" is used for multiple countries');
      }
      $allAliases[$code] = true;

      $countriesByCode[$code] = $countryValues;
    }

    $this->writeJsonOrFail('../gen/ent_countries.json', $countriesByCode);
    echo '✓ Saved ' . count($countriesByCode) . ' country definitions.';
    return $countriesByCode;
  }

  private function createRealPlaceQuestions(): array {
    echo '<h2>Real place questions</h2>';
    $realPlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_real_names.txt';
    echo "<div class='link'>Using $realPlacesUrl</div>";
    $realPlaceQuestions = $this->generatePlaceQuestions($realPlacesUrl, 'yes');
    echo '✓ Loaded ' . count($realPlaceQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($realPlaceQuestions);
    return $realPlaceQuestions;
  }

  private function createFakePlaceQuestions(): array {
    echo '<h2>Fake place questions</h2>';
    $fakePlacesUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/place_fake_names.txt';
    echo "<div class='link'>Using $fakePlacesUrl</div>";
    $fakePlaceQuestions = $this->generatePlaceQuestions($fakePlacesUrl, 'no');
    echo '✓ Loaded ' . count($fakePlaceQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($fakePlaceQuestions);
    return $fakePlaceQuestions;
  }

  private function createCountryQuestions(array $countriesByCode): array {
    echo '<h2>Country questions</h2>';
    $countryQuestionsUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/country_question.txt';
    echo "<div class='link'>Using $countryQuestionsUrl</div>";
    $countryQuestions = $this->generateCountryQuestions($countryQuestionsUrl, $countriesByCode);
    echo '✓ Loaded ' . count($countryQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($countryQuestions);
    return $countryQuestions;
  }

  private function createCustomQuestions(): array {
    echo '<h2>Custom questions</h2>';
    $customQuestionsUrl = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/data/questions.txt';
    echo "<div class='link'>Using $customQuestionsUrl</div>";
    $customQuestions = $this->generateCustomQuestions($customQuestionsUrl);
    echo '✓ Loaded ' . count($customQuestions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($customQuestions);
    return $customQuestions;
  }

  // -------
  // UTILS
  // -------

  private static function addEntriesToArray(array &$arr, array $entries): void {
    foreach ($entries as $entry) {
      $arr[] = $entry;
    }
  }

  private function generatePlaceQuestionTexts(array $iniData): array {
    $placeQuestion = $this->validateIsTextWithinLength('place_question', $iniData, 10, 100);
    $this->validateHasAllPlaceholders('place_question', $placeQuestion, '%place%');

    $realPlaceResolution = $this->validateIsTextWithinLength('place_resolution.yes', $iniData, 10, 100);
    $this->validateHasAllPlaceholders('place_resolution.yes', $realPlaceResolution, '%place%');

    $fakePlaceResolution = $this->validateIsTextWithinLength('place_resolution.no', $iniData, 10, 100);
    $this->validateHasAllPlaceholders('place_resolution.no', $fakePlaceResolution, '%place%');

    return [
      'question' => $placeQuestion,
      'resolution.yes' => $realPlaceResolution,
      'resolution.no' => $fakePlaceResolution,
    ];
  }

  private function generateCountryQuestionTexts(array $iniData): array {
    $populationQuestion = $this->validateIsTextWithinLength('population_question', $iniData, 10, 100);
    $this->validateHasAllPlaceholders('population_question', $populationQuestion, '%country1%', '%country2%');

    $populationResolution = $this->validateIsTextWithinLength('population_resolution', $iniData, 10, 100);
    $this->validateHasAllPlaceholders('population_resolution', $populationResolution, '%country1%');

    $surfaceQuestion = $this->validateIsTextWithinLength('surface_question', $iniData, 10, 100);
    $this->validateHasAllPlaceholders('surface_question', $surfaceQuestion, '%country1%', '%country2%');

    $surfaceResolution = $this->validateIsTextWithinLength('surface_resolution', $iniData, 10, 100);
    $this->validateHasAllPlaceholders('surface_resolution', $surfaceResolution, '%country1%');

    return [
      'population_question'   => $populationQuestion,
      'population_resolution' => $populationResolution,
      'surface_question'      => $surfaceQuestion,
      'surface_resolution'    => $surfaceResolution,
    ];
  }

  private function createCountryValues(string $line): array {
    // Name, aliases, population, surface
    $parts = explode(',', $line);
    if (count($parts) !== 4) {
      throw new Exception("The country definition line '$line' has an unexpected number of fields");
    }
    $name = trim($parts[0]);
    $population = (int) $parts[2];
    if ($population <= 0) {
      throw new Exception("The population for country '$name' is zero or negative!");
    }
    $surface = (int) $parts[3];
    if ($surface <= 0) {
      throw new Exception("The surface for country '$name' is zero or negative!");
    }

    $aliases = [];
    foreach (explode(';', $parts[1]) as $alias) {
      $alias = strtolower(trim($alias));
      if (empty($alias)) {
        throw new Exception("Found an empty alias for country '$name'");
      }
      $aliases[] = strtolower(trim($alias));
    }
    if (empty($alias)) {
      throw new Exception("There is no alias for country '$name'. At least one is required.");
    }

    return [
      'name' => $name,
      'code' => $aliases[0],
      'aliases' => $aliases,
      'population' => $population,
      'surface' => $surface
    ];
  }

  private function generatePlaceQuestions(string $file, string $answer): array {
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

  private function generateCountryQuestions(string $file, array $countriesByCode): array {
    $lines = explode("\n", $this->readFileOrThrow($file));

    $questions = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if (!empty($line) && $line[0] !== '#') {
        $parts = explode(' ', $line);
        if (count($parts) !== 3) {
          throw new Exception('Country question "' . $line . '" does not consist of three parts');
        }
        $type = $parts[0];
        $country1 = $parts[1];
        $country2 = $parts[2];
        if ($type !== 'population' && $type !== 'surface') {
          throw new Exception("Unknown question type '$type' in line '$line'");
        }
        if (!isset($countriesByCode[$country1]) || !isset($countriesByCode[$country2])) {
          throw new Exception("Unknown country referenced: '$country1' or '$country2'");
        } else if ($country1 === $country2) {
          throw new Exception("The question '$line' uses the same country twice!");
        }

        $country1Value = $countriesByCode[$country1][$type]; // $type is population or surface
        $country2Value = $countriesByCode[$country2][$type];
        if ($country1Value === $country2Value) {
          throw new Exception("The property $type for countries $country1 and $country2 is the same!");
        }
        $answer = ($country1Value > $country2Value) ? $country1 : $country2;
        $questions[] = new Question(strtoupper($type), "$country1,$country2", $answer);
      }
    }
    return $questions;
  }

  private function generateCustomQuestions(string $file): array {
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
