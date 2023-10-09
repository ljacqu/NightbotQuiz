<?php

class Question {

  public $questionTypeId;
  public $question;
  public $answer;

  function __construct(string $questionTypeId, string $question, string $answer) {
    $this->questionTypeId = $questionTypeId;
    $this->question = $question;
    $this->answer = $answer;
  }

  function validateCustomAnswers(): void {
    $answers = [];
    foreach (explode(',', $this->answer) as $answer) {
      if (empty($answer)) {
        throw new Exception('Found empty answer option in line "' . $this->answer . '"');
      }
      $answers[strtolower($answer)] = true;
    }

    $answerEntries = array_map(
      function ($e) { return (string) $e; },
      array_keys($answers));
    $this->answer = implode(',', $answerEntries);
  }
}
