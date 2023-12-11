<?php

abstract class QuestionType {

  private static array $instanceByType = [];

  static function getType(Question $type): QuestionType {
    return self::getTypeByName($type->questionType);
  }

  static function getTypeByName(string $type): QuestionType {
    if (!isset(self::$instanceByType[$type])) {
      switch ($type) {
        case 'PLACE':
          require_once __DIR__ . '/PlaceQuestionType.php';
          $instance = new PlaceQuestionType();
          break;
        case 'POPULATION':
          require_once __DIR__ . '/CountryBasedQuestionType.php';
          require_once __DIR__ . '/PopulationQuestionType.php';
          $instance = new PopulationQuestionType();
          break;
        case 'SURFACE':
          require_once __DIR__ . '/CountryBasedQuestionType.php';
          require_once __DIR__ . '/SurfaceQuestionType.php';
          $instance = new SurfaceQuestionType();
          break;
        case 'LANG':
          require_once __DIR__ . '/LangQuestionType.php';
          $instance = new LangQuestionType();
          break;
        case 'custom':
          require_once __DIR__ . '/CustomQuestionType.php';
          $instance = new CustomQuestionType();
          break;
        default:
          throw new Exception('Unknown question type: ' . $type);
      }

      self::$instanceByType[$type] = $instance;
    }
    return self::$instanceByType[$type];
  }

  abstract function generateQuestionText(Question $question): string;

  abstract function processAnswer(Question $question, string $answerLower): Answer;

  abstract function generateResolutionText(Question $question): string;

  abstract function generateIsolatedAnswerText(Question $question, $answer=null): string;

  abstract function generateKey(Question $question): string;

  abstract function generateCategory(Question $question): ?string;

}
