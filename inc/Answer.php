<?php

/**
 * Represents a processed answer for a question. Defines the behavior that should ensue based on the answer.
 */
class Answer {

  /**
   * A string representing the answer provided by the user. This is the value that should be stored in the database;
   * it may be different from what the user actually typed (e.g. in the case of aliases).
   * 
   * @var string 
   */
  public string $answer;
  /**
   * Defines whether the answer is correct.
   * 
   * @var bool
   */
  public bool $isCorrect;
  /**
   * Defines whether we should consider the question as resolved because of this answer; this may be the case for yes/no
   * questions if we don't want to permit multiple answers.
   * 
   * @var bool
   */
  public bool $resolvesQuestion;
  /**
   * If true, signals that the answer was not recognized. In such cases, an error should be returned and the answer should
   * not be stored because it cannot be applied to the question (e.g. an unrecognized answer for a yes/no question).
   * 
   * @var bool
   */
  public bool $invalid;

  function __construct(string $answer, bool $isCorrect, bool $resolvesQuestion, bool $invalid) {
    $this->answer = $answer;
    $this->isCorrect = $isCorrect;
    $this->resolvesQuestion = $resolvesQuestion;
    $this->invalid = $invalid;
  }

  static function forCorrectAnswer(string $answer): Answer {
    return new Answer($answer, true, true, false);
  }

  static function forWrongAnswer(string $answer, bool $resolvesQuestion): Answer {
    return new Answer($answer, false, $resolvesQuestion, false);
  }

  static function forUnknownAnswer(string $answer): Answer {
    return new Answer($answer, false, false, true);
  }
}
