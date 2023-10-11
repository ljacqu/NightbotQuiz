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

  static function getOwnerNightbotInfo(DatabaseHandler $db, int $ownerId): OwnerNightbotInfo {
    $values = $db->getOwnerNightbotInfo($ownerId);
    return $values === null ? new OwnerNightbotInfo() : OwnerNightbotInfo::createFromDbValues($values);
  }

  static function createObtainTokenPageLinkForSiblingOrSelf(): string {
    $link = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    return preg_replace('/\w+\.php$/', 'obtain_token.php', $link);
  }

  static function outputHtmlStart(string $title, array $ownerInfo, ?string $relPath=null): void {
    $name = ucfirst($ownerInfo['name']);
    $relPath = $relPath ?? '';
    $impersonatorString = isset($ownerInfo['impersonator'])
      ? "&middot; <a href='{$relPath}impersonate.php?exit'>Exit impersonation</a>"
      : '';

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>$title</title>
  <link rel="stylesheet" href="{$relPath}admin.css" />
</head>
<body>
  <p class="header">
  Hi, <b>$name</b> $impersonatorString &middot; <a href="{$relPath}login.php?logout">Log out</a></p>
HTML;
  }
}
