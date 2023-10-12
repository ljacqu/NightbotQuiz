<?php

session_start();

require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';
require '../../inc/Utils.php';

Utils::setJsonHeader();

if (!isset($_SESSION['owner'])) {
  die(Utils::toResultJson('Error: You are not logged in'));
} else if (isset($_SESSION['impersonator'])) {
  die(Utils::toResultJson('Error: You are impersonating a user. The request was blocked to avoid accidentally sending messages to another account\'s chat'));
}

$db = new DatabaseHandler();

// Get token info and validate it
$tokenInfo = $db->getNightbotToken($_SESSION['owner']);
if (!$tokenInfo) {
  die(Utils::toResultJson('Error: Invalid user session'));
} else if (empty($tokenInfo['token'])) {
  die(Utils::toResultJson('Error: No Nightbot token information is available'));
} else if (time() > $tokenInfo['token_expires']) {
  die(Utils::toResultJson('Error: Expired Nightbot token'));
}

// Validate message
// TODO: Change to POST (safer)
$message = filter_input(INPUT_GET, 'msg', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
if (empty($message)) {
  die(Utils::toResultJson('Error: the message is empty'));
}

// Send request
$data = ['message' => $message];
$jsonData = json_encode($data);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.nightbot.tv/1/channel/send');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Authorization: Bearer ' . $tokenInfo['token']
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

$response = curl_exec($ch);

if (curl_errno($ch)) {
  echo Utils::toResultJson('Error sending Nightbot message: ' . curl_error($ch));
} else {
  $jsonAnswer = json_decode($response, true);
  if (isset($jsonAnswer['message'])) {
    echo Utils::toResultJson('Error: ' . $jsonAnswer['message']);
  } else {
    // Convention in the JS calling this: any result that doesn't start with "Success" is treated as an error!
    echo Utils::toResultJson('Successfully sent message (' . date('H:i') . ')');
  }
}

curl_close($ch);
