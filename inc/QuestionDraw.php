<?php

class QuestionDraw {

  public int $drawId;
  public Question $question;
  public int $created;
  public ?int $lastAnswer;
  public ?int $lastQuestionQuery;
  public ?int $solved;
  public ?int $timesQuestionQueried;
  public ?int $lastQuestionRepeat;

  static function createFromDbRow(array $data): QuestionDraw {
    $draw = new QuestionDraw();
    $draw->drawId            = $data['id'];
    $draw->question          = new Question($data['type'], $data['question'], $data['answer']);
    $draw->created           = $data['created'];
    $draw->lastAnswer        = $data['last_answer'];
    $draw->lastQuestionQuery = $data['last_question'];
    $draw->solved            = $data['solved'];
    $draw->timesQuestionQueried = $data['times_question_queried'];
    $draw->lastQuestionRepeat = $data['last_question_repeat'];
    return $draw;
  }
}
