<?php

session_start();

require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db);

AdminHelper::outputHtmlStart('Change password', $ownerInfo);

echo '<p class="crumbs"><a href="index.php">Main</a> &lt; <b>Change password</b></p>
  <h2>Change password</h2>';

$cur = filter_input(INPUT_POST, 'cur', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
$new = filter_input(INPUT_POST, 'new', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
$confirm = filter_input(INPUT_POST, 'confirm', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

if ($cur && $new && $confirm) {
  echo '<p>';
  if (strlen($new) < 8) {
    echo '<b>Error:</b> your new password must be at least eight characters long!';
  } else {
    $id = $db->getOwnerIdOnCorrectLogin($ownerInfo['name'], $cur);
    if ($id !== null) {
      if ($new === $confirm) {
        $db->setPassword($ownerInfo['id'], password_hash($new, PASSWORD_DEFAULT));
        echo 'Your password has been successfully updated!';
        $new = '';
      } else {
        echo '<b>Error:</b> The password and the confirmation did not match!';
      }
    } else {
      echo '<b>Error:</b> Your current password was not correct.';
    }
  }
  echo '</p>';
}
?>

<form method="post">
  <table>
    <tr>
    <td>
      <label for="cur">Current password:</label>
      </td>
      <td><input type="password" name="cur" id="cur" /></td>
    </tr>
    <tr>
      <td><label for="new">New password:</label></td>
      <td><input type="password" minlength="8" name="new" id="new" required="required" /></td>
    </tr>
    <tr>
      <td><label for="confirm">Confirm password:</label></td>
      <td><input type="password" name="confirm" id="confirm" value="<?php echo htmlspecialchars($new); ?>" /></td>
    </tr>
    <tr>
      <td colspan="2"><input type="submit" value="Update" /></td>
    </tr>
  </table>
</form>
