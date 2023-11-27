<?php

class HighwayUpdater extends Updater {

  function generateQuestions(): array {
    $languagesByCode = $this->saveLanguages();
    $this->saveLangDemoTexts($languagesByCode);

    return $this->generateLangQuestions($languagesByCode);
  }

  private function saveLanguages(): array {
    $url = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/langs/languages.txt';
    echo "<h2>Saving languages</h2>";
    self::outputSourceLinkDiv($url);
    $langFile = $this->readFileOrThrow($url);

    $languagesByCode = [];
    foreach (explode("\n", $langFile) as $line) {
      $line = trim($line);
      if (!empty($line)) {
        $entryInfo = $this->createLanguageEntry($line);
        $code = $entryInfo['code'];
        if (isset($languagesByCode[$code])) {
          throw new Exception('The language code "' . $code . '" is used multiple times!');
        }
        $languagesByCode[$code] = $entryInfo['entry'];
      }
    }
    echo 'Loaded ' . count($languagesByCode) . ' languages';

    $usedAliases = [];
    foreach ($languagesByCode as $lang) {
      foreach ($lang['aliases'] as $alias) {
        if (isset($usedAliases[$alias])) {
          throw new Exception("The alias $alias is duplicated (one language is " . $lang['name'] . ")");
        }
        $usedAliases[$alias] = true;
      }
    }

    $this->writeJsonOrFail('../gen/ent_languages.json', $languagesByCode);
    echo '<br />✓ Saved language definitions successfully.';
    return $languagesByCode;
  }

  private function saveLangDemoTexts(array $languagesByCode): void {
    $url = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/langs/demo_texts.txt';
    echo "<h2>Saving language sample sentences</h2>";
    self::outputSourceLinkDiv($url);
    $demoFile = $this->readFileOrThrow($url);

    $demoSentencesByCode = [];
    foreach (explode("\n", $demoFile) as $line) {
      $line = trim($line);
      if (!empty($line)) {
        $code = substr($line, 0, 2);
        $text = substr($line, 3);
        if (isset($demoSentencesByCode[$code])) {
          echo "Warning: Multiple texts exist for language '$code'<br />";
        }
        if (!isset($languagesByCode[$code])) {
          echo "Warning: Sample sentence with unknown language '$code'<br />";
        }
        $languagesByCode[$code] = $text;
      }
    }

    echo 'Loaded ' . count($languagesByCode) . ' sample sentences';
    $this->writeJsonOrFail('../gen/ent_languages_sample.json', $languagesByCode);
    echo '<br />✓ Saved language samples successfully.';
  }

  private function generateLangQuestions(array $languagesByCode): array {
    $url = 'https://raw.githubusercontent.com/ljacqu/NightbotQuiz/langs/texts.txt';
    echo "<h2>Saving language questions</h2>";
    self::outputSourceLinkDiv($url);
    $questionFile = $this->readFileOrThrow($url);

    $questions = [];
    foreach (explode("\n", $questionFile) as $line) {
      $line = trim($line);
      if (!empty($line) && $line[0] !== '#') {
        $questions[] = $this->createLanguageQuestion($line, $languagesByCode);
      }
    }

    echo '✓ Loaded ' . count($questions) . ' questions';
    echo '<br />' . $this->generateQuestionPreview($questions);
    return $questions;
  }

  // ------------
  // Helpers
  // ------------

  private function createLanguageEntry(string $langLine): array {
    $langParts = explode('|', $langLine);
    if (count($langParts) !== 4) {
      throw new Exception('Invalid language line "' . $langLine . '", expected four parts!');
    }

    $name = trim($langParts[1]);
    $group = trim($langParts[2]);

    $code = trim($langParts[0]);
    if (!preg_match('/^[a-z]{2}$/', $code)) {
      throw new Exception('Invalid language code in line "' . $langLine . '", expected two letters a-z');
    }
    $aliases = explode(',', trim($langParts[3]));
    array_unshift($aliases, $code);

    foreach ($aliases as $k => $alias) {
      $alias = trim($alias);
      if (empty($alias)) {
        throw new Exception('Found an empty alias for language "' . $code . '"');
      }
      $aliases[$k] = strtolower($alias);
    }

    return [
      'code' => $code,
      'entry' => [
        'name' => $name,
        'group' => $group,
        'aliases' => $aliases
      ]
    ];
  }

  private function createLanguageQuestion(string $line, array $languagesByCode): Question {
    $code = substr($line, 0, 2);
    $text = trim(substr($line, 3));

    if ($line[2] !== ':') { // Sanity check
      throw new Exception("Malformed line '$line'");
    } else if (empty($text)) {
      throw new Exception("The question in line '$line' was empty");
    } else if (!isset($languagesByCode[$code])) {
      throw new Exception("Found question for code '$code', but this language code is unknown");
    }

    return new Question('LANG', $text, $code);
  }
}
