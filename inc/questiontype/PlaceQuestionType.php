<?php

class PlaceQuestionType extends QuestionType {

  private array $questionText;

  function __construct() {
    $placeTexts = json_decode(file_get_contents(__DIR__ . '/../../gen/qt_place_texts.json'), true);
    $this->questionText = $placeTexts;
  }

  function generateKey(Question $question): string {
    return md5('place_' . $question->question . $question->answer);
  }

  function getPossibleAnswers(Question $question): array {
    if ($question->answer === 'yes') {
      return ['yes', 'y'];
    }
    return ['no', 'n'];
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

  function generateIsolatedAnswerText(Question $question): string {
    if ($question->answer === 'yes') {
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
        : Answer::forWrongAnswer($validAnswer, true);
    }
    return Answer::forUnknownAnswer($answerLower);
  }

  function generateCategory(Question $question): ?string {
    return null;
  }
}
