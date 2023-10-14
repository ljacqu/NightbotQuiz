<?php

class Question {

  public string $questionType;
  public string $question;
  public string $answer;

  function __construct(string $questionType, string $question, string $answer) {
    $this->questionType = $questionType;
    $this->question = $question;
    $this->answer = $answer;
  }

  function validateCustomAnswers(): void {
    $answers = [];
    foreach (explode(',', $this->answer) as $answer) {
      $answer = trim($answer);
      if (empty($answer)) {
        throw new Exception('Found empty answer option in line "' . $this->answer . '"');
      }
      $answers[$answer] = true;
    }

    $answerEntries = array_map(
      function ($e) { return (string) $e; },
      array_keys($answers));
    $this->answer = implode(',', $answerEntries);
  }
}
