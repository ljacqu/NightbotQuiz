<?php

class QuestionType {

  static function getPossibleAnswers(Question $question): array {
    if ($question->questionTypeId === 'PLACE') {
      if ($question->answer === 'yes') {
        return ['yes', 'y'];
      } else {
        return ['no', 'n'];
      }
    } else if ($question->questionTypeId === 'custom') {
      return explode(',', $question->answer);
    }
    throw new Exception('Unknown question type: ' . $question->questionTypeId);
  }

  static function generateKey(Question $question): string {
    switch ($question->questionTypeId) {
      case 'PLACE':
        return md5('place_' . $question->question . $question->answer);
      case 'custom':
        return md5('cust_' . $question->question);
      default:
        throw new Exception('Unknown question type: ' . $question->questionTypeId);
    }
  }
}
