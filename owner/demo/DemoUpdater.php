<?php

class DemoUpdater extends Updater {

  function generateQuestions(): array {
    echo '<h2>Generating demo questions</h2>
      Note that external definitions for countries, languages etc. are not updated as not to conflict
      with actual quiz owners that define this data! As such, generating these demo questions may fail
      if they\'re based on outdated definitions.';

    echo '<h2>Place questions</h2>';
    $placeQuestions = $this->createPlaceQuestions();
    echo $this->generateQuestionPreview($placeQuestions);

    echo '<h2>Country questions</h2>';
    $countryQuestions = $this->createCountryQuestions();
    echo $this->generateQuestionPreview($countryQuestions);

    echo '<h2>Language questions</h2>';
    $languageQuestions = $this->createLanguageQuestions();
    echo $this->generateQuestionPreview($languageQuestions);

    echo '<h2>Custom questions</h2>';
    $customQuestions = $this->createCustomQuestions();
    echo $this->generateQuestionPreview($customQuestions);

    return array_merge(
      $placeQuestions,
      $countryQuestions,
      $languageQuestions,
      $customQuestions
    );
  }

  private function createPlaceQuestions(): array {
    return [
      new Question('PLACE', 'Halo', 'no'),
      new Question('PLACE', 'Battlefield', 'yes')
    ];
  }

  private function createCountryQuestions(): array {
    return [
      new Question('SURFACE', 'no,se', 'se'),
      new Question('POPULATION', 'bg,sk', 'bg')
    ];
  }

  private function createLanguageQuestions(): array {
    switch (rand(0,2)) {
      case 0:
        return [
          new Question('LANG', 'Tré eru stórar fjölærar trjáplöntur.', 'is'),
          new Question('LANG', 'A fa legtöbbször dús lombozattal rendelkezik.', 'hu')
        ];
      case 1:
        return [
          new Question('LANG', 'Tré eru stórar fjölærar trjáplöntur.', 'is'),
          new Question('LANG', 'Flest tré tilheyra um fimmtíu ættum jurta.', 'is')
        ];
      default:
        return [
          new Question('LANG', 'Bleras differentas spezias da plantas datti però er entaifer ils angiosperms.', 'rm'),
          new Question('LANG', 'Tar las plantas per propi sa sviluppa il bist successivamain d’in scherm', 'rm')
        ];
    }
  }

  private function createCustomQuestions(): array {
    return [
      new Question('custom', '‘Losing my religion‘ was a hit for which alternative Rock Band in 1991?', 'rem,r.e.m.'),
      new Question('custom', 'In what country is the Angkor Wat temple?', 'cambodia')
    ];
  }
}
