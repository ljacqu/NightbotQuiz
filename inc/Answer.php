<?php

class Answer {

  public string|null $answer;
  public bool $isCorrect;
  public bool $resolvesQuestion;
  public bool $invalid;

  function __construct(string|null $answer, bool $isCorrect, bool $resolvesQuestion, bool $invalid) {
    $this->answer = $answer;
    $this->isCorrect = $isCorrect;
    $this->resolvesQuestion = $resolvesQuestion;
    $this->invalid = $invalid;
  }

  static function forCorrectAnswer(string $answer) {
    return new Answer($answer, true, true, false);
  }

  static function forWrongAnswer(string $answer, bool $resolvesQuestion) {
    return new Answer($answer, false, $resolvesQuestion, false);
  }

  static function forUnknownAnswer(string $answer) {
    return new Answer($answer, false, false, true);
  }
}
