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
