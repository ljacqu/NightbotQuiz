const quizTimer = {

    createdAt: new Date().getTime() / 1000, // seconds
    secret: 'TBD', // overridden by init function
    twitchName: '', // overridden by init function
    isPaused: true,
    isStopped: false, // contrary to isPaused, means a page refresh is required to activate the timer again
    hash: 'notset',
    possibilitiesByType: {}

};

function getCurrentTimeAsString() {
    const currentDate = new Date();
    return String(currentDate.getHours()).padStart(2, '0')
        + ':' + String(currentDate.getMinutes()).padStart(2, '0')
        + ':' + String(currentDate.getSeconds()).padStart(2, '0');
}

function setBodyBgColor(color) {
    // Using background-color instead of background-image here does not override the style from the CSS,
    // because it uses background-image. So we're forced to create a gradient for a single color.
    document.body.style.backgroundImage = `linear-gradient(0deg, ${color}, ${color})`;
}

function fetchJson(request) {
    return fetch(request)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not OK');
            }
            return response.json();
        });
}

quizTimer.sendMessage = (msg) => {
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
                setBodyBgColor(quizTimer.isStopped ? '#999' : '#cfc');
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
            if (data.result.trim() !== '') {
                document.getElementById('result').innerHTML = data.result;
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
                updatePageElementsForStoppedQuiz();
                quizTimer.isStopped = true;
            }

            document.getElementById('time').innerHTML = getCurrentTimeAsString();

            pollErrorElem.style.display = 'none';
            return data.result;
        })
        .then(result => {
            if (result.trim() !== '') {
                quizTimer.sendMessage(result);
            }
            setBodyBgColor('#e5fff9');
        })
        .catch(error => {
            pollErrorElem.style.display = 'block';
            document.getElementById('pollerrormsg').innerHTML = error.message;
            setBodyBgColor('#fff0f0');
        });
};

quizTimer.solveQuestion = () => {
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
                quizTimer.sendMessage(result);
            }
        })
        .catch(error => {
            document.getElementById('solveerror').innerHTML = 'Error: ' + error.message;
        })
        .finally(() => {
            document.getElementById('solvehelp').style.display = 'none';
        });
};

function updateAnswerButtonsForQuestionType(questionType) {
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
                        quizTimer.sendMessage(data.result);
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
}

function getPossibilitiesForQuestionType(questionType) {
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
}

quizTimer.togglePause = () => {
    const isChecked = document.getElementById('pause').checked;
    quizTimer.isPaused = isChecked;
    setBodyBgColor(quizTimer.isPaused ? '#ccc' : '#fff');
};

quizTimer.callPollRegularly = () => {
    if (quizTimer.isStopped) {
        setBodyBgColor('#999');
        return;
    }

    const currentTime = new Date().getTime() / 1000;
    if (currentTime - quizTimer.createdAt > 6 * 3600) {
        setBodyBgColor('#f99');
        document.getElementById('time-elapsed-error').style.display = 'block';
        const pauseCheckbox = document.getElementById('pause');
        pauseCheckbox.checked = true;
        pauseCheckbox.disabled = true;
        return;
    }

    if (!quizTimer.isPaused) {
        const variant = document.getElementById('stop-after-question').checked ? 'timer-stop' : 'timer';
        quizTimer.callPollFile(variant);
    } else {
        // Update background color to the "paused" color to reset the bgcolor
        // in case we pressed on a manual button
        setBodyBgColor('#ccc');
    }

    // The number below is how often, in milliseconds, we call this function
    setTimeout(quizTimer.callPollRegularly, 15000);
};

function updatePageElementsForStoppedQuiz() {
    for (const element of document.querySelectorAll('input,button')) {
        element.disabled = true;
    }
    document.querySelector('h2').innerText += ' (stopped)';
    document.title += ' (stopped)';

    document.getElementById('answerresponse').innerHTML = '&nbsp;';
    document.getElementById('answerbuttons').innerHTML = '';
    setBodyBgColor('#999');
}

quizTimer.stop = () => {
    quizTimer.isPaused = true;
    quizTimer.isStopped = true;

    document.getElementById('pause').checked = true;
    quizTimer.solveQuestion();
    updatePageElementsForStoppedQuiz();
};

quizTimer.initializeTimer = () => {
    quizTimer.togglePause();

    window.addEventListener('keydown', (e) => {
        if (e.code === 'KeyP') {
            const pauseCheckbox = document.getElementById('pause');
            if (pauseCheckbox && !pauseCheckbox.disabled) {
                pauseCheckbox.checked = !pauseCheckbox.checked;
                pauseCheckbox.dispatchEvent(new Event('change'));
            }
        } else if (e.code === 'KeyS') {
            const stopCheckbox = document.getElementById('stop-after-question');
            if (stopCheckbox && !stopCheckbox.disabled) {
                stopCheckbox.checked = !stopCheckbox.checked;
                stopCheckbox.dispatchEvent(new Event('change'));
            }
        }
    });

    quizTimer.callPollRegularly();
};
