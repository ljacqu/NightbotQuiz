const quizTimer = {

    createdAt: new Date().getTime() / 1000, // seconds
    secret: 'TBD', // overridden on initialization
    twitchName: '', // overridden on initialization
    readOnly: false,
    hash: 'notset',
    possibilitiesByType: {},
    scheduledCall: null

};

const getCurrentTimeAsString = () => {
    const currentDate = new Date();
    return String(currentDate.getHours()).padStart(2, '0')
        + ':' + String(currentDate.getMinutes()).padStart(2, '0')
        + ':' + String(currentDate.getSeconds()).padStart(2, '0');
};

const setBodyBgColor = (color) => {
    // Using background-color instead of background-image here does not override the style from the CSS,
    // because it uses background-image. So we're forced to create a gradient for a single color.
    document.body.style.backgroundImage = `linear-gradient(0deg, ${color}, ${color})`;
};

const fetchJson = (request) => {
    return fetch(request)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not OK');
            }
            return response.json();
        });
};

const sendMessage = (msg, isPaused = false) => {
    const formData = new FormData();
    formData.append('msg', msg);
    const request = new Request('send_message.php', {
        method: 'POST',
        body: formData
    });

    const msgElem = document.getElementById('msg');
    fetchJson(request)
        .then(data => {
            if (!data.result || !data.result.startsWith('Success')) {
                msgElem.className = 'error';
                msgElem.innerText = data.result ?? data;
                setBodyBgColor('#fff0f0');
            } else {
                msgElem.className = '';
                msgElem.innerText = data.result;
                if (!isPaused) {
                    setBodyBgColor('#cfc');
                }
            }
        })
        .catch(error => {
            msgElem.className = 'error';
            msgElem.innerText = error.message;
            setBodyBgColor('#fff0f0');
        });
};

quizTimer.callPollFile = (variant) => {
    const requestUrl = `../../api/poll.php?secret=${quizTimer.secret}&variant=${variant}&hash=${quizTimer.hash}`;
    const request = new Request(requestUrl, {
        method: 'GET'
    });

    const pollErrorElem = document.getElementById('pollerror');
    fetchJson(request)
        .then(data => {
            setBodyBgColor('#e5fff9');

            if (data.result.trim() !== '') {
                document.getElementById('result').innerHTML = data.result;
                sendMessage(data.result, !!data.stop);
            } else if (data.info && data.info.trim() !== '') {
                document.getElementById('result').innerHTML = data.info;
            }
            if (data.hash) {
                quizTimer.hash = data.hash;
            }
            if (data.type && quizTimer.twitchName) {
                updateAnswerButtonsForQuestionType(data.type);
            }
            if (data.stop) {
                cancelScheduledCall();
                updatePageElementsForPausedQuiz();
            }

            document.getElementById('time').innerHTML = getCurrentTimeAsString();
            pollErrorElem.style.display = 'none';
        })
        .catch(error => {
            pollErrorElem.style.display = 'block';
            document.getElementById('pollerrormsg').innerHTML = error.message;
            setBodyBgColor('#fff0f0');
        });
};

const cancelScheduledCall = () => {
    clearTimeout(quizTimer.scheduledCall);
    quizTimer.scheduledCall = null;
};

const solveQuestion = () => {
    const options = document.getElementById('solvedeleteifempty').checked ? '' : 'r';
    const requestUrl = `../../api/solve.php?secret=${quizTimer.secret}&options=${options}`;
    const request = new Request(requestUrl, { method: 'GET' });

    fetchJson(request)
        .then(data => {
            const infoText = data.info ? (' [Info: ' + data.info + ']') : '';
            document.getElementById('solveresult').innerHTML = data.result + infoText;
            return data.result;
        })
        .then(result => {
            if (result.trim() !== '') {
                sendMessage(result, true);
            }
        })
        .catch(error => {
            document.getElementById('solveerror').innerHTML = 'Error: ' + error.message;
        })
        .finally(() => {
            document.getElementById('solvehelp').style.display = 'none';
        });
};

const updateAnswerButtonsForQuestionType = (questionType) => {
    if (quizTimer.possibilitiesByType[questionType] !== undefined) {
        document.getElementById('answerresponse').innerHTML = '&nbsp;';
        const answerButtonsContainer = document.getElementById('answerbuttons');
        answerButtonsContainer.innerHTML = '';

        for (const possibility of quizTimer.possibilitiesByType[questionType]) {
            const btn = document.createElement('button');
            btn.className = 'action answer';
            btn.innerText = possibility['text'];
            btn.onclick = () => {
                const request = new Request(`../../api/answer.php?secret=${quizTimer.secret}&a=${possibility['code']}`);
                request.method = 'GET';
                request.headers.append('Nightbot-User', quizTimer.twitchName);

                fetchJson(request)
                    .then(data => {
                        document.getElementById('answerresponse').innerHTML = `Answer: ${data.result}`;
                        sendMessage(data.result);
                    })
                    .catch(error => {
                        document.getElementById('answerresponse').innerHTML = `Answer: ${error.message}`;
                    });
            };
            answerButtonsContainer.appendChild(btn);
        }
    } else {
        getPossibilitiesForQuestionType(questionType);
    }
};

