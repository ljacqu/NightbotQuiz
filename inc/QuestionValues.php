<?php
  
/**
 * Contains all question values, including technical fields—used when interacting 
 * with the database rows of the questions.
 */
class QuestionValues extends Question {

  public string $key;
  public ?string $category;
  
  function __construct(Question $question, string $key, ?string $category) {
    parent::__construct($question->questionTypeId, $question->question, $question->answer);
    $this->key = $key;
    $this->category = $category;
  }
}
