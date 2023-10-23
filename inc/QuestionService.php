<?php

class QuestionService {

  private DatabaseHandler $db;

  function __construct(DatabaseHandler $db) {
    $this->db = $db;
  }

  function getLastQuestionDraw(int $ownerId): ?QuestionDraw {
    $drawValues = $this->db->getLastQuestionDraw($ownerId);
    if ($drawValues) {
      return QuestionDraw::createFromDbRow($drawValues);
    }
    return null;
  }

  function drawNewQuestion(int $ownerId, int $skipPastQuestions): ?Question {
    $questionValues = $this->db->drawNewQuestion($ownerId, $skipPastQuestions);
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

  function saveLastQuestionQuery(int $ownerId, int $drawId): void {
    $this->db->saveLastQuestionQuery($ownerId, $drawId);
  }

  function createResolutionText(QuestionDraw $lastDraw, bool $emptyForZeroAnswers=false): string {
    $questionType = QuestionType::getType($lastDraw->question);
    $solutionText = $questionType->generateResolutionText($lastDraw->question);

    $stats = $this->getCorrectAnswers($lastDraw);
    if ($emptyForZeroAnswers && $stats['total'] == 0) {
      return '';
    }
    $textChoices = $this->getTextChoicesForAnswerStats($stats['total_correct'], $stats['total'], $stats['user']);

    $statText = $textChoices[ array_rand($textChoices) ];
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
        'Nobody got it right 😅'
      ];
    } else if ($totalCorrect === 1) {
      return [
        'gg ' . $firstUser,
        'Congrats, ' . $firstUser . '!',
        $firstUser . ' got it right'
      ];
    } else if ($totalCorrect === $total) {
      return [
        'Everyone guessed correctly 🎉',
        'All guesses were correct! 👏',
        'Everybody got it right 🥳'
      ];
    } else {
      return ['Correct guesses: ' . $totalCorrect . '/' . $total];
    }
  }
}
