<?php

class QuestionService {

  private DatabaseHandler $db;

  function __construct(DatabaseHandler $db) {
    $this->db = $db;
  }

  function getLastQuestionDraw(int $ownerId): QuestionDraw|null {
    $drawValues = $this->db->getLastQuestionDraw($ownerId);
    if ($drawValues) {
      return QuestionDraw::createFromDbRow($drawValues);
    }
    return null;
  }

  function drawNewQuestion(int $ownerId, int $skipPastQuestions): Question|null {
    $questionValues = $this->db->drawNewQuestion($ownerId, $skipPastQuestions);
    if ($questionValues) {
      return new Question($questionValues['type'], $questionValues['question'], $questionValues['answer']);
    }
    return null;
  }
}
