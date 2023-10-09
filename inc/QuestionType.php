<?php

abstract class QuestionType {

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

  abstract function generateKey0(Question $question): string;

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

  static function generateQuestionText(Question $question, string $root): string {
    switch ($question->questionTypeId) {
      case 'PLACE':
        return (new PlaceQuestionType($root))->createQuestionText($question);
      case 'custom':
        return $question->question;
      default:
        throw new Exception('Unknown question type: ' . $question->questionTypeId);
    }
  }


  static function processAnswer(Question $question, string $answerLower): Answer {
    if ($question->questionTypeId === 'PLACE') {
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
    } else if ($question->questionTypeId === 'custom') {
      $correctAnswers = explode(',', $question->answer);
      if (in_array($answerLower, $correctAnswers)) {
        return Answer::forCorrectAnswer($answerLower);
      }
      return Answer::forWrongAnswer($answerLower, false);
    } else {
      throw new Exception('Unknown question type: ' . $question->questionTypeId);
    }
  }
}
