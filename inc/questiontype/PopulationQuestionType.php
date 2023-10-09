<?php

class PopulationQuestionType extends CountryBasedQuestionType {

  function generateKey(Question $question): string {
    return 'pop_' . substr(md5($question->question), 0, 16);
  }

  protected function getQuestionTextKey(): string {
    return 'population_question';
  }

  protected function getResolutionTextKey(): string {
    return 'population_resolution';
  }
}
