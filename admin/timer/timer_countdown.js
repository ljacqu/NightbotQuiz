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
        if (e.code === 'KeyC' || e.code === 'Enter' || e.code === 'NumpadEnter') {
            document.getElementById('cd-start-btn').click();
        } else if (e.code === 'KeyP') {
            document.getElementById('cd-start-paused-btn').click();
        }
    };

    const initializeCountdownElements = () => {
        const secondsParamInputElem = document.getElementById('cd-seconds-param');
        if (secondsParamInputElem.value !== '') {
            const waitTime = +secondsParamInputElem.value;
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
        window.addEventListener('keydown', keyEventListener);
    };

    const saveNewWaitTime = (waitTime) => {
        const formData = new FormData();
        formData.append('seconds', waitTime);

        const request = new Request('js_save_countdown_wait.php', {
            method: 'POST',
            body: formData
        });
        fetch(request)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network error');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response when saving new wait time:', data.result);
            })
            .catch(e => {
                console.error('Error saving wait time ' + waitTime, e);
            });
    };

    const startCountdown = () => {
        const waitTime = Math.min(+document.getElementById('cd-seconds-param').value, 900);
        saveNewWaitTime(waitTime);
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
