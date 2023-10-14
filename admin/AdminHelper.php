<?php

final class AdminHelper {

  static function getOwnerInfoOrRedirect(DatabaseHandler $db, ?string $relPath=null): array {
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

    $path = $relPath ?? '';
    session_destroy();
    header("Location: {$path}login.php");
    exit;
  }

  static function getOwnerNightbotInfo(DatabaseHandler $db, int $ownerId): OwnerNightbotInfo {
    $values = $db->getOwnerNightbotInfo($ownerId);
    return $values === null ? new OwnerNightbotInfo() : OwnerNightbotInfo::createFromDbValues($values);
  }

  /**
   * Returns the full link to the obtain_token.php page. As hinted by the function name, this function can
   * only be used by obtain_token.php or any sibling page; calling this function from elsewhere will result
   * in a wrong path.
   *
   * @return string full link to obtain_token.php
   */
  static function createObtainTokenPageLinkForSiblingOrSelf(): string {
    $link = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    return preg_replace('/\\w+\.php$/', 'obtain_token.php', $link);
  }

  static function outputHtmlStart(string $title, array $ownerInfo, ?string $relPath=null): void {
    $name = ucfirst($ownerInfo['name']);
    $relPath = $relPath ?? '';
    $impersonatorString = isset($ownerInfo['impersonator'])
      ? "&middot; <a href='#' onclick='document.getElementById(\"exitimpform\").submit();'>Exit impersonation</a>"
      : '';
    $favicon = $_SERVER['HTTP_HOST'] === 'localhost'
      ? '' // don't define favicon for localhost to be able to distinguish browser tabs
      : "<link rel='icon' href='{$relPath}../indexpage/favicon.ico' />";

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>$title</title>
  <link rel="stylesheet" href="{$relPath}admin.css" />
  $favicon
</head>
<body>
  <p class="header">
  Hi, <b>$name</b> $impersonatorString &middot; <a href="#" onclick="document.getElementById('logoutform').submit();">Log out</a></p>
HTML;

    echo "<form id='logoutform' method='post' action='{$relPath}login.php'><input type='hidden' name='logout' value='1' /></form>";
    if (isset($ownerInfo['impersonator'])) {
      echo "<form id='exitimpform' method='post' action='{$relPath}impersonate.php'><input type='hidden' name='exit' value='1' /></form>";
    }
  }
}
