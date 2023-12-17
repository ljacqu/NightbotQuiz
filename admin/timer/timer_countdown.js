(() => {
    let countdown = 0;
    let scheduledCountdownFn;

    const showQuizPage = (paused) => {
        clearTimeout(scheduledCountdownFn);
        document.getElementById('countdown-section').style.display = 'none';
        document.getElementById('timer-controls-section').style.display = 'block';

        document.getElementById('pause').checked = paused;
        quizTimer.initializeTimer();
    };

    const initializeCountdownElements = () => {
        const savedWaitTime = localStorage.getItem('nq-timer-wait');

        if (savedWaitTime) {
            const waitTime = +savedWaitTime;
            if (waitTime <= 0) {
                showQuizPage(true);
            } else {
                document.getElementById('cd-seconds-param').value = waitTime;
            }
        }

        document.getElementById('cd-start-btn').onclick = startCountdown;
        document.getElementById('cd-start-directly-btn').onclick = () => showQuizPage(false);
        document.getElementById('cd-start-paused-btn').onclick = () => showQuizPage(true);
    };

    const startCountdown = () => {
        const waitTime = Math.min(+document.getElementById('cd-seconds-param').value, 6000);
        localStorage.setItem('nq-timer-wait', '' + waitTime);
        document.getElementById('cd-start-btn').disabled = true;
        document.getElementById('cd-seconds-param').disabled = true;
        countdown = waitTime;

        if (countdown > 0) {
            document.getElementById('cd-seconds-param-section').style.display = 'none';
            document.getElementById('countdown-display').style.display = 'block';
            executeCountdown();
        } else {
            showQuizPage(true);
        }
    };

    const executeCountdown = () => {
        if (countdown > 0) {
            document.getElementById('countdown-time').innerText = countdown;
            scheduledCountdownFn = setTimeout(executeCountdown, 1000);
            --countdown;
        } else {
            showQuizPage(false);
        }
    };

    initializeCountdownElements();
})();
