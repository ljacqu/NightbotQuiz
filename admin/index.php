<?php

session_start();
require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';
$db = new DatabaseHandler();
$ownerInfo = Adminhelper::getOwnerInfoOrRedirect($db);

AdminHelper::outputHtmlStart('Quiz administration', $ownerInfo);
echo '<p class="crumbs"><b>Main</b></p>';

$tokenInfo = $db->getNightbotToken($ownerInfo['id']);
?>

<ul>
  <li><a href="change_pass.php">Change password</a></li>
</ul>

<h2>Quiz administration</h2>
<ul class="overview">
  <li><a href="overview.php"><b class="kb-shortcut">O</b>verview</a></li>
  <li><a href="settings.php"><b class="kb-shortcut">S</b>ettings</a></li>
  <li><a href="update.php">Update questions</a></li>
  <li><a href="./timer/">Timer configuration</a></li>
  <li><a href="test_calls.php">Test quiz commands</a></li>
</ul>

<?php
if ($ownerInfo['is_admin']) {
  echo <<<HTML
<h2>System administration</h2>
<ul class="overview">
 <li><a href="statistics.php">Statistics</a></li>
 <li><a href="impersonate.php">Impersonate</a></li>
 <li><a href="create_owner.php">Create user</a></li>
</ul>

HTML;
}

$hasValidToken = !empty($tokenInfo['token_expires']) && (time() < $tokenInfo['token_expires']);
if ($hasValidToken) {
  echo <<<HTML
<button onclick="window.location.href='./timer/timer.php';" class="action">
   Open <span class="kb-shortcut">t</span>imer page
</button>
HTML;
}

echo '<p style="margin-top: 2em; font-size: 0.9em">Keyboard shortcuts: ';
if ($hasValidToken) {
  echo 'O (overview), S (settings), T (timer)';
} else {
  echo 'O (overview), S (settings)';
}
echo '</p>';
?>
<script>
  const hasTimerBtn = <?= $hasValidToken ? 'true' : 'false' ?>;
  window.addEventListener('keydown', (e) => {
    if (e.code === 'KeyS') {
      window.location.href = 'settings.php';
    } else if (e.code === 'KeyO') {
      window.location.href = 'overview.php';
    } else if (e.code === 'KeyT' && hasTimerBtn) {
      window.location.href = './timer/timer.php';
    }
  });
</script>

</body>
</html>

