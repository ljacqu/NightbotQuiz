<?php

require '../inc/functions.php';
require '../conf/config.php';

verifyApiSecret();

$secret = API_SECRET;

echo <<<HTML
<ul>
  <li><a href="settings.php?secret=$secret">Change settings</a></li>
  <li><a href="update.php?secret=$secret">Update questions</a></li>
</ul>
HTML;
