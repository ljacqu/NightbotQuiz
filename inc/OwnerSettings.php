<?php

class OwnerSettings {

  public int $ownerId;
  public string $ownerName;
  public string $activeMode;
  public ?string $twitchName;
  public bool $timerSolveCreatesNewQuestion;
  public int $debugMode;
  public ?int $timerCountdownSeconds;
  public int $repeatUnansweredQuestion;

  public int $timerUnsolvedQuestionWait;
  public int $timerSolvedQuestionWait;
  public int $timerLastAnswerWait;
  public int $timerLastQuestionQueryWait;
  public int $userNewWait;

  public int $historyDisplayEntries;
  public int $historyAvoidLastAnswers;
  public int $highScoreDays;

  static function createFromDbRow(array $data): OwnerSettings {
    $settings = new OwnerSettings();

    $settings->ownerId                    = $data['id'];
    $settings->ownerName                  = $data['name'];
    $settings->activeMode                 = $data['active_mode'];
    $settings->twitchName                 = $data['twitch_name'];
    $settings->timerSolveCreatesNewQuestion = $data['timer_solve_creates_new_question'];
    $settings->debugMode                  = $data['debug_mode'];
    $settings->timerCountdownSeconds      = $data['timer_countdown_seconds'];
    $settings->repeatUnansweredQuestion   = $data['repeat_unanswered_question'];
    $settings->timerUnsolvedQuestionWait  = $data['timer_unsolved_question_wait'];
    $settings->timerSolvedQuestionWait    = $data['timer_solved_question_wait'];
    $settings->timerLastAnswerWait        = $data['timer_last_answer_wait'];
    $settings->timerLastQuestionQueryWait = $data['timer_last_question_query_wait'];
    $settings->userNewWait                = $data['user_new_wait'];
    $settings->historyDisplayEntries      = $data['history_display_entries'];
    $settings->historyAvoidLastAnswers    = $data['history_avoid_last_answers'];
    $settings->highScoreDays              = $data['high_score_days'];
    return $settings;
  }

  function outputDebug(): bool {
    return ($this->debugMode & 1) === 1;
  }

  static function createWithDefaults(): OwnerSettings {
    $settings = new OwnerSettings();
    $settings->ownerId = -1;
    $settings->ownerName = 'def';

    $settings->activeMode = 'ON';
    $settings->timerSolveCreatesNewQuestion = false;
    $settings->debugMode = 0;
    $settings->timerCountdownSeconds = null;
    $settings->repeatUnansweredQuestion = 0;
    $settings->timerUnsolvedQuestionWait = 120;
    $settings->timerSolvedQuestionWait = 120;
    $settings->timerLastAnswerWait = 30;
    $settings->timerLastQuestionQueryWait = 40;
    $settings->userNewWait = 90;

    $settings->historyDisplayEntries = 0;
    $settings->historyAvoidLastAnswers = 0;
    $settings->highScoreDays = 30;
    return $settings;
  }
}
