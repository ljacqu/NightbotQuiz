<?php

abstract class HtmlPageGenerator {

  private int $ownerId;
  private DatabaseHandler $db;

  function __construct(int $ownerId, DatabaseHandler $db) {
    $this->ownerId = $ownerId;
    $this->db = $db;
  }

  static function of(string $owner, int $ownerId, DatabaseHandler $db) {
    switch ($owner) {
      case 'medcam':
        require_once __DIR__ . '/medcam/MedcamHtmlPageGenerator.php';
        return new MedcamHtmlPageGenerator($ownerId, $db);
      case 'highway':
        require_once __DIR__ . '/highway/HighwayHtmlPageGenerator.php';
        return new HighwayHtmlPageGenerator($ownerId, $db);
      default:
        throw new Exception('Unknown owner "' . $owner . '"');
    }
  }

  function getPageTitle(): string {
    return 'Quiz questions';
  }

  function generatePreface(): string {
    return '<h2>Recent questions</h2>
     <p>Answer the questions with <span class="command">' . COMMAND_ANSWER . '</span>; display the current question with <span class="command">'
      . COMMAND_QUESTION . '</span>; create a new question with <span class="command">' . COMMAND_QUESTION
      . ' new</span>.<p>Hover over the answer column below to see the answer!</p>';
  }

  function generateQuestionsTable(int $numberOfEntries): string {
    $lastQuestions = $this->db->getLastDraws($this->ownerId, $numberOfEntries);

    if (empty($lastQuestions)) {
      return 'No data to show!';
    }

    $result = '<table><tr><th>Question</th><th>Answer</th></tr>';
    foreach ($lastQuestions as $questionData) {
      $question = $this->createQuestion($questionData);
      $questionType = QuestionType::getType($question);

      $questionText = htmlspecialchars( $questionType->generateQuestionText($question) );
      $result .= "<tr><td>$questionText</td>";
      if ($questionData['is_solved']) {
        $textAnswer = htmlspecialchars( $questionType->generateIsolatedAnswerText($question) );
        $result .= "<td class='answer'>$textAnswer</td>";
      } else {
        $result .= '<td>Not yet solved</td>';
      }
      $result .= "</tr>";

    }
    $result .= "</table>";

    return $result;
  }

  function generateAppendix(): string {
    return '';
  }

  private function createQuestion(array $data): Question {
    return new Question($data['type'], $data['question'], $data['answer']);
  }
}
