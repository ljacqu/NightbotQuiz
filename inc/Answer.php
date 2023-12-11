<?php

/**
 * Represents a processed answer for a question. Defines the behavior that should ensue based on the answer.
 */
class Answer {

  const INVALID_USE_DEFAULT_ERROR = '__invalid';

  /**
   * A string representing the answer provided by the user. This is the value that should be stored in the database;
   * it may be different from what the user actually typed (e.g. in the case of aliases).
   *
   * @var string 
   */
  public string $answer;
  /**
   * If the answer that should be used in texts differs from the canonical answer above, this will be not null.
   *
   * @var ?string
   */
  public ?string $answerForText;
  /**
   * Defines whether the answer is correct.
   *
   * @var bool
   */
  public bool $isCorrect;
  /**
   * If not null, signals that the answer was not recognized. In such cases, an error should be returned and the
   * answer should not be stored because it cannot be applied to the question (e.g. an unrecognized answer for a
   * yes/no question). Use {@link self::INVALID_USE_DEFAULT_ERROR} if you don't want to provide a custom error message
   * for the invalid answer.
   *
   * @var ?string
   */
  public ?string $invalidError;

  function __construct(string $answer, ?string $answerForText, bool $isCorrect, ?string $invalidError) {
    $this->answer = $answer;
    $this->answerForText = $answerForText;
    $this->isCorrect = $isCorrect;
    $this->invalidError = $invalidError;
  }

  static function forCorrectAnswer(string $answer, ?string $answerForText=null): Answer {
    return new Answer($answer, $answerForText, true, null);
  }

  static function forWrongAnswer(string $answer, ?string $answerForText=null): Answer {
    return new Answer($answer, $answerForText, false, null);
  }

  static function forUnknownAnswer(string $answer, string $invalidError): Answer {
    return new Answer($answer, null, false, $invalidError);
  }
}
