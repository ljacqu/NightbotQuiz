<?php

class OwnerSettings {

  public int $ownerId;
  public string $ownerName;
  public string $activeMode;

  public int $timerUnsolvedQuestionWait;
  public int $timerSolvedQuestionWait;
  public int $timerLastAnswerWait;
  public int $userNewWait;

  public int $historyDisplayEntries;
  public int $historyAvoidLastAnswers;

  static function createFromDbRow(array $data): OwnerSettings {
    $settings = new OwnerSettings();

    $settings->ownerId                   = $data['id'];
    $settings->ownerName                 = $data['name'];
    $settings->activeMode                = $data['active_mode'];
    $settings->timerUnsolvedQuestionWait = $data['timer_unsolved_question_wait'];
    $settings->timerSolvedQuestionWait   = $data['timer_solved_question_wait'];
    $settings->timerLastAnswerWait       = $data['timer_last_answer_wait'];
    $settings->userNewWait               = $data['user_new_wait'];
    $settings->historyDisplayEntries     = $data['history_display_entries'];
    $settings->historyAvoidLastAnswers   = $data['history_avoid_last_answers'];
    return $settings;
  }
}