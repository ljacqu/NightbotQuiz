(() => {
    let countdown = 0;
    let scheduledCountdownFn;

    const showQuizPage = (paused) => {
        clearTimeout(scheduledCountdownFn);
        window.removeEventListener('keydown', keyEventListener);

        document.getElementById('countdown-section').style.display = 'none';
        document.getElementById('timer-controls-section').style.display = 'block';

        document.getElementById('pause').checked = paused;
        quizTimer.initializeTimer();
    };

    const keyEventListener = (e) => {
        if (e.code === 'KeyS') {
            document.getElementById('cd-start-btn').click();
        } else if (e.code === 'KeyP') {
            document.getElementById('cd-start-paused-btn').click();
        }
    };

    const initializeCountdownElements = () => {
        const savedWaitTime = localStorage.getItem('nq-timer-wait');

        const secondsParamInputElem = document.getElementById('cd-seconds-param');
        if (savedWaitTime) {
            const waitTime = +savedWaitTime;
            if (waitTime <= 0) {
                showQuizPage(true);
                return;
            } else {
                secondsParamInputElem.value = waitTime;
            }
        }

        document.getElementById('countdown-section').style.display = 'block';
        document.getElementById('cd-start-btn').onclick = startCountdown;
        document.getElementById('cd-start-directly-btn').onclick = () => showQuizPage(false);
        document.getElementById('cd-start-paused-btn').onclick = () => showQuizPage(true);

        const cancelLink = document.getElementById('countdown-display').querySelector('a');
        cancelLink.onclick = () => {
            cancelCountdown();
        };

        secondsParamInputElem.onkeydown = (e) => {
            if (e.code === 'Enter' || e.code === 'NumpadEnter') {
                document.getElementById('cd-start-btn').click();
            }
        };

        window.addEventListener('keydown', keyEventListener);
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

    const cancelCountdown = () => {
        clearTimeout(scheduledCountdownFn);
        document.getElementById('cd-start-btn').disabled = false;
        document.getElementById('cd-seconds-param').disabled = false;
        document.getElementById('cd-seconds-param-section').style.display = 'block';
        document.getElementById('countdown-display').style.display = 'none';
    };

    const executeCountdown = () => {
        if (countdown > 0) {
            scheduledCountdownFn = setTimeout(executeCountdown, 1000);

            document.getElementById('countdown-time').innerText =
                Math.floor(countdown / 60) + ':' + String(countdown % 60).padStart(2, '0');
            --countdown;
        } else {
            showQuizPage(false);
        }
    };

    initializeCountdownElements();
})();
