<?php

class OwnerPollValues {

  public int $ownerId;
  public string $activeMode;

  public int $timerUnsolvedQuestionWait;
  public int $timerSolvedQuestionWait;
  public int $timerLastAnswerWait;
  public int $userNewWait;
  public int $historyAvoidLastAnswers;

  static function createFromDbRow(array $data) {
    $values = new OwnerPollValues();

    $values->ownerId = $data['id'];
    $values->activeMode = $data['active_mode'];
    $values->timerUnsolvedQuestionWait = $data['timer_unsolved_question_wait'];
    $values->timerSolvedQuestionWait = $data['timer_solved_question_wait'];
    $values->timerLastAnswerWait = $data['timer_last_answer_wait'];
    $values->userNewWait = $data['user_new_wait'];
    $values->historyAvoidLastAnswers = $data['history_avoid_last_answers'];
    return $values;
  }
}
