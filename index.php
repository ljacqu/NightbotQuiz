<!DOCTYPE html>
<html>
<head>
  <title>Language guess</title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <style type="text/css">

  body, table {
    font-family: Arial;
    font-size: 11pt;
  }
  td, th {
    padding: 2px;
  }
  table {
    border-collapse: collapse;
  }
  .command {
    font-family: Consolas, monospace;
  }
  th a {
    text-decoration: none;
  }

  @media (prefers-color-scheme: dark) {
    body {
      background-color: #111;
      color: #eee;
    }
    td, th {
      border: 1px solid #999;
    }
    .answer {
      color: #111;
    }
    .answer:hover {
      color: #111;
      background-color: #cc0;
    }
    .command {
      color: #ffdf90;
    }
    th a {
      color: #99f;
    }
    th a:hover, a:link, a:visited {
      color: #ff9;
    }
  }

  @media (prefers-color-scheme: light) {
    body {
      background-color: #fff;
      color: #000;
    }
    td, th {
      border: 1px solid #000;
    }
    .answer {
      color: #fff;
    }
    .answer:hover {
      color: #000;
      background-color: #ff0;
    }
    .command {
      color: #f40;
    }
    th a {
      color: #007;
    }
    th a:hover, a:link, a:visited {
      color: #33f;
    }
  }
  </style>
</head>
<body>

  <?php
  require './conf/config.php';
  require './inc/functions.php';
  require './conf/current_state.php';

  echo '<h2>Recent questions</h2>';
  if (empty($data_lastQuestions)) {
    echo 'No data to show!';
    exit;
  }
  echo '<p>Answer the questions with <span class="command">' . COMMAND_ANSWER . '</span>; display the current question with <span class="command">'
    . COMMAND_QUESTION . '</span>; create a new question with <span class="command">' . COMMAND_QUESTION . ' new</span>.';
  echo '<p>Hover over the answer column below to see the answer!</p>';


  echo '<table><tr><th>Text</th><th>Language</th></tr>';
  foreach ($data_lastQuestions as $question) {
    echo "<tr><td>" . htmlspecialchars($question['line']) . "</td>";
    if (isset($question['solver'])) {
      echo "<td class='answer'>" . htmlspecialchars($question['textanswer']);
    } else {
      echo '<td>Not yet solved';
    }
    echo "</td></tr>";

  }
  echo "</table>";

  ?>
  
</body>
</html>
