<?php

require '../conf/config.php';
require '../inc/functions.php';
require '../inc/Question.php';
require '../conf/question_types.php';
require '../gen/settings.php';

verifyApiSecret();

$activeOptions = [
  'ON',
  'USER_ONLY',
  'OFF'
];
$activeOptionsText = [
  'ON' => 'On',
  'USER_ONLY' => 'User only (silent timer)',
  'OFF' => 'Off'
];

$active = filter_input(INPUT_POST, 'active', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if ($active !== null && in_array($active, $activeOptions, true)) {

  $data_settings = [
    'active' => $active
  ];

  $fh = fopen('../gen/settings.php', 'w') or die ('Failed to write to settings');
  fwrite($fh, '<?php $data_settings = ' . var_export($data_settings, true) . ';');
  fclose($fh);

  echo 'Quiz activity mode has been updated!';

}


$secret = API_SECRET;

$activeOptionsHtml = '';
foreach ($activeOptions as $opt) {
  $text = $activeOptionsText[$opt];
  if ($opt === $data_settings['active']) {
    $activeOptionsHtml .= "<option value='$opt' selected='selected'>$text</option>";
  } else {
    $activeOptionsHtml .= "<option value='$opt'>$text</option>";
  }
}

echo <<<HTML
<form method="post" action="settings.php?secret=$secret">
Quiz activity: <select name="active">$activeOptionsHtml
</select>

<br /><input type="submit" value="Save settings" />
</form>
HTML;
