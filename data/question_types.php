<?php

function createQuestionText($questionEntry, $textsByQuestionType) {
  if (!isset($questionEntry['type'])) {
    return $questionEntry['line'];
  }

  switch ($questionEntry['type']) {
    case 'REAL_PLACE':
    case 'FAKE_PLACE':
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
