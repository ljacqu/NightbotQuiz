<?php

abstract class CountryBasedQuestionType extends QuestionType {

  private array $questionTexts;
  private array $countriesByCode;

  function __construct() {
    $this->questionTexts = json_decode(file_get_contents(__DIR__ . '/../../gen/qt_country_texts.json'), true);
    $this->countriesByCode = json_decode(file_get_contents(__DIR__ . '/../../gen/ent_countries.json'), true);
  }

  function getPossibleAnswers(Question $question): array {
    $countryAnswer = $this->countriesByCode[$question->answer];
    $answers = $countryAnswer['aliases'];
    $answers[] = strtolower($countryAnswer['name']);
    return $answers;
  }

  function generateQuestionText(Question $question): string {
    $countries = explode(',', $question->question);
    $country1 = $this->countriesByCode[$countries[0]]['name'];
    $country2 = $this->countriesByCode[$countries[1]]['name'];

    return str_replace(
      ['%country1%', '%country2%'],
      [$country1, $country2],
      $this->questionTexts[$this->getQuestionTextKey()]);
  }

  protected abstract function getQuestionTextKey(): string;
  protected abstract function getResolutionTextKey(): string;

  function generateResolutionText(Question $question): string {
    $countries = explode(',', $question->question);
    $answerCode = $question->answer;
    $otherCode = $countries[0] === $answerCode ? $countries[1] : $countries[0];

    $answerName = $this->countriesByCode[$answerCode]['name'];
    $otherName  = $this->countriesByCode[$otherCode]['name'];

    return str_replace(
      ['%country1%', '%country2%'],
      [$answerName, $otherName],
      $this->questionTexts[$this->getResolutionTextKey()]);

  }

  function generateIsolatedAnswerText(Question $question): string {
    $answerCode = $question->answer;
    return $this->countriesByCode[$answerCode]['name'];
  }

  function processAnswer(Question $question, string $answerLower): Answer {
    $country = $this->countriesByCode[$answerLower] ?? null;
    if (!$country) {
      return Answer::forUnknownAnswer($answerLower);
    }

    // substr with strpos probably more performant, but what if we only have one alias?
    $answerNormalized = explode(',', $country['aliases'])[0];
    return Answer::forWrongAnswer($answerNormalized, false);
  }

  function generateCategory(Question $question): ?string {
    return null;
  }
}
