<?php

class QuestionType {

  private $textAnswer;
  private $possibleAnswers;

  function __construct(...$possibleAnswers) {
    if (empty($possibleAnswers)) {
      throw new Exception('Possible answers may not be empty!');
    }

    $this->textAnswer = $possibleAnswers[0];
    $this->possibleAnswers = $possibleAnswers;
  }

  function getTextAnswer() {
    return $this->textAnswer;
  }

  function setTextAnswer($textAnswer) {
    $this->textAnswer = $textAnswer;
  }

  function getPossibleAnswers() {
    return $this->possibleAnswers;
  }
}
