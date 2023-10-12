<?php

session_start();

require 'AdminHelper.php';
require '../Configuration.php';
require '../inc/DatabaseHandler.php';

$db = new DatabaseHandler();
$ownerInfo = AdminHelper::getOwnerInfoOrRedirect($db);
AdminHelper::outputHtmlStart('Test quiz commands', $ownerInfo);

$secret = $db->getOwnerSecret($ownerInfo['id']);
?>

<p class="crumbs"><a href="index.php">Main</a> &lt; <b>Test calls</b></p>
  <h2>Test quiz commands</h2>
  <p>Test your quiz by calling the commands on this page with different usernames.
     <b>Note that this changes your actual quiz's answer data</b>, so don't use this while your quiz is running for real.</p>

<?php
if (isset($_POST['deldemoanswers'])) {
  echo '<h2>Deleting demo answers</h2>';
  $deletedAnswers = $db->deleteDemoAnswers($ownerInfo['id']);
  echo 'Deleted ' . $deletedAnswers . ' answers by demo users';
}

if (isset($_POST['delemptyquestions'])) {
  echo '<h2>Deleting empty draws</h2>';
  $deletedQuestions = $db->deleteEmptyDraws($ownerInfo['id']);
  echo 'Deleted ' . $deletedQuestions . ' question draws that had no answers.';
}
?>


<script src="test_caller.js"></script>
<script>
  const caller = createApiTester('<?php echo $secret; ?>');
</script>

<h2>Call !q</h2>
<p>
  <button class="request" style="background-color: #ff0" onclick="caller.callPoll('');"> &nbsp; !q &nbsp; </button>
  <button class="request" style="background-color: #fa0" onclick="caller.callPoll('timer');">!q timer</button>
  <button class="request" style="background-color: #f90" onclick="caller.callPoll('new');">!q new</button>
  <button class="request" style="background-color: #f60" onclick="caller.callPoll('silentnew');">!q silentnew</button>
  &nbsp; &nbsp; <span style="color: #999" onclick="document.getElementById('customqblock').style = ''; this.style.display = 'none';" title="Click to call !q with a custom variant">Call with custom variant?</span>
</p>

<p style="display: none" id="customqblock">
  <label for="variant">Custom variant:</label> <input type="text" id="variant" />
  <button class="request" style="background-color: #fa8" onclick="caller.callPoll(document.getElementById('variant').value);">Call !q with variant</button>
</p>

Result:
<div id="pollresult" class="requestresult"></div>

<p>History:</p>
<ul class="requesthistory" id="pollhistory"><li>None yet</li></ul>

<h2>Call !a</h2>
<p>
  <label for="answer">Answer:</label> <input type="text" id="answer" />
</p>

<p>
  <button onclick="caller.callAnswer(document.getElementById('answer').value, 'Arno');"  class="request" style="background-color: #bbf">Answer as Arno</button>
  <button onclick="caller.callAnswer(document.getElementById('answer').value, 'Beth');"  class="request" style="background-color: #7da">Answer as Beth</button>
  <button onclick="caller.callAnswer(document.getElementById('answer').value, 'Chris');" class="request" style="background-color: #dd3">Answer as Chris</button>
  <button onclick="caller.callAnswer(document.getElementById('answer').value, 'Dan');"   class="request" style="background-color: #fa3">Answer as Dan</button>
</p>


Result:
<div id="anwserresult" class="requestresult"></div>

<p>History:</p>
<ul class="requesthistory" id="answerhistory"><li>None yet</li></ul>

<h2>Cleanup</h2>
<p>Remove demo user answers and questions without guesses from the database with the buttons below. Demo users can be recognized because they start with <code>demo&amp;</code>.
  No Twitch user can have <code>&amp;</code> in his name. Note that removing empty answers may also delete older legitimate questions if no one provided a guess!</p>

<p>
  <button class="request" style="background-color: #66f" onclick="document.getElementById('delansform').submit();">Delete answers from demo users</button>
  <button class="request" style="background-color: #a6d" onclick="document.getElementById('delemptyform').submit();">Delete empty questions</button>
</p>


<form method="post" id="delansform"> <input type="hidden" name="deldemoanswers" value="1" /> </form>
<form method="post" id="delemptyform"> <input type="hidden" name="delemptyquestions" value="1" /> </form>

</body></html>
