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
      case 'demo':
        require_once __DIR__ . '/demo/DemoHtmlPageGenerator.php';
        return new DemoHtmlPageGenerator($ownerId, $db);
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
    $result = '<h2>Recent questions</h2>
     <p>Answer the questions with <span class="command">' . COMMAND_ANSWER . '</span>; display the current question with<span class="command">'
      . COMMAND_QUESTION . '</span>; create a new question with <span class="command">' . COMMAND_QUESTION
      . ' new</span>.';

    if (!isset($_GET['highscore'])) {
      $result .= '<p>Hover over or click on the answer column below to see the answer! 
                     Click on the "Answer" title to see them all.</p>';
    }
    return $result;
  }

  function generateQuestionsTable(int $numberOfEntries, array $users): string {
    $lastQuestions = $this->db->getLastDraws($this->ownerId, $numberOfEntries);

    if (empty($lastQuestions)) {
      return 'No data to show!';
    }
    $userData = $this->getUserAnswersForQuestions($lastQuestions, $users);

    $result = '<table><tr>
      <th>Question</th>
      <th onclick="toggleAllSpoilers(this)" style="cursor: pointer" title="Click to reveal all answers">Answer</th>';
    foreach ($users as $user) {
      $result .= '<th>' . htmlspecialchars($user) . '</th>';
    }
    $result .= '</tr>';
    foreach ($lastQuestions as $questionData) {
      $question = $this->createQuestion($questionData);
      $questionType = QuestionType::getType($question);

      $questionText = htmlspecialchars( $questionType->generateQuestionText($question) );
      $result .= "<tr><td>$questionText</td>";
      if ($questionData['is_solved']) {
        $textAnswer = htmlspecialchars( $questionType->generateIsolatedAnswerText($question) );
        $result .= "<td class='answer' onclick='toggleSpoiler(this)'>$textAnswer</td>";
      } else {
        $result .= '<td>Not yet solved</td>';
      }
      foreach ($users as $user) {
        $userAnswer = isset($userData[$questionData['id']])
          ? ($userData[$questionData['id']][$user] ?? '')
          : '';
        $class = $this->getCssClassForQuestionAndUserAnswer($question, $userAnswer, $questionData['is_solved']);
        $result .= "<td class='$class'>" . $userAnswer . '</td>';
      }

      $result .= "</tr>";

    }
    $result .= "</table> <a href='?highscore'>Show high score</a>";

    return $result;
  }

  private function getUserAnswersForQuestions(array $lastDraws, array $users): array {
    if (empty($users)) {
      return [];
    }

    $drawIds = [];
    foreach ($lastDraws as $row) {
      $drawIds[] = $row['id'];
    }

    $userDrawAnswers = $this->db->getUserAnswersOnDraws($drawIds, $users);
    $answersByDrawId = [];
    foreach ($userDrawAnswers as $dbRow) {
      $drawId= $dbRow['draw_id'];
      if (!isset($answersByDrawId[$drawId])) {
        $answersByDrawId[$drawId] = [];
      }
      $answersByDrawId[$drawId][$dbRow['user']] = $dbRow['answer'];
    }
    return $answersByDrawId;
  }

  private function getCssClassForQuestionAndUserAnswer(Question $question, ?string $userAnswer,
                                                       bool $isSolved): string {
    if ($isSolved && !empty($userAnswer)) {
      return $userAnswer === $question->answer ? 'correct' : 'wrong';
    }
    return '';
  }

  function generateScoresTable(int $limitInDays): string {
    $scores = $this->db->getTopScores($this->ownerId, $limitInDays);

    if (empty($scores)) {
      return 'No data to show yet!';
    }

    $result = '<h2>High score</h2>
               <p>The following is the high score from the questions of the past <b>' . $limitInDays . ' days</b>.</p> 
               <table><tr><th>User</th><th>Correct answers</th>
               <th title="Total answers / correct answers">Accuracy</th></tr>';
    foreach ($scores as $scoreEntry) {
      $accuracy = $scoreEntry['total'] == 0
        ? ''
        : (round($scoreEntry['correct'] / $scoreEntry['total'] * 100) . ' %');
      $result .= "<tr><td>" . htmlspecialchars($scoreEntry['user']) . "</td>
                     <td>{$scoreEntry['correct']}</td><td>{$accuracy}</td></tr>";
    }
    $result .= '</table> <a href="?">Show recent questions</a>';

    return $result;
  }

  function generateAppendix(): string {
    return '';
  }

  private function createQuestion(array $data): Question {
    return new Question($data['type'], $data['question'], $data['answer']);
  }
}
