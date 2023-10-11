<?php

session_start();

require '../AdminHelper.php';
require '../OwnerNightbotInfo.php';
require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db);

AdminHelper::outputHtmlStart('App configurations', $ownerInfo, '../');
echo '<p class="crumbs"><a href="../">Main</a> &lt; <a href="index.php">Timer</a> &lt; <b>App configuration</b></p>';

$nightbotInfo = AdminHelper::getOwnerNightbotInfo($db, $ownerInfo['id']);

if (isset($_POST['client_id']) && isset($_POST['client_secret'])) {
  echo '<h2>Configuration update</h2>';
  $clientId = trim(filter_input(INPUT_POST, 'client_id', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR));
  $clientSecret = trim(filter_input(INPUT_POST, 'client_secret', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR));

  if (!empty($clientId) && !empty($clientSecret)) {
    $nightbotInfo->clientId = $clientId;
    $nightbotInfo->clientSecret = $clientSecret;
    $db->updateOwnerNightbotInfo($ownerInfo['id'], $nightbotInfo);
    echo 'Thank you! The app details have been saved.';
  } else {
    echo '<b>Error:</b> please provide the client ID and the client secret.';
  }
}

$appRedirectUrl = AdminHelper::createObtainTokenPageLinkForSiblingOrSelf();
?>
<h2>Nightbot app configuration</h2>
<p>
  In order to obtain a token, you need to <a href="https://nightbot.tv/account/applications">register an application</a>
  in Nightbot. Fill in a name (e.g. "Stream quiz"), and add <code><?php echo htmlspecialchars($appRedirectUrl); ?></code> as redirect URI.
</p>

<p>
  Please provide the client ID and client secret in the form below so that you can obtain a token from Nightbot in a next step.
  <br />Once saved, the client secret is no longer shown on this page for your safety. A small excerpt of the stored value is
  shown in the field.
</p>

<form method="post">
  <table>
    <tr>
      <td style="font-weight: bold"><label for="client_id">Client ID</label></td>
      <td><input type="text" name="client_id" id="client_id" value="<?php echo htmlspecialchars($nightbotInfo->clientId)?>" /></td>
    </tr>
    <tr>
      <td style="font-weight: bold"><label for="client_secret">Client secret</label></td>
      <td><input type="text" name="client_secret" id="client_secret" placeholder="<?php echo obfuscateClientSecret($nightbotInfo->clientSecret); ?>" /></td>
    </tr>
    <tr>
      <td colspan="2"><input type="submit" value=" Save client values " /></td>
    </tr>
  </table>
</form>

<h2>Token information</h2>
<?php
if (empty($nightbotInfo->token)) {
  echo 'No Nightbot token has been saved yet. Go to <a href="obtain_token.php">obtain token</a> to get one after filling
        in the client configurations above!';
} else {
  echo 'Using a Nightbot token that expires in ' . date('Y-m-d, H:i', $nightbotInfo->tokenExpires);
}

function obfuscateClientSecret(?string $clientSecret): string {
  if (empty($clientSecret) || strlen($clientSecret) <= 6) {
    return '';
  }

  $start = substr($clientSecret, 0, 3);
  $end = substr($clientSecret, -3);
  return $start . '…' .$end;
}
?>
</body></html>
