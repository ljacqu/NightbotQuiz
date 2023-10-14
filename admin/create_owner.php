<?php

session_start();

require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';
require '../inc/OwnerSettings.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db);
if (!$ownerInfo['is_admin']) {
  header('Location: index.php');
  exit;
}

AdminHelper::outputHtmlStart('Create user', $ownerInfo);

echo '<p class="crumbs"><a href="index.php">Main</a> &lt; <b>Create user</b></p>
  <h2>Create user</h2>';

$name = trim(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR));
if ($name) {
  $pass = filter_input(INPUT_POST, 'pass', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  if (strlen($name) < 2 || strlen($name) > 40) {
    echo '<b>Error:</b> The name is too short or too long';
  } else if (strlen($pass) < 8) {
    echo '<b>Error:</b> The password should be at least eight characters';
  } else {
    $db->createNewOwner($name, $pass);
    echo 'Success! User ' . htmlspecialchars($name) . ' created.';
  }
  echo '<h2>New user</h2>';
}
?>
<form method="post">
  <table>
    <tr>
      <td><label for="name">Name:</label></td>
      <td><input type="text" name="name" id="name" /></td>
    </tr>
    <tr>
      <td><label for="pass">Initial password:</label></td>
      <td><input type="password" name="pass" id="pass" /></td>
    </tr>
    <tr>
      <td colspan="2"><input type="submit" value="Create" /></td>
    </tr>
  </table>
</form>

</body></html>
