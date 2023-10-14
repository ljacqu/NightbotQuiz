<?php
session_start();

require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db);

if (isset($_POST['exit'])) {
  if (isset($_SESSION['impersonator'])) {
    $_SESSION['owner'] = $_SESSION['impersonator'];
    unset($_SESSION['impersonator']);
  }
  header('Location: index.php');
  exit;
}

if (!$ownerInfo['is_admin']) {
  header('Location: index.php');
  exit;
}

$impersonate = filter_input(INPUT_POST, 'impersonate', FILTER_VALIDATE_INT);
if ($impersonate && $impersonate !== $ownerInfo['id']) {
  // If we're already in impersonation mode, disallow this so we don't get confused
  if (isset($_SESSION['impersonator'])) {
    die('Yo dawg, you cannot impersonate someone while impersonating someone!');
  }

  $_SESSION['owner'] = $impersonate;
  $_SESSION['impersonator'] = $ownerInfo['id'];
  header('Location: index.php');
  exit;
}

AdminHelper::outputHtmlStart('Impersonate a user', $ownerInfo);
echo '<p class="crumbs"><a href="index.php">Main</a> &lt; <b>Impersonate</b></p>
  <h2>Impersonate user</h2>
  <p>Change your session to another quiz owner.</p>';

echo '<form method="post" action="impersonate.php">
  <input type="hidden" id="impersonate" name="impersonate" value="monkaS" />
  <table class="bordered"><tr><th>ID</th><th>Name</th><th>Impersonate</th></tr>';
$disableImpersonateButton = isset($_SESSION['impersonator']);
foreach ($db->getAllOwners() as $ownerRow) {

  if ($ownerRow['id'] == $ownerInfo['id']) {
    $impersonate = '&nbsp;';
  } else {
    $impersonate = createButtonChangingHiddenInput($ownerRow['id'], $disableImpersonateButton);
  }

  echo "<tr>
    <td>{$ownerRow['id']}</td>
    <td>{$ownerRow['name']}</td>
    <td>$impersonate</td>
    </tr>";
}
echo '</table>';

function createButtonChangingHiddenInput(int $id, bool $disabled): string {
  if ($disabled) {
    return '<input type="submit" value="Impersonate" title="You are already impersonating a user" disabled="disabled" />';
  }
  return <<<HTML
<input type="submit" value="Impersonate" onclick="document.getElementById('impersonate').value = $id; return true;" />
HTML;
}

?>
</body>
</html>
