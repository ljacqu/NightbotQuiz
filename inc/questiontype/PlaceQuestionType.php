<?php

class PlaceQuestionType extends QuestionType {

  private array $questionText;

  function __construct() {
    $placeTexts = json_decode(file_get_contents(__DIR__ . '/../../gen/qt_place_texts.json'), true);
    $this->questionText = $placeTexts;
  }

  function generateKey(Question $question): string {
    return 'plc_' . substr(md5($question->question . $question->answer), 0, 16);
  }

  function generateQuestionText(Question $question): string {
    return str_replace('%place%', $question->question, $this->questionText['question']);
  }

  function generateResolutionText(Question $question): string {
    if ($question->answer === 'yes') {
      return str_replace('%place%', $question->question, $this->questionText['resolution.yes']);
    }
    return str_replace('%place%', $question->question, $this->questionText['resolution.no']);
  }

  function generateIsolatedAnswerText(Question $question, $answer=null): string {
    $answerToConvert = $answer ?? $question->answer;
    if ($answerToConvert === 'yes') {
      return 'Yes';
    }
    return 'No';
  }

  function processAnswer(Question $question, string $answerLower): Answer {
    $validAnswer = ($answerLower === 'yes' || $answerLower === 'y') ? 'yes' : null;
    if ($answerLower === 'no' || $answerLower === 'n') {
      $validAnswer = 'no';
    }
    if ($validAnswer) {
      return $validAnswer === $question->answer
        ? Answer::forCorrectAnswer($validAnswer)
        : Answer::forWrongAnswer($validAnswer);
    }
    return Answer::forUnknownAnswer($answerLower, Answer::INVALID_USE_DEFAULT_ERROR);
  }

  function generateCategory(Question $question): ?string {
    return null;
  }

  function getAllPossibleAnswers(): array {
    return [
      ['code' => 'yes', 'text' => 'Yes'],
      ['code' => 'no', 'text' => 'No']
    ];
  }
}
