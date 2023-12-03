<?php

session_start();

require '../AdminHelper.php';
require '../OwnerNightbotInfo.php';
require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db, '../');

AdminHelper::outputHtmlStart('Nightbot token', $ownerInfo, '../');

echo '<p class="crumbs"><a href="../">Main</a> &lt; <a href="index.php">Timer</a> &lt; <b>Obtain token</b></p>
<h2>Nightbot token</h2>';
$nightbotInfo = AdminHelper::getOwnerNightbotInfo($db, $ownerInfo['id']);

if (empty($nightbotInfo->clientId) || empty($nightbotInfo->clientSecret)) {
  echo '<p>No Nightbot client information is available!
    <br />Please register an application and enter the client values
    generated by Nightbot first: <a href="app_configuration.php">App configuration</a></p>';
} else if (isset($_SESSION['impersonator'])) {
  echo '<p>You are currently impersonating a user—you cannot obtain a token on their behalf!</p>';
} else if (isset($_GET['code'])) {
  echo retrieveToken($ownerInfo['id'], $nightbotInfo, $db);
} else {
  echo createInfoAndLinkResponse($nightbotInfo, isset($_GET['force']));
}

echo '</body></html>';

// -------------
// FUNCTIONS
// -------------

function retrieveToken(int $ownerId, OwnerNightbotInfo $nightbotInfo, DatabaseHandler $db): string {
  $code = filter_input(INPUT_GET, 'code', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  if (empty($code)) {
    return '<b>Error:</b> Invalid code value.';
  }

  $data = [
    'client_id' => $nightbotInfo->clientId,
    'client_secret' => $nightbotInfo->clientSecret,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => AdminHelper::createObtainTokenPageLinkForSiblingOrSelf()
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.nightbot.tv/oauth2/token');
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

  $response = curl_exec($ch);

  // Debugging? Uncomment the line below to see what was returned.
  // var_dump($response);

  if (curl_errno($ch)) {
    return '<b>Error:</b> Could not get token. cURL error: ' . curl_error($ch);
  } else {
    return handleSuccessfulTokenCurlResponse($response, $ownerId, $nightbotInfo, $db);
  }
}

function handleSuccessfulTokenCurlResponse(string $response, int $ownerId, OwnerNightbotInfo $nightbotInfo,
                                           DatabaseHandler $db): string {
  /*
   * $token should be something like this:
   * array (
   *  'access_token' => '1234567890abcdefedcba09876543210a0c7f1a3',
   *  'token_type' => 'bearer',
   *  'expires_in' => 2592000,
   *  'refresh_token' => 'fedcba09876543210abcdef0123456789a5c3b0d',
   *  'scope' => 'channel_send',
   * );
   */
  $token = json_decode($response, true);

  if (isset($token['message'])) {
    // Nightbot likes to send a HTTP OK status with a "message" containing an error,
    // so pick up on this here. The OAuth response does not have a property "message"
    return '<b>Error:</b> Nightbot answered: ' . htmlspecialchars($token['message']);
  } else if (!isset($token['access_token'])) {
    return '<b>Error:</b> The response did not include the token';
  } else if (!isset($token['expires_in'])) {
    return '<b>Error:</b> The expiration property was not part of the response';
  }


  $nightbotInfo->token = $token['access_token'];
  $nightbotInfo->tokenExpires = time() + $token['expires_in'] - 30;
  $db->updateNightbotTokenInfo($ownerId, $nightbotInfo, $token['refresh_token']);

  return '<p><b style="color: green">&check;</b> Success! The token has been persisted!</p>
    <p><a href="index.php">Timer configuration</a></p>';
}

function createInfoAndLinkResponse(OwnerNightbotInfo $nightbotInfo, bool $forceLink): string {
  $response = '';
  if (empty($nightbotInfo->token) || empty($nightbotInfo->tokenExpires)) {
    $response .= '<p>No Nightbot token is available.</p>';
  } else {
    $expiryDate = date('Y-m-d, H:i', $nightbotInfo->tokenExpires);
    if (time() >= $nightbotInfo->tokenExpires) {
      $response .= '<p>The Nightbot token has <span title="Expiry date: '
        . $expiryDate . '">expired</span>! Please generate a new one.';
    } else {
      $response .= '<p><b style="color: green">&check;</b> A non-expired Nightbot token has been
        saved for your account. The token will expire on ' . $expiryDate;
    }
  }

  $showLink = $forceLink || empty($nightbotInfo->tokenExpires)
    || time() > ($nightbotInfo->tokenExpires - 43200); // allow to regenerate a token if it is only valid for 12 hours, or less
  if ($showLink) {
    $redirectUrl = AdminHelper::createObtainTokenPageLinkForSiblingOrSelf();
    $url = "https://api.nightbot.tv/oauth2/authorize?response_type=code&client_id=" . $nightbotInfo->clientId
      . "&redirect_uri=" . urlencode($redirectUrl) . "&scope=channel_send";
    $response .= '<p><a href="' . htmlspecialchars($url) . '">Click here to connect with Nightbot</a></p>
      <p>If there is an error while getting a token from Nightbot, please ensure that the
      <a href="app_configuration.php">client details</a> are correct.</p>';
  } else {
    $response .= '<p>Everything is in order! You don\'t have to obtain a new token from Nightbot. 
      <a href="?force">Click here</a> if you really want to obtain a new token (should only be done if the current one doesn\'t work).</p>';
  }

  return $response;
}
