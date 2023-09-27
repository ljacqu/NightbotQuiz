<?php

$data_questionTypes = [
  'REAL_PLACE' => new QuestionType('yes', 'y', 'true'),
  'FAKE_PLACE' => new QuestionType('no', 'n', 'false')
];

function createAnsweringText($question, $questionType) {
  // TODO: $question is the full question, not just the place name!
  switch ($questionType) {
    case 'REAL_PLACE':
      return $question . ' is a real place in the UK!';
    case 'FAKE_PLACE':
      return $question . ' is NOT a real place in the UK!';
  }
}
