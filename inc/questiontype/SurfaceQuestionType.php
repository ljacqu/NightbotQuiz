<?php

class SurfaceQuestionType extends CountryBasedQuestionType {


  function generateKey(Question $question): string {
    return 'srf_' . substr(md5($question->question), 0, 16);
  }

  protected function getQuestionTextKey(): string {
    return 'population_question';
  }

  protected function getResolutionTextKey(): string {
    return 'population_resolution';
  }
}
