<?php

class CustomQuestionType extends QuestionType {

  function generateKey(Question $question): string {
    return md5('cust_' . $question->question);
  }

  function getPossibleAnswers(Question $question): array {
    return explode(',', $question->answer);
  }

  function generateQuestionText(Question $question): string {
    return $question->question;
  }

  function generateResolutionText(Question $question): string {
    return 'The previous answer was: ' . $this->generateIsolatedAnswerText($question);
  }

  function generateIsolatedAnswerText(Question $question): string {
    $firstAnswer = substr($question->answer, 0, strpos($question->answer, ','));
    return ucfirst($firstAnswer);
  }

  function processAnswer(Question $question, string $answerLower): Answer {
    $correctAnswers = explode(',', $question->answer);
    if (in_array($answerLower, $correctAnswers)) {
      return Answer::forCorrectAnswer($answerLower);
    }
    return Answer::forWrongAnswer($answerLower, false);
  }

  function generateCategory(Question $question): ?string {
    return null;
  }
}
