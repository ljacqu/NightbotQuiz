<?php

class CustomQuestionType extends QuestionType {

  function generateKey0(Question $question): string {
    return md5('cust_' . $question->question);
  }
}
