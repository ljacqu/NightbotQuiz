<?php

class LangQuestionType extends QuestionType {

  private $languageData;

  function __construct() {
    $this->languageData = json_decode(file_get_contents(__DIR__ . '/../../gen/ent_languages.json'), true);
  }

  function getPossibleAnswers(Question $question): array {
    $language = $this->languageData[$question->answer];
    $possibilities = $language['aliases'];
    $possibilities[] = strtolower($language['name']);
    return $possibilities;
  }

  function generateQuestionText(Question $question): string {
    return 'Guess the language: ' . $question->question;
  }

  function processAnswer(Question $question, string $answerLower): Answer {
    $givenLanguage = $this->resolveLanguage($answerLower);
    if ($givenLanguage === null) {
      return Answer::forUnknownAnswer($answerLower);
    } else if ($givenLanguage['aliases'][0] === $question->answer) {
      return Answer::forCorrectAnswer($givenLanguage['aliases'][0]);
    } else {
      return Answer::forWrongAnswer($answerLower, false);
    }
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
    return "The previous text was in " . $this->generateIsolatedAnswerText($question);
  }

  function generateIsolatedAnswerText(Question $question): string {
    $language = $this->languageData[$question->answer];
    return $language['name'];
  }

  function generateKey(Question $question): string {
    return 'lng_' . substr(md5($question->question), 0, 16);
  }

  function generateCategory(Question $question): ?string {
    return $question->answer;
  }
}
