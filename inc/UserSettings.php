<?php

class UserSettings {

  public $activeMode;

  public $timerUnsolvedQuestionWait;
  public $timerSolvedQuestionWait;
  public $timerLastAnswerWait;
  public $userNewWait;

  public $historyDisplayEntries;
  public $historyAvoidLastAnswers;

  static function createFromDbRow(array $data) {
    $settings = new UserSettings();

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
