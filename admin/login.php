<?php

session_start();

if (isset($_SESSION['owner'])) {
  if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: login.php?bye');
  } else {
    header('Location: index.php');
  }
  exit;
}

require '../Configuration.php';
require '../inc/DatabaseHandler.php';

$name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
$pass = filter_input(INPUT_POST, 'pass', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if ($name && $pass) {
  $db = new DatabaseHandler();
  $id = $db->getOwnerIdOnCorrectLogin($name, $pass);
  if ($id) {
    $_SESSION['owner'] = $id;

    header('Location: index.php');
    exit;
  } else {
    $invalid = true;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Log in</title>
  <link rel="stylesheet" href="admin.2.css" />
  <?php
  if ($_SERVER['HTTP_HOST'] !== 'localhost') {
    echo '<link rel="icon" href="../indexpage/favicon.ico" />';
  }
  ?>
</head>
<body>
  <?php
  if (!isset($invalid) && isset($_GET['bye'])) {
    echo 'You have been logged out. See you around!';
  }

  echo '<h2>Login</h2>';  
  if (isset($invalid)) {
    echo '<p>Invalid login. Please try again.</p>';
  }
  ?>
  <form method="post" action="login.php">
    <table>
      <tr>
        <td><label for="name">Username:</label></td>
        <td><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" maxlength="200" /></td>
      </tr>
      <tr>
        <td><label for="pass">Password:</label></td>
        <td><input type="password" id="pass" name="pass" /></td>
      </tr>
      <tr>
        <td colspan="2"><input type="submit" value="Log in" /></td>
      </tr>
    </table>
  </form>
</body>
</html>
