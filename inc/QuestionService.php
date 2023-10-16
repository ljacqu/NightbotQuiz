<?php

class QuestionService {

  private DatabaseHandler $db;

  function __construct(DatabaseHandler $db) {
    $this->db = $db;
  }

  function getLastQuestionDraw(int $ownerId): ?QuestionDraw {
    $drawValues = $this->db->getLastQuestionDraw($ownerId);
    if ($drawValues) {
      return QuestionDraw::createFromDbRow($drawValues);
    }
    return null;
  }

  function drawNewQuestion(int $ownerId, int $skipPastQuestions): ?Question {
    $questionValues = $this->db->drawNewQuestion($ownerId, $skipPastQuestions);
    if ($questionValues) {
      return new Question($questionValues['type'], $questionValues['question'], $questionValues['answer']);
    }
    return null;
  }

  function setCurrentDrawAsResolved(int $drawId): void {
    $this->db->setCurrentDrawAsSolved($drawId);
  }

  function getCorrectAnswers(QuestionDraw $draw): array {
    return $this->db->getCorrectAnswers($draw->drawId, $draw->question->answer);
  }

  function saveLastQuestionQuery(int $ownerId, int $drawId): void {
    $this->db->saveLastQuestionQuery($ownerId, $drawId);
  }
}
