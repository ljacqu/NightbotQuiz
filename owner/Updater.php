<?php

abstract class Updater {

  static function of(string $owner) {
    switch ($owner) {
      case 'medcam':
        return new MedUpdater();
      default:
        throw new Exception('Unknown owner "' . $owner . '"');
    }
  }

  /**
   * Generates the questions that is the complete new set of questions
   * for the given owner.
   *
   * @return Question[] all questions the quiz should consist of
   */
  abstract function generateQuestions(): array;

  protected function readFileOrThrow($fileLocation) {
    $contents = file_get_contents($fileLocation);
    if ($contents === false) {
      throw new Exception('Could not read file "' . $fileLocation . "'");
    }
    return $contents;
  }

  protected function validateIsTextWithinLength($key, $texts, $minLength, $maxLength) {
    if (!isset($texts[$key])) {
      die("No text for key '$key' found!");
    }

    $obj = $texts[$key];
    if (is_scalar($obj)) {
      $str = (string) $obj;
      if (strlen($str) < $minLength) {
        die("Expected the text for '$key' to have at least $minLength characters");
      } else if ($maxLength && strlen($str) > $maxLength) {
        die("Expected the text for '$key' to have at most $maxLength characters");
      }
      return $str;
    } else {
      die("The entry for '$key' is not a string!");
    }
  }

  protected function validateIsNonEmptyCsvList($key, $texts) {
    $text = $this->validateIsTextWithinLength($key, $texts, 0, null);
    $array = [];
    foreach (explode(',', $text) as $entry) {
      if (empty($entry)) {
        die('Found empty text entry in text for "' . $key . '"');
      }
      $array[] = trim(strtolower($entry));
    }
    return $array;
  }

  protected function generateQuestionPreview($questions, $textsByQuestionType) {
    $lastQuestion = end($questions);

    if ($lastQuestion) {
      $questionEntry = createQuestionRecord($lastQuestion);

      $answersList = implode(', ', QuestionType::getPossibleAnswers($lastQuestion));
      return 'Last question: <span class="lastquestion">'
        . htmlspecialchars(createQuestionText($questionEntry, $textsByQuestionType))
        . '</span> (' . htmlspecialchars($answersList) . ')';
    }
  }
}
