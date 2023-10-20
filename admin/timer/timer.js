const quizTimer = {

    secret: 'TBD', // overridden by init function
    isActive: false,
    hash: 'notset',
    createdAt: new Date().getTime() / 1000 // seconds

};

function getCurrentTimeAsString() {
    const currentdate = new Date();
    return String(currentdate.getHours()).padStart(2, '0')
        + ":" + String(currentdate.getMinutes()).padStart(2, '0')
        + ":" + String(currentdate.getSeconds()).padStart(2, '0');
}

function setBodyBgColor(color) {
    // Using background-color instead of background-image here does not override the style from the CSS,
    // because it uses background-image. So we're forced to create a gradient for a single color.
    document.body.style.backgroundImage = `linear-gradient(0deg, ${color}, ${color})`;
}

quizTimer.sendMessage = (msg) => {
    const formData = new FormData();
    formData.append('msg', msg);
    const request = new Request('send_message.php', {
        method: 'POST',
        body: formData
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

quizTimer.callPollFile = (variant) => {
    const requestUrl = `../../api/poll.php?secret=${quizTimer.secret}&variant=${variant}&hash=${quizTimer.hash}`;
    const request = new Request(requestUrl, {
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
                quizTimer.hash = data.hash;
            }

            document.getElementById('time').innerHTML = getCurrentTimeAsString();

            pollErrorElem.style.display = 'none';
            return data.result;
        })
        .then((result) => {
            if (result.trim() !== '') {
                quizTimer.sendMessage(result);
            }
            setBodyBgColor('#e5fff9');
        })
        .catch((error) => {
            pollErrorElem.style.display = 'block';
            document.getElementById('pollerrormsg').innerHTML = error.message;
            setBodyBgColor('#fff0f0');
        });
};

quizTimer.togglePause = () => {
    const isChecked = document.getElementById('pause').checked;
    quizTimer.isActive = !isChecked;
    setBodyBgColor(quizTimer.isActive ? '#fff' : '#ccc');
};

quizTimer.callPollRegularly = () => {
    const currentTime = new Date().getTime() / 1000;
    if (currentTime - quizTimer.createdAt > 6 * 3600) {
        setBodyBgColor('#f99');
        document.getElementById('time-elapsed-error').style.display = 'block';
        return;
    }

    if (quizTimer.isActive) {
        quizTimer.callPollFile('timer');
    } else {
        // Update background color to the "paused" color to reset the bgcolor
        // in case we pressed on a manual button
        setBodyBgColor('#ccc');
    }

    // The number below is how often, in milliseconds, we call poll.php?variant=timer
    setTimeout(quizTimer.callPollRegularly, 15000);
};

function initializeTimer(secret) {
    quizTimer.secret = secret;
    quizTimer.togglePause();

    window.addEventListener('keyup', (e) => {
        if (e.code === 'KeyP') {
            const pauseCheckbox = document.getElementById('pause');
            pauseCheckbox.checked = !pauseCheckbox.checked;
            pauseCheckbox.dispatchEvent(new Event('change'));
        }
    });

    quizTimer.callPollRegularly();
}
