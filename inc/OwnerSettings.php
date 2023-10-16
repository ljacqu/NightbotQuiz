<?php

class OwnerSettings {

  public int $ownerId;
  public string $ownerName;
  public string $activeMode;
  public bool $timerSolveCreatesNewQuestion;
  public int $debugMode;

  public int $timerUnsolvedQuestionWait;
  public int $timerSolvedQuestionWait;
  public int $timerLastAnswerWait;
  public int $timerLastQuestionQueryWait;
  public int $userNewWait;

  public int $historyDisplayEntries;
  public int $historyAvoidLastAnswers;

  static function createFromDbRow(array $data): OwnerSettings {
    $settings = new OwnerSettings();

    $settings->ownerId                    = $data['id'];
    $settings->ownerName                  = $data['name'];
    $settings->activeMode                 = $data['active_mode'];
    $settings->timerSolveCreatesNewQuestion = $data['timer_solve_creates_new_question'];
    $settings->debugMode                  = $data['debug_mode'];
    $settings->timerUnsolvedQuestionWait  = $data['timer_unsolved_question_wait'];
    $settings->timerSolvedQuestionWait    = $data['timer_solved_question_wait'];
    $settings->timerLastAnswerWait        = $data['timer_last_answer_wait'];
    $settings->timerLastQuestionQueryWait = $data['timer_last_question_query_wait'];
    $settings->userNewWait                = $data['user_new_wait'];
    $settings->historyDisplayEntries      = $data['history_display_entries'];
    $settings->historyAvoidLastAnswers    = $data['history_avoid_last_answers'];
    return $settings;
  }

  static function createWithDefaults(): OwnerSettings {
    $settings = new OwnerSettings();
    $settings->ownerId = -1;
    $settings->ownerName = 'def';

    $settings->activeMode = 'ON';
    $settings->timerSolveCreatesNewQuestion = false;
    $settings->debugMode = 0;
    $settings->timerUnsolvedQuestionWait = 120;
    $settings->timerSolvedQuestionWait = 120;
    $settings->timerLastAnswerWait = 30;
    $settings->timerLastQuestionQueryWait = 40;
    $settings->userNewWait = 90;

    $settings->historyDisplayEntries = 0;
    $settings->historyAvoidLastAnswers = 0;
    return $settings;
  }
}
