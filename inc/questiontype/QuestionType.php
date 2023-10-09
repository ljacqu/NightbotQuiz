<?php

abstract class QuestionType {

  static function getType(string $type): QuestionType {
    switch ($type) {
      case 'PLACE':
        require_once __DIR__ . '/PlaceQuestionType.php';
        return new PlaceQuestionType();
      case 'custom':
        require_once __DIR__ . '/CustomQuestionType.php';
        return new CustomQuestionType();
      default:
        throw new Exception('Unknown question type: ' . $type);
    }
  }

  abstract function getPossibleAnswers(Question $question): array;

  abstract function generateQuestionText(Question $question): string;

  abstract function processAnswer(Question $question, string $answerLower): Answer;

  abstract function generateKey(Question $question): string;

  abstract function generateCategory(Question $question): ?string;

}
