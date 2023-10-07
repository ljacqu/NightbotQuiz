<?php

class PlaceQuestionType extends QuestionType {

  private string $questionText;

  function __construct($root) {
    require $root . '/gen/qt_place_texts.php';

    $this->questionText = $data_questionTypeTexts['PLACE']['question'];
  }

  function generateKey0(Question $question): string {
    return md5('place_' . $question->question . $question->answer);
  }

  function createQuestionText(Question $question): string {
    return str_replace('%place%', $question->question, $this->questionText);
  }
}
