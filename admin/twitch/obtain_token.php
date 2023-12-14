<?php

session_start();

require '../AdminHelper.php';
require '../OwnerTwitchInfo.php';
require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db, '../');

AdminHelper::outputHtmlStart('Twitch token', $ownerInfo, '../');

echo '<p class="crumbs"><a href="../">Main</a> &lt; <a href="index.php">Twitch</a> &lt; <b>Connect</b></p>
<h2>Twitch token</h2>';
$twitchInfo = getOwnerTwitchInfo($db, $ownerInfo['id']);

if (empty(Configuration::TWITCH_CLIENT_ID) || empty(Configuration::TWITCH_CLIENT_SECRET)) {
  echo '<p>Twitch app information is not available.
    <br />Please contact the quiz administrator.</p>';
} else if (isset($_SESSION['impersonator'])) {
  echo '<p>You are currently impersonating a user—you cannot obtain a token on their behalf!</p>';
} else if (isset($_GET['code'])) {
  echo retrieveToken($ownerInfo['id'], $twitchInfo, $db);
} else {
  echo createInfoAndLinkResponse($twitchInfo, isset($_GET['force']));
}

echo '</body></html>';

// -------------
// FUNCTIONS
// -------------

function getOwnerTwitchInfo(DatabaseHandler $db, int $ownerId): OwnerTwitchInfo {
  $twitchInfo = $db->getOwnerTwitchInfo($ownerId);
  return $twitchInfo ? OwnerTwitchInfo::createFromDbValues($twitchInfo) : new OwnerTwitchInfo();
}

// Docs: https://dev.twitch.tv/docs/authentication/getting-tokens-oauth/#authorization-code-grant-flow

function retrieveToken(int $ownerId, OwnerTwitchInfo $nightbotInfo, DatabaseHandler $db): string {
  $code = filter_input(INPUT_GET, 'code', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  if (empty($code)) {
    return '<b>Error:</b> Invalid code value.';
  }

  $data = [
    'client_id' => Configuration::TWITCH_CLIENT_ID,
    'client_secret' => Configuration::TWITCH_CLIENT_SECRET,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => AdminHelper::createObtainTokenPageLinkForSiblingOrSelf()
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://id.twitch.tv/oauth2/token');
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

function handleSuccessfulTokenCurlResponse(string $response, int $ownerId, OwnerTwitchInfo $twitchInfo,
                                           DatabaseHandler $db): string {
  /*
   * The $response should look something like this:
   * {
   *   "access_token": "rfx2uswqe8l4g1mkagrvg5tv0ks3",
   *   "expires_in": 14124,
   *   "refresh_token": "5b93chm6hdve3mycz05zfzatkfdenfspp1h1ar2xxdalen01",
   *   "scope": [
   *     "channel:moderate",
   *     "chat:edit",
   *     "chat:read"
   *   ],
   *   "token_type": "bearer"
   * }
   */
  $token = json_decode($response, true);

  if (!isset($token['access_token'])) {
    return '<b>Error:</b> The response did not include the token';
  } else if (!isset($token['expires_in'])) {
    return '<b>Error:</b> The expiration property was not part of the response';
  }

  $twitchInfo->token = $token['access_token'];
  $twitchInfo->tokenExpires = time() + $token['expires_in'] - 30;
  $twitchInfo->refreshToken = $token['refresh_token'];
  $db->updateTwitchTokenInfo($ownerId, $twitchInfo);

  return '<p><b style="color: green">&check;</b> Success! The Twitch token has been persisted!</p>';
}

function createInfoAndLinkResponse(OwnerTwitchInfo $twitchInfo, bool $forceLink): string {
  $response = '';
  if (empty($twitchInfo->token) || empty($twitchInfo->tokenExpires)) {
    $response .= '<p>No Twitch token is available.</p>';
  } else {
    $expiryDate = date('Y-m-d, H:i', $twitchInfo->tokenExpires);
    if (time() >= $twitchInfo->tokenExpires) {
      $response .= '<p>The Twitch token has <span title="Expiry date: '
        . $expiryDate . '">expired</span>! Please generate a new one.';
    } else {
      $response .= '<p><b style="color: green">&check;</b> A non-expired Twitch token has been
        saved for your account. The token will expire on ' . $expiryDate;
    }
  }

  $showLink = $forceLink || empty($twitchInfo->tokenExpires)
    || time() > ($twitchInfo->tokenExpires - 43200); // allow to regenerate a token if it is only valid for 12 hours, or less
  if ($showLink) {
    $redirectUrl = AdminHelper::createObtainTokenPageLinkForSiblingOrSelf();
    $url = "https://id.twitch.tv/oauth2/authorize?response_type=code&client_id=" . Configuration::TWITCH_CLIENT_ID
      . "&redirect_uri=" . urlencode($redirectUrl)
      . "&scope=" . urlencode('chat:edit');
    $response .= '<p><a href="' . htmlspecialchars($url) . '">Click here to connect with Twitch</a></p>';
  } else {
    $response .= '<p>Everything is in order! You don\'t have to obtain a new token from Twitch. 
      <a href="?force">Click here</a> if you really want to obtain a new token (should only be done if the current one doesn\'t work).</p>';
  }

  return $response;
}
