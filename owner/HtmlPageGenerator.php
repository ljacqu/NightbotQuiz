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
     <p>Answer the questions with <span class="command">' . COMMAND_ANSWER . '</span>; display the current question with <span class="command">'
      . COMMAND_QUESTION . '</span>; create a new question with <span class="command">' . COMMAND_QUESTION
      . ' new</span>.';

    if (!isset($_GET['highscore'])) {
      $result .= '<p>Hover over or click on the answer column below to see the answer! 
                     Click on the "Answer" title to see them all.</p>';
    }
    return $result;
  }

  function generateQuestionsTable(array $pageParams, array $users): string {
    $lastQuestions = $this->db->getLastDraws($this->ownerId, $pageParams['history_display_entries']);

    if (empty($lastQuestions)) {
      return 'No data to show!';
    }
    $userData = $this->getUserAnswersForQuestions($lastQuestions, $users);
    $usersSanitized = $userData['users'];
    $answersByDrawAndName = $userData['answers'];

    $result = '<table><tr>
      <th>Question</th>
      <th onclick="toggleAllSpoilers(this)" style="cursor: pointer" title="Click to reveal all answers">Answer</th>';
    foreach ($usersSanitized as $user) {
      $result .= '<th>' . htmlspecialchars($user) . '</th>';
    }
    $result .= '</tr>';
    foreach ($lastQuestions as $questionData) {
      $question = $this->createQuestion($questionData);
      $questionType = QuestionType::getType($question);
      $solvedTitle = $questionData['solved'] ? 'Solved ' . date('Y-m-d, H:i', $questionData['solved']) : '';

      $questionText = htmlspecialchars( $questionType->generateQuestionText($question) );
      $result .= "<tr><td title='$solvedTitle'>$questionText</td>";
      if (!empty($questionData['solved'])) {
        $textAnswer = htmlspecialchars( $questionType->generateIsolatedAnswerText($question) );
        $result .= "<td class='answer' onclick='toggleSpoiler(this)'>$textAnswer</td>";
      } else {
        $result .= '<td>Not yet solved</td>';
      }

      foreach ($usersSanitized as $user) {
        $userAnswer = isset($answersByDrawAndName[$questionData['id']])
          ? ($answersByDrawAndName[$questionData['id']][strtolower($user)] ?? '')
          : '';
        if ($userAnswer) {
          $class = $this->getCssClassForUserAnswer($question, $userAnswer, $questionData['is_solved']);
          $userAnswerText = $questionType->generateIsolatedAnswerText($question, $userAnswer);
          $result .= "<td class='$class'>" . htmlspecialchars($userAnswerText) . '</td>';
        } else {
          $result .= '<td></td>';
        }
      }

      $result .= "</tr>";

    }
    $result .= "</table> " . $this->createLinksBelowQuestionsTable($pageParams);

    $result .= $this->createUserForm($users, $usersSanitized);
    return $result;
  }

  protected function createLinksBelowQuestionsTable(array $pageParams): string {
    if ($pageParams['high_score_days'] >= 0) {
      return "<br /><a href='?highscore'>Show high score</a>";
    }
    return '';
  }

  private function getUserAnswersForQuestions(array $lastDraws, array $users): array {
    if (empty($users)) {
      return ['users' => [], 'answers' => []];
    }

    $drawIds = [];
    foreach ($lastDraws as $row) {
      $drawIds[] = $row['id'];
    }

    $userDrawAnswers = $this->db->getUserAnswersOnDraws($drawIds, $users);
    $answersByDrawIdAndUser = [];
    $namesFromLowerToOriginal = [];
    foreach ($userDrawAnswers as $dbRow) {
      $drawId = $dbRow['draw_id'];
      if (!isset($answersByDrawIdAndUser[$drawId])) {
        $answersByDrawIdAndUser[$drawId] = [];
      }
      $namesFromLowerToOriginal[strtolower($dbRow['user'])] = $dbRow['user'];
      $answersByDrawIdAndUser[$drawId][strtolower($dbRow['user'])] = $dbRow['answer'];
    }

    $this->sortArrayByEncounterOrder($namesFromLowerToOriginal, $users);
    return [
      'users' => $namesFromLowerToOriginal,
      'answers' => $answersByDrawIdAndUser
    ];
  }

  /**
   * Sorts the first array to be in the same order as the original users, which is an array of users that
   * are not in lower case, with potential duplicates.
   *
   * @param array $arrayWithUserLowerKeys array to sort (keys must be lowercase usernames present in the other array)
   * @param array $originalUsersParam usernames whose encounter order should be reflected in the first array
   */
  private function sortArrayByEncounterOrder(array &$arrayWithUserLowerKeys, array $originalUsersParam): void {
    $index = 0;
    $indexByUserLower = [];
    foreach ($originalUsersParam as $user) {
      $nameLower = strtolower($user);
      if (!isset($indexByUserLower[$nameLower])) {
        $indexByUserLower[$nameLower] = $index;
      }
      ++$index;
    }

    uksort($arrayWithUserLowerKeys, function ($a, $b) use ($indexByUserLower) {
      $index1 = $indexByUserLower[$a];
      $index2 = $indexByUserLower[$b];
      return $index1 === $index2 ? 0 : ($index1 > $index2 ? 1 : -1);
    });
  }

  private function getCssClassForUserAnswer(Question $question, ?string $userAnswer, bool $isSolved): string {
    if ($isSolved) {
      return $userAnswer === $question->answer ? 'correct' : 'wrong';
    }
    return '';
  }

  private function createUserForm(array $users, array $namesFromLowerToOriginal): string {
    $result = '<h2>View user answers</h2>
               Enter names below to see their answers on the recent questions.';
    if (count($users) !== count($namesFromLowerToOriginal)) {
      $result .= '<br /><b>Note:</b> One or more users are not shown because they have no answers, 
        or the same user was specified multiple times.';
    }

    $result .= '<div id="userform">';

    if (count($users) < 10) {
      $users[] = '';
    }
    foreach ($users as $user) {
      $userName = $namesFromLowerToOriginal[ strtolower($user) ] ?? $user;
      $userEscaped = htmlspecialchars($userName, ENT_QUOTES);
      $result .= "<input type='text' style='display: block' class='userfield' value='$userEscaped' />";
    }
    $result .= '</div>
      &nbsp; <input type="submit" id="userbtn" disabled="disabled" onclick="onUserFieldButton()" value="View user answers" />';
    return $result;
  }

  function generateHighScoreTable(int $limitInDays): string {
    $scores = $this->db->getTopScores($this->ownerId, $limitInDays);

    $result = '<h2>High score</h2>
      <p>The following is the high score from the questions of the past <b>' . $limitInDays . ' days</b>.</p>';
    if (empty($scores)) {
      return $result . 'No data to show yet!<br /><a href="?">Show recent questions</a>';
    }

    $result .= '<table><tr><th title="Rank">#</th><th>User</th><th>Correct answers</th><th>Total answers</th>
      <th title="Correct answers / total answers">Accuracy</th></tr>';
    $rank = 0;
    $pastEntry = ['correct' => 0, 'total' => 0];
    $skipZeroEntries = !isset($_GET['zeroes']);
    foreach ($scores as $scoreEntry) {
      if ($scoreEntry['correct'] != $pastEntry['correct'] || $scoreEntry['total'] != $pastEntry['total']) {
        ++$rank;
      }
      if ($skipZeroEntries && $scoreEntry['correct'] == 0) {
        // Stop loop: rows are sorted by total correct, so we'll only have 0 corrects from now on
        break;
      }
      $pastEntry = $scoreEntry;

      $accuracy = round($scoreEntry['correct'] / $scoreEntry['total'] * 100) . ' %';
      $result .= "<tr><td class='numbercell'>$rank</td><td>" . htmlspecialchars($scoreEntry['user'])
        . "</td><td class='numbercell'>{$scoreEntry['correct']}</td>"
        . "<td class='numbercell'>{$scoreEntry['total']}</td>"
        . "<td class='numbercell'>{$accuracy}</td></tr>";
    }
    $result .= '</table><br /><a href="?">Show recent questions</a>';

    return $result;
  }

  function generateAppendix(): string {
    return '';
  }

  private function createQuestion(array $data): Question {
    return new Question($data['type'], $data['question'], $data['answer']);
  }
}
