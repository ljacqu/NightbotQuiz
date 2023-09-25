<?php

class Question {

  public $questionTypeId;
  public $question;
  public $answers;
  public $textAnswer; // First answer option, in original casing to show in responses

  function __construct(string $questionTypeId, string $question) {
    $this->questionTypeId = $questionTypeId;
    $this->question = $question;
  }

  static function __set_state(array $arr) {
    $question = new Question($arr['questionTypeId'], $arr['question']);
    $question->answers    = $arr['answers'];
    $question->textAnswer = $arr['textAnswer'];
    return $question;
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
