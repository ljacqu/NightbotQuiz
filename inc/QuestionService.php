<?php

class QuestionService {

  private DatabaseHandler $db;

  function __construct(DatabaseHandler $db) {
    $this->db = $db;
  }

  function getLastQuestionDraw(int $ownerId, bool $mustBeSolved=false): ?QuestionDraw {
    $drawValues = $this->db->getLastQuestionDraw($ownerId, $mustBeSolved);
    if ($drawValues) {
      return QuestionDraw::createFromDbRow($drawValues);
    }
    return null;
  }

  function drawNewQuestion(int $ownerId, string $ownerName, int $skipPastQuestions): ?Question {
    $questionValues = $this->db->drawNewQuestion($ownerId, $ownerName, $skipPastQuestions);
    if ($questionValues) {
      return new Question($questionValues['type'], $questionValues['question'], $questionValues['answer']);
    }
    return null;
  }

  function setCurrentDrawAsResolved(int $drawId): void {
    $this->db->setCurrentDrawAsSolved($drawId);
  }

  function getCorrectAnswers(QuestionDraw $draw): array {
    return $this->db->getCorrectAnswers($draw->drawId, $draw->question->answer);
  }

  function saveLastQuestionQuery(int $drawId, bool $updateLastRepeatTimestamp): void {
    $this->db->saveLastQuestionQuery($drawId, $updateLastRepeatTimestamp);
  }

  function createResolutionText(QuestionDraw $lastDraw, bool $emptyForZeroAnswers=false): string {
    $questionType = QuestionType::getType($lastDraw->question);
    $solutionText = $questionType->generateResolutionText($lastDraw->question);

    $stats = $this->getCorrectAnswers($lastDraw);
    if ($emptyForZeroAnswers && $stats['total'] == 0) {
      return '';
    }
    $textChoices = $this->getTextChoicesForAnswerStats($stats['total_correct'], $stats['total'], $stats['user']);

    $statText = Utils::getRandomText(...$textChoices);
    return Utils::connectTexts($solutionText, $statText);
  }

  private function getTextChoicesForAnswerStats(int $totalCorrect, int $total, ?string $firstUser): array {
    if ($total === 0) {
      return [ '' ];
    } else if ($totalCorrect === 0) {
      if ($total >= 5) {
        return [
          'Nobody guessed the right answer 🙈',
          'There was no correct guess 😲'
        ];
      }
      return [
        'No one guessed the right answer.',
        'Nobody got it right 😅',
        'There was no right answer 😬',
        'All guesses were wrong 🤭',
        'There was no correct guess.'
      ];
    } else if ($totalCorrect === 1) {
      return [
        "gg $firstUser",
        "Congrats, $firstUser!",
        "$firstUser got it right",
        "Good going, $firstUser 😀",
        "$firstUser guessed correctly!"
      ];
    } else if ($totalCorrect === $total) {
      return [
        'Everyone guessed correctly 🎉',
        'All guesses were correct! 👏',
        'Everybody got it right 🥳',
        'All answers were right! 😀✨',
        'Nice—everyone was right! ☺️'
      ];
    } else {
      return [
        "Correct guesses: $totalCorrect/$total",
        "$totalCorrect of $total guesses were correct",
        "$totalCorrect correct guesses out of $total",
        "$total total guesses; $totalCorrect were right!",
        "$totalCorrect out of $total were right",
        "$totalCorrect guessed the right answer 😁"
      ];
    }
  }
}
