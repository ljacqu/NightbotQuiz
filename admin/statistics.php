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

echo "<h2>System statistics</h2>";

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
      echo "<td style='text-align: $align'>" . htmlspecialchars($value) . '</td>';
      if ($key !== 'name' && $key !== 'id' && $value) {
        $sum[$key] = $value + ($sum[$key] ?? 0);
      }
    }
    echo '</tr>';
  }

  echo "<tr>";
  foreach (array_keys($statistics[0]) as $key) {
    if ($key === 'name') {
      echo '<td><b>SUM</b></td>';
    } else {
      echo '<td style="text-align: right">' . ($sum[$key] ?? '') . ' </td>';
    }
  }
  echo '</tr></table>';
}

?>
</body>
</html>
