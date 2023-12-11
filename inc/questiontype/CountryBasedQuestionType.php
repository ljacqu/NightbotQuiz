<?php

abstract class CountryBasedQuestionType extends QuestionType {

  private array $questionTexts;
  private array $countriesByCode;

  function __construct() {
    $this->questionTexts = json_decode(file_get_contents(__DIR__ . '/../../gen/qt_country_texts.json'), true);
    $this->countriesByCode = json_decode(file_get_contents(__DIR__ . '/../../gen/ent_countries.json'), true);
  }

  protected abstract function getQuestionTextKey(): string;

  protected abstract function getResolutionTextKey(): string;

  function generateQuestionText(Question $question): string {
    $countries = explode(',', $question->question);
    $country1 = $this->countriesByCode[$countries[0]]['name'];
    $country2 = $this->countriesByCode[$countries[1]]['name'];

    return str_replace(
      ['%country1%', '%country2%'],
      [$country1, $country2],
      $this->questionTexts[$this->getQuestionTextKey()]);
  }

  function generateResolutionText(Question $question): string {
    $countries = explode(',', $question->question);
    $answerCode = $question->answer;
    $otherCode = $countries[0] === $answerCode ? $countries[1] : $countries[0];

    $answerName = $this->countriesByCode[$answerCode]['name'];
    $otherName  = $this->countriesByCode[$otherCode]['name'];

    return str_replace(
      ['%country1%', '%country2%'],
      [ucfirst($answerName), $otherName],
      $this->questionTexts[$this->getResolutionTextKey()]);

  }

  function generateIsolatedAnswerText(Question $question, $answer=null): string {
    $answerCode = $answer ?? $question->answer;
    return $this->countriesByCode[$answerCode]['name'];
  }

  function processAnswer(Question $question, string $answerLower): Answer {
    $country = $this->resolveCountry($answerLower);
    if (!$country) {
      return Answer::forUnknownAnswer($answerLower, Answer::INVALID_USE_DEFAULT_ERROR);
    }

    $answerNormalized = $country['aliases'][0];
    $countryChoices = explode(',', $question->question);
    if (!in_array($answerNormalized, $countryChoices)) {
      $country1 = $this->countriesByCode[$countryChoices[0]]['name'];
      $country2 = $this->countriesByCode[$countryChoices[1]]['name'];
      $wrongCountryError = 'Please guess either ' . $country1 . ' or ' . $country2;
      return Answer::forUnknownAnswer($answerLower, $wrongCountryError);
    }

    return $answerNormalized === $question->answer
      ? Answer::forCorrectAnswer($answerNormalized, $country['name'])
      : Answer::forWrongAnswer($answerNormalized, $country['name']);
  }

  private function resolveCountry(string $answerLower): ?array {
    if (isset($this->countriesByCode[$answerLower])) {
      return $this->countriesByCode[$answerLower];
    }

    foreach ($this->countriesByCode as $country) {
      if ($answerLower === strtolower($country['name'])) {
        return $country;
      } else if (in_array($answerLower, $country['aliases'], true)) {
        return $country;
      }
    }
    return null;
  }

  function generateCategory(Question $question): ?string {
    return null;
  }

  function getAllPossibleAnswers(): array {
    $answers = [];
    foreach ($this->countriesByCode as $code => $countryEntry) {
      $answers[] = ['code' => $code, 'text' => $countryEntry['name']];
    }
    return $answers;
  }
}
