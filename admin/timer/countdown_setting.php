<?php

session_start();

require '../AdminHelper.php';
require '../OwnerNightbotInfo.php';
require '../../Configuration.php';
require '../../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db, '../');

AdminHelper::outputHtmlStart('Timer countdown', $ownerInfo, '../');
$nightbotInfo = AdminHelper::getOwnerNightbotInfo($db, $ownerInfo['id']);

?>
<p class="crumbs"><a href="../">Main</a> &lt; <a href="index.php">Timer</a> &lt; <b>Countdown setting</b></p>
<h2>Timer countdown</h2>
The quiz timer has a countdown, so that it can be started and a delay is added before the page sends a new
question to Nightbot. This can help prevent the quiz to seem very "abrupt" if you unpause the timer at the
very start of your stream.
<br />
<br />You can disable the timer by setting 0 seconds in the field and then pressing "Start". In the future,
you will no longer see the countdown section first.
<br />
<br />Currently, the following is saved for you: <span id="timer-seconds"></span>

<script>
  const countdownValue = localStorage.getItem('nq-timer-wait');
  const resultArea = document.getElementById('timer-seconds');
  if (countdownValue) {


    const resetBtn = document.createElement('button');
    resetBtn.innerText = 'Forget this value';
    resetBtn.className = 'action';
    resetBtn.onclick = () => {
      localStorage.removeItem('nq-timer-wait');
      resetBtn.disabled;
      resultArea.innerText = 'No value saved';
      resultArea.style.fontWeight = 'bold';
    };

    if (countdownValue <= 0) {
      resultArea.innerText = countdownValue + ' (countdown is disabled) ';
    } else {
      resultArea.innerText = countdownValue + ' seconds ';
    }
    resultArea.appendChild(resetBtn);
  } else {
    resultArea.innerText = 'No value saved';
  }
</script>
</body></html>
