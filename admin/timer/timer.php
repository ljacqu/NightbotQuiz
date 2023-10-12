<?php

session_start();

require '../AdminHelper.php';
require '../OwnerNightbotInfo.php';
require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';
require '../../inc/Utils.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db, '../');
$nightbotInfo = AdminHelper::getOwnerNightbotInfo($db, $ownerInfo['id']);
$apiSecret = $db->getOwnerSecret($ownerInfo['id']);

// Deny this page if impersonating a user to avoid unintended changes to someone else's quiz
if (isset($_SESSION['impersonator'])) {
  AdminHelper::outputHtmlStart('Timer', $ownerInfo, '../');
  echo '<p class="crumbs"><a href="../index.php">Main</a> &lt; <a href="index.php">Timer</a> &lt; <b>Timer page</b></p>';
  echo '<h2>Timer denied</h2>You are currently impersonating a user! The timer has been blocked to avoid accidentally changing another user\'s quiz data';
  echo '</body></html>';
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Quiz - timer</title>
  <script>
    const secret = '<?php echo $apiSecret; ?>';

    var isActive = true;
    var hash = 'notset';

    function getCurrentTimeAsString() {
      const currentdate = new Date();
      return String(currentdate.getHours()).padStart(2, '0')
        + ":" + String(currentdate.getMinutes()).padStart(2, '0')
        + ":" + String(currentdate.getSeconds()).padStart(2, '0');
    }

    const callPollFile = (variant) => {
      const request = new Request(`../../api/poll.php?secret=${secret}&variant=${variant}&hash=${hash}`, {
        method: 'GET'
      });

      const pollErrorElem = document.getElementById('pollerror');
      fetch(request)
        .then((response) => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then((data) => {
          if (data.result.trim() !== '') {
            document.getElementById('result').innerHTML = data.result;
          } else if (data.info && data.info.trim() !== '') {
            document.getElementById('result').innerHTML = data.info;
          }
          if (data.hash) {
            hash = data.hash;
          }

          document.getElementById('time').innerHTML = getCurrentTimeAsString();

          pollErrorElem.style.display = 'none';
          return data.result;
        })
        .then((result) => {
          if (result.trim() !== '') {
            sendMessage(result);
          }
          setBodyBgColor('#e5fff9');
        })
        .catch((error) => {
          pollErrorElem.style.display = 'block';
          document.getElementById('pollerrormsg').innerHTML = error.message;
          setBodyBgColor('#fff0f0');
        });
    };

    const sendMessage = (msg) => {
      const request = new Request(`send_message.php?msg=` + encodeURIComponent(msg), {
        method: 'GET'
      });

      const msgElem = document.getElementById('msg');
      fetch(request)
        .then((response) => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then((data) => {
          if (!data.result || !data.result.startsWith('Success')) {
            msgElem.className = 'error';
            msgElem.innerText = data.result ?? data;
            setBodyBgColor('#fff0f0');
          } else {
            msgElem.className = '';
            msgElem.innerText = data.result;
            setBodyBgColor('#cfc');
          }
        })
        .catch((error) => {
          msgElem.className = 'error';
          msgElem.innerText = error.message;
          setBodyBgColor('#fff0f0');
        });
    };

    function setBodyBgColor(color) {
      document.body.style.backgroundColor = color;
    }

    function togglePause() {
      const isChecked = document.getElementById('pause').checked;
      isActive = !isChecked;
      setBodyBgColor(isActive ? '#fff' : '#ccc');
    }

    function callPollRegularly() {
      if (isActive) {
        callPollFile('timer');
      } else {
        // Update background color to the "paused" color to reset the bgcolor
        // in case we pressed on a manual button
        setBodyBgColor('#ccc');
      }

      // The number below is how often, in milliseconds, we call poll.php?variant=timer.
      // If you've configured sensible timeouts in config.php, this number can be quite
      // low; otherwise, a higher number like 90s may be more appropriate. 
      setTimeout(callPollRegularly, 15000);
    }
  </script>
  <link rel="stylesheet" href="../admin.css" />
  <style>
  body {
    font-size: 12pt;
  }
  .error {
    color: #f00;
    background-color: #fcc;
  }
  #result {
    border: 1px solid #999;
    background-color: #ccc;
    padding: 5px;
    margin: 12px;
    display: inline-block;
    font-size: 12pt;
  }
  .manual {
    padding: 5px;
    margin: 3px;
    font-size: 12pt;
  }
  </style>
</head>
<body onload="togglePause(); callPollRegularly()">
  <p class="crumbs" style="font-size: 10pt"><a href="../">Main</a> &lt; <a href="index.php">Timer</a> &lt; <b>Timer page</b></p>
  <h2>Quiz timer</h2>
  Last message:
  <div id="result"><span style="color: #333; font-style: italic; font-size: 0.9em">No response with text received yet</span></div>
  <div>Last request: <span id="time"></span></div>
  <div id="pollerror" class="error" style="display: none">Error during last call: <span id="pollerrormsg"></span> </div>
  <div>Last Nightbot message: <span id="msg"></span></div>

  <div style="margin-top: 1em"><input type="checkbox" checked="checked" name="pause" id="pause" onchange="togglePause();" /> <label for="pause">Pause</label></div>

  <div>
    <button class="manual" style="background-color: #ecf" onclick="callPollFile('');" title="Runs !q and sends the result to Nightbot">Show question again</button>
    <button class="manual" style="background-color: #ffe" onclick="callPollFile('silentnew');" title="Runs !q silentnew and sends the result to Nightbot">Force new question</button>
  </div>

  <?php
  if (isTokenExpired($nightbotInfo)) {
    echo '<h2>No Nightbot token</h2>
      <div class="error" style="padding: 9px">No valid Nightbot token has been found!
      Please go to <a href="obtain_token.php">obtain token</a> to generate a new one.</div>';
  }
  ?>
</body>
</html>

<?php

function isTokenExpired(OwnerNightbotInfo $nightbotInfo): bool {
  return empty($nightbotInfo->tokenExpires) || time() > $nightbotInfo->tokenExpires;
}
