<?php

class HighwayHtmlPageGenerator extends HtmlPageGenerator {

  private array $languageData;

  function __construct(int $ownerId, DatabaseHandler $db) {
    parent::__construct($ownerId, $db);
    $this->languageData = json_decode(file_get_contents(__DIR__ . '/../../gen/ent_languages.json'), true);
  }

  function generateQuestionsTable(int $numberOfEntries, array $users): string {
    if (!isset($_GET['allhist']) && $numberOfEntries > 5) {
      return parent::generateQuestionsTable(5, $users) . ' <a href="?allhist">Show all past questions</a>';
    }
    return parent::generateQuestionsTable($numberOfEntries, $users);
  }

  function generateAppendix(): string {
    if (isset($_GET['highscore']) || isset($_GET['allhist'])) {
      return '';
    }

    // Title and demo link
    $result = '<h2>Languages</h2>';
    $showDemoSentence = isset($_GET['demo']);
    $demoLinkAddition = filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) === 'group' ? '&amp;sort=group' : '';
    $result .= '<p>'
      . ($showDemoSentence ? "<a href='?a$demoLinkAddition'>Hide sample sentence</a>" : "<a href='?demo$demoLinkAddition'>Show sample sentence</a>")
      . '</p>';

    // Table header
    $sortLinkAddition = $showDemoSentence ? '&amp;demo' : '';
    $result .= "<div style='width: 100%'>
    <div style='float: left; margin-bottom: 20px;'>
      <table>
        <tr>
          <th><a href='?sort=name$sortLinkAddition' title='Click to sort by name'>Language</a></th>
          <th><a href='?sort=group$sortLinkAddition' title='Click to sort by group'>Group</a></th>";
    if ($showDemoSentence) {
      $result .= '<th>Example</th>';
    }
    $result .= '<th>Aliases</th></tr>';

    // Table rows
    $languagesByCode = $this->getLanguagesByCodeWithDefinedSorting();
    $demoSentencesByCode = $showDemoSentence ? $this->loadDemoSentences() : [];
    foreach ($languagesByCode as $code => $lang) {
      $aliases = implode(', ', $lang['aliases']);
      $result .= "<tr><td>{$lang['name']}</td><td>{$lang['group']}</td>";
      if ($showDemoSentence) {
        $demoSentence = $demoSentencesByCode[$code] ?? '';
        $result .= '<td>' . htmlspecialchars($this->trimDemoText($demoSentence, $code)) . '</td>';
      }
      $result .= "<td>$aliases</td></tr>";
    }

    // Close tags, add usage <div>
    $result .= '</table></div>'
      . $this->generateExplanationDiv()
      . '</div>';

    return $result;
  }

  private function generateExplanationDiv(): string {
    $commandAnswer = COMMAND_ANSWER;
    return <<<HTML
    <div style="margin-left: 10px; margin-bottom: 20px; float: left">
      <br />
      When prompted to guess the language of a text, you can use the language name or any of its aliases.
    For example, you can use any of the following to answer with German:
      <ul>
        <li><span class="command">$commandAnswer german</span></li>
        <li><span class="command">$commandAnswer de</span></li>
        <li><span class="command">$commandAnswer deutsch</span></li>
      </ul>

      <br />
      Note: The first alias in the table is always the ISO 639-1 code of the language, the second alias is the ISO 639-2 code (or similar).
    </div>
HTML;
  }

  private function getLanguagesByCodeWithDefinedSorting(): array {
    $languagesByCode = $this->languageData;

    $sortFn = filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR) === 'group'
      ? function ($a, $b) { return strcmp($a['group'], $b['group']); }
      : function ($a, $b) { return strcmp($a['name'],  $b['name']); };
    uasort($languagesByCode, $sortFn);
    return $languagesByCode;
  }

  private function trimDemoText(string $text, string $code): string {
    if ($code === 'kk' || mb_strlen($text) > 80) {
      switch ($code) {
        case 'kk':
          $limit = 68;
          break;
        case 'ta':
          $limit = 56;
          break;
        default:
          $limit = 80;
      }
      return trim(mb_substr($text, 0, $limit)) . '…';
    }
    return $text;
  }

  private function loadDemoSentences(): array {
    return json_decode(file_get_contents(__DIR__ . '/../../gen/ent_languages_sample.json'), true);
  }
}
