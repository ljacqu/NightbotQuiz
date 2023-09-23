<?php
  
class Question {

  public $question;
  public $answers;
  public $textAnswer; // First answer option, in original casing to show in responses

  function __construct($question) {
    $this->question = $question;
  }

  function setAnswersFromCommaSeparatedText($commaSeparatedAnswers) {
    $answers = [];
    $textAnswer = null;
    foreach (explode(',', $commaSeparatedAnswers) as $answer) {
      $answer = trim($answer);
      if (empty($answer)) {
        throw new Exception('Found empty answer option in line "' . $commaSeparatedAnswers . '"');
      }
      if ($textAnswer === null) {
        $textAnswer = $answer;
      }

      $answers[strtolower($answer)] = true;
    }
    if (empty($answers)) {
      throw new Exception('No answers were defined to set!');
    }

    $this->answers = array_map(
      function ($e) { return (string) $e; },
      array_keys($answers));
    $this->textAnswer = $textAnswer;
  }
}
