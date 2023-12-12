<?php

class PlaceQuestionType extends QuestionType {

  private array $questionText;

  function __construct() {
    $placeTexts = json_decode(file_get_contents(__DIR__ . '/../../gen/qt_place_texts.json'), true);
    $this->questionText = $placeTexts;
  }

  function generateKey(Question $question): string {
    return 'plc_' . substr(md5($question->question . $question->answer), 0, 16);
  }

  function generateQuestionText(Question $question): string {
    return str_replace('%place%', $question->question, $this->questionText['question']);
  }

  function generateResolutionText(Question $question): string {
    if ($question->answer === 'yes') {
      return str_replace('%place%', $question->question, $this->questionText['resolution.yes']);
    }
    return str_replace('%place%', $question->question, $this->questionText['resolution.no']);
  }

  function generateIsolatedAnswerText(Question $question, $answer=null): string {
    $answerToConvert = $answer ?? $question->answer;
    if ($answerToConvert === 'yes') {
      return 'Yes';
    }
    return 'No';
  }

  function processAnswer(Question $question, string $answerLower): Answer {
    switch ($answerLower) {
      case 'yeah':
      case 'yep':
      case 'yup':
        return $this->createAnswer($question, 'yes',
          "Aight, got %name%'s answer",
          "@%name% Cool, your answer is saved",
          "Sweet, saved %name%'s guess");
      case 'yas':
        return $this->createAnswer($question, 'yes',
          "Slay, queen! 💅 %name% is guessing yes");
      case 'yass':
      case 'yess':
      case 'yiss':
        return $this->createAnswer($question, 'yes',
          "@%name% A sav'd yar answar",
          "@%name% Eh seved yer enswer",
          "@%name% I siv'd yir inswir");
      case 'aye':
        return $this->createAnswer($question, 'yes',
          "@%name% Och aye! I've haen yer guess, nae bother!",
          "@%name% Aye—I've saved yer guess. Guid on ye!",
          "@%name% Aye, aye, Captain! Yer guess is in the buik.");
      case 'si':
      case 'sì':
        return $this->createAnswer($question, 'yes',
          '@%name% Grazie, ho registrato tutto!', 'Perfetto, ho registrato "sì" per %name%!');
      case 'oui':
        return $this->createAnswer($question, 'yes',
          "@%name% I save ze ânsweur! Merci!");
      case 'ja':
        return $this->createAnswer($question, 'yes',
          "Wunderbar. I saved yes for %name%", "@%name% Danke, I got your answer!");
      case 'yes':
      case 'ye':
      case 'y':
        return $this->createAnswer($question, 'yes');

      case 'nah':
      case 'nope':
      case 'naw':
        return $this->createAnswer($question, 'no',
          "Cool beans, that's a no from %name%", "Noice, saved %name%'s answer 👍");
      case 'nae':
        return $this->createAnswer($question, 'no',
          "@%name% Nae bother! I've got 'nae' down for %name%! Cheers for your guess!",
          "@%name% Guid yin! I've noted doon yer guess",);
      case 'non':
        return $this->createAnswer($question, 'no',
          "%name% sinks ze answer iz non. I savéd it!",
          "Oh non ! %name% thinks the right answer is no.");
      case 'nein':
        return $this->createAnswer($question, 'no',
          "@%name% Hiermit melde ich den Rätselantwortdatenbankspeicherungserfolg.");
      case 'no':
      case 'n':
        return $this->createAnswer($question, 'no');

      case 'maybe':
      case 'what':
      case 'wtf':
      case '?':
        return $this->createUnknownAnswer($answerLower,
          "It's a yes/no question cmonBruh",
          "Everything OK? Answer yes or no monkaS",
          "Kappa", "FailFish");
      case 'oof':
      case 'idk':
        return $this->createUnknownAnswer($answerLower, "Welp", "¯\_(ツ)_/¯");
      default:
        return $this->createUnknownAnswer($answerLower);
    }
  }

  private function createAnswer(Question $question, string $yesOrNo, string ...$easterEggMessages): Answer {
    $answer = $yesOrNo === $question->answer
      ? Answer::forCorrectAnswer($yesOrNo)
      : Answer::forWrongAnswer($yesOrNo);

    if (!empty($easterEggMessages) && rand(0, 20) === 4) {
      $answer->customResponse = Utils::getRandomText(...$easterEggMessages);
    }
    return $answer;
  }

  private function createUnknownAnswer(string $answerLower, string ...$easterEggMessages): Answer {
    if (!empty($easterEggMessages) && rand(0, 2) === 0) {
      return Answer::forUnknownAnswer($answerLower, Utils::getRandomText(...$easterEggMessages));
    }
    return Answer::forUnknownAnswer($answerLower, "Invalid answer. Please guess yes or no.");
  }

  function generateCategory(Question $question): ?string {
    return null;
  }

  function getAllPossibleAnswers(): array {
    return [
      ['code' => 'yes', 'text' => 'Yes'],
      ['code' => 'no', 'text' => 'No']
    ];
  }
}