const getPossibilitiesForQuestionType = (questionType) => {
    fetchJson(`../../api/possibilities.php?secret=${quizTimer.secret}&questiontype=${questionType}`)
        .then(data => {
            if (!data.possibilities) {
                throw new Error(data.result ?? 'No result');
            }
            quizTimer.possibilitiesByType[questionType] = data.possibilities;
            updateAnswerButtonsForQuestionType(questionType);
        })
        .catch(error => {
            document.getElementById('answerbuttons').innerHTML =
                'Error getting buttons for question type ' + questionType + ': ' + error.message;
        });
};

const checkboxes = {
    pause: document.getElementById('pause'),
    stop: document.getElementById('stop-after-question')
};

const togglePause = () => {
    if (checkboxes.pause.checked) {
        cancelScheduledCall();
        updatePageElementsForPausedQuiz();
    } else {
        quizTimer.scheduledCall = setTimeout(callPollRegularly, 3000);
        updatePageElementsForActiveQuiz();
    }
};

const toggleStop = () => {
    if (!checkboxes.stop.checked && !checkboxes.pause.checked && quizTimer.scheduledCall === null) {
        updatePageElementsForActiveQuiz();
        quizTimer.scheduledCall = setTimeout(callPollRegularly, 3000);
    }
    // Otherwise, nothing to do: checking the stop checkbox changes the call to the poll.php file, which will stop
    // itself at the appropriate moment; and if pause is still checked, we need to get the pause checkbox unchecked
    // before the page should make any calls on its own again.
};

const callPollRegularly = () => {
    if (quizTimer.readOnly) {
        setBodyBgColor('#999');
        return;
    }

    const currentTime = new Date().getTime() / 1000;
    if (currentTime - quizTimer.createdAt > 6 * 3600) {
        setBodyBgColor('#f99');
        quizTimer.readOnly = true;
        document.getElementById('time-elapsed-error').style.display = 'block';
        checkboxes.pause.checked = true;
        checkboxes.pause.disabled = true;
        checkboxes.stop.disabled = true;
    } else if (checkboxes.pause.checked) {
        // Update background color to the "paused" color to reset the bgcolor
        // in case we pressed on a manual button
        setBodyBgColor('#ccc');
    } else {
        const variant = checkboxes.stop.checked ? 'timer-stop' : 'timer';
        quizTimer.callPollFile(variant);

        // The number below is how often, in milliseconds, we call this function
        quizTimer.scheduledCall = setTimeout(callPollRegularly, 15000);
    }
};

const updatePageElementsForPausedQuiz = () => {
    setBodyBgColor('#ccc');
    document.title = 'Quiz - timer (paused)';
    document.querySelector('h2').innerText = 'Quiz timer (paused)';
};

const updatePageElementsForActiveQuiz = () => {
    setBodyBgColor('#fff');
    document.title = 'Quiz - timer';
    document.querySelector('h2').innerText = 'Quiz timer';
};

const updatePageElementsForStoppedQuiz = () => {
    setBodyBgColor('#999');
    document.title = 'Quiz - timer (stopped)';
    document.querySelector('h2').innerText = 'Quiz - timer (stopped)';

    for (const element of document.querySelectorAll('input,button')) {
        element.disabled = true;
    }

    document.getElementById('answerresponse').innerHTML = '&nbsp;';
    document.getElementById('answerbuttons').innerHTML = '';

    document.getElementById('quiz-activity-off-btn').disabled = false;
    document.getElementById('turn-quiz-off-section').style.display = 'block';
};

const stopDirectly = () => {
    quizTimer.readOnly = true;

    checkboxes.pause.checked = true;
    cancelScheduledCall();
    solveQuestion();
    updatePageElementsForStoppedQuiz();
};

quizTimer.initializeTimer = () => {
    togglePause();

    window.addEventListener('keydown', (e) => {
        if (e.code === 'KeyP') {
            const pauseCheckbox = checkboxes.pause;
            if (pauseCheckbox && !pauseCheckbox.disabled) {
                pauseCheckbox.checked = !pauseCheckbox.checked;
                pauseCheckbox.dispatchEvent(new Event('change'));
            }
        } else if (e.code === 'KeyS') {
            const stopCheckbox = checkboxes.stop;
            if (stopCheckbox && !stopCheckbox.disabled) {
                stopCheckbox.checked = !stopCheckbox.checked;
                stopCheckbox.dispatchEvent(new Event('change'));
            }
        } else if (e.code === 'KeyQ') {
            if (!quizTimer.readOnly) {
                document.getElementById('show-question-btn').click();
            }
        } else if (e.code === 'KeyN') {
            if (!quizTimer.readOnly) {
                document.getElementById('new-question-btn').click();
            }
        }
    });

    callPollRegularly();
};

// Initialize change handlers
checkboxes.pause.onchange = togglePause;
checkboxes.stop.onchange = toggleStop;
document.getElementById('stop-directly-btn').onclick = stopDirectly;
document.getElementById('show-question-btn').onclick = () => quizTimer.callPollFile('');
document.getElementById('new-question-btn').onclick = () => quizTimer.callPollFile('silentnew');
