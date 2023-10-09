<?php

class QuestionDraw {

  public int $drawId;
  public Question $question;
  public int $created;
  public int|null $solved;

  static function createFromDbRow(array $data): QuestionDraw {
    $draw = new QuestionDraw();
    $draw->drawId   = $data['id'];
    $draw->question = new Question($data['type'], $data['question'], $data['answer']);
    $draw->created  = $data['created'];
    $draw->solved   = $data['solved'];
    return $draw;
  }
}
