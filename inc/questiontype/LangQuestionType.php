<?php

class LangQuestionType extends QuestionType {

  private $languageData;

  function __construct() {
    $this->languageData = json_decode(file_get_contents(__DIR__ . '/../../gen/ent_languages.json'), true);
  }

  function generateQuestionText(Question $question): string {
    return 'Guess the language: ' . $question->question;
  }

  function processAnswer(Question $question, string $answerLower): Answer {
    $givenLanguage = $this->resolveLanguage($answerLower);
    if ($givenLanguage === null) {
      return $this->handleUnknownAnswer($answerLower);
    } else if ($givenLanguage['aliases'][0] === $question->answer) {
      return Answer::forCorrectAnswer($givenLanguage['aliases'][0], $givenLanguage['name']);
    } else {
      return Answer::forWrongAnswer($givenLanguage['aliases'][0], $givenLanguage['name']);
    }
  }

  private function handleUnknownAnswer(string $answerLower): Answer {
    if ($answerLower === 'asian' || $answerLower === 'african' || $answerLower === 'american'
        || $answerLower === 'indian' || $answerLower === 'swiss') {
      return Answer::forUnknownAnswer($answerLower, Utils::getRandomText('cmonBruh', 'LUL', 'Kappa'));
    } else if ($answerLower === 'france' || $answerLower === 'italy' || $answerLower === 'germany'
               || $answerLower === 'spain' || $answerLower === 'greece' || $answerLower === 'japan'
               || $answerLower === 'china') {
      $smile = Utils::getRandomText('cmonBruh', 'Kappa', 'monkaS', 'NotLikeThis');
      $langPlease = Utils::getRandomText('Guess a language', 'Please answer with a language',
        'We\'re looking for a language!');
      return Answer::forUnknownAnswer($answerLower, "That's a country $smile $langPlease");
    } else if ($answerLower === 'idk' || $answerLower === 'what' || $answerLower === 'uhh'
               || $answerLower === 'wtf' || $answerLower === 'oof') {
      $smile = Utils::getRandomText(':)', ';)', 'Kappa', 'GoldPLZ');
      return Answer::forUnknownAnswer($answerLower, "Well, it's a guessing game... $smile");
    }
    return Answer::forUnknownAnswer($answerLower, "Unknown language. Run !langs to see the choices");
  }

  private function resolveLanguage(string $answerLower): ?array {
    if (isset($this->languageData[$answerLower])) {
      return $this->languageData[$answerLower];
    }

    foreach ($this->languageData as $lang) {
      if ($answerLower === strtolower($lang['name'])) {
        return $lang;
      } else if (in_array($answerLower, $lang['aliases'])) {
        return $lang;
      }
    }
    return null;
  }

  function generateResolutionText(Question $question): string {
    $language = $this->generateIsolatedAnswerText($question);
    return Utils::getRandomText(
      "The previous text was in $language",
      "The previous language was $language",
      "The text before was in $language",
      "The language before was $language");
  }

  function generateIsolatedAnswerText(Question $question, $answer=null): string {
    $language = $this->languageData[ ($answer ?? $question->answer) ];
    return $language['name'];
  }

  function generateKey(Question $question): string {
    return 'lng_' . substr(md5($question->question), 0, 16);
  }

  function generateCategory(Question $question): ?string {
    return $question->answer;
  }

  function getAllPossibleAnswers(): array {
    $answers = [];
    foreach ($this->languageData as $code => $languageEntry) {
      $answers[] = ['code' => $code, 'text' => $languageEntry['name']];
    }

    // Language entries are sorted by code internally; return the entries sorted by name.
    usort($answers, function ($a, $b) {
      return strcmp($a['text'], $b['text']);
    });
    return $answers;
  }
}
