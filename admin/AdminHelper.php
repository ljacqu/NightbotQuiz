<?php

final class AdminHelper {

  static function getOwnerInfoOrRedirect(DatabaseHandler $db): array {
    if (isset($_SESSION['owner'])) {
      $ownerId = (int) $_SESSION['owner'];

      $info = $db->getAdminParamsForOwner($ownerId);
      if ($info !== null) {
        $info['id'] = (int) $ownerId;
        if (isset($_SESSION['impersonator'])) {
          $info['impersonator'] = (int) $_SESSION['impersonator'];
        }
        return $info;
      }
    }

    header('Location: login.php');
    exit;
  }

  static function outputHtmlStart(string $title, array $ownerInfo): void {
    $name = ucfirst($ownerInfo['name']);
    $impersonatorString = isset($ownerInfo['impersonator'])
      ? '&middot; <a href="impersonate.php?exit">Exit impersonation</a>'
      : '';

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>$title</title>
  <link rel="stylesheet" href="admin.css" />
</head>
<body>
  Hi, <b>$name</b> &middot; <a href="index.php">Main</a> $impersonatorString &middot; <a href="login.php?logout">Log out</a>
HTML;
  }
}
