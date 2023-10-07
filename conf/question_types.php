<?php

function createQuestionText($questionEntry, $textsByQuestionType) {
  if (!isset($questionEntry['type'])) {
    return $questionEntry['line'];
  }

  // TODO: Move function ,and remove old place types
  switch ($questionEntry['type']) {
    case 'REAL_PLACE':
    case 'FAKE_PLACE':
    case 'PLACE':
      return str_replace('%place%', $questionEntry['line'], $textsByQuestionType[$questionEntry['type']]['question']);
    default:
      throw new Exception('Unknown question type: ' . $questionEntry['type']);
  }
}

function createResolutionText($questionEntry, $textsByQuestionType) {
  if (!isset($questionEntry['type'])) {
    return 'The previous answer was: ' . $questionEntry['textanswer'];
  }

  switch ($questionEntry['type']) {
    case 'REAL_PLACE':
    case 'FAKE_PLACE':
      return str_replace('%place%', $questionEntry['line'], $textsByQuestionType[$questionEntry['type']]['resolutionText']);
    default:
      throw new Exception('Unknown question type: ' . $questionEntry['type']);
  }
}

function getPossibleAnswers($questionEntry, $textsByQuestionType) {
  return isset($questionEntry['type'])
    ? $textsByQuestionType[$questionEntry['type']]['answers']
    : $questionEntry['answers'];
}

/**
 * Returns whether the answer supplied by the player can be mapped to a wrong answer, but
 * given that it is wrong, it resolves the question. Used for yes/no questions, but this
 * check ensures that "maybe" as an answer gets rejected and doesn't resolve the question.
 *
 * @param string $questionType the question type
 * @param string $answer the player's answer
 * @param array $textsByQuestionType text data of the question types
 * @return array response with keys "solved" and "invalid", indicating whether the answer was wrong but solved
 *               the question (for yes/no questions), or if the answer does not apply to the question
 */
function processInvalidAnswer($questionType, $answer, $textsByQuestionType) {
  $isSolvingAnswer = false;
  $isInvalidAnswer = false;

  switch ($questionType) {
    case 'REAL_PLACE':
      $isSolvingAnswer = array_search($answer, $textsByQuestionType['FAKE_PLACE']['answers'], true) !== false;
      $isInvalidAnswer = !$isSolvingAnswer;
      break;
    case 'FAKE_PLACE':
      $isSolvingAnswer = array_search($answer, $textsByQuestionType['REAL_PLACE']['answers'], true) !== false;
      $isInvalidAnswer = !$isSolvingAnswer;
      break;
    default:
  }

  return ['solved' => $isSolvingAnswer, 'invalid' => $isInvalidAnswer];
}