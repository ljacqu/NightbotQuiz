<?php

session_start();

require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db);
if (!$ownerInfo['is_admin']) {
  header('Location: index.php');
  exit;
}

AdminHelper::outputHtmlStart('System statistics', $ownerInfo);

echo '<p class="crumbs"><a href="index.php">Main</a> &lt; <b>Statistics</b></p>
  <h2>System statistics</h2>';

$statistics = $db->getSystemStatistics();
if (empty($statistics)) {
  echo '<p>Uh oh! Could not get any statistics</p>';
} else {
  echo '<table class="bordered"><tr>';
  foreach (array_keys($statistics[0]) as $key) {
    echo "<th>" . htmlspecialchars($key) . "</th>";
  }
  echo '</tr>';

  $sum = [];
  foreach ($statistics as $statsRow) {
    echo "<tr>";
    foreach ($statsRow as $key => $value) {
      $align = $key === 'name' ? 'left' : 'right';
      $cellValue = ($key === 'id' || $key === 'name') ? $value : ($value ?? 0);
      echo "<td style='text-align: $align'>" . htmlspecialchars($cellValue) . '</td>';
      if ($key !== 'name' && $key !== 'id') {
        $sum[$key] = $cellValue + ($sum[$key] ?? 0);
      }
    }
    echo '</tr>';
  }

  echo "<tr>";
  foreach (array_keys($statistics[0]) as $key) {
    if ($key === 'name') {
      echo '<td><b>SUM</b></td>';
    } else {
      echo '<td style="text-align: right">' . ($sum[$key] ?? '') . '</td>';
    }
  }
  echo '</tr></table>';
}

$questionsByType = $db->countQuestionsByType();
echo '<h2>Questions by type</h2>';
if (empty($questionsByType)) {
  echo 'No statistics to show for now.';
} else {
  echo '<table class="bordered"><tr>';
  foreach (array_keys($questionsByType[0]) as $key) {
    echo '<th>' . htmlspecialchars($key) . '</th>';
  }
  echo '</tr>';
  foreach ($questionsByType as $statsRow) {
    echo '<tr>';
    foreach ($statsRow as $key => $value) {
      $align = $key === 'type' ? 'left' : 'right';
      echo "<td style='text-align: $align'>$value</td>";
    }
    echo '</tr>';
  }
  echo '</table>';
}
?>
</body>
</html>
