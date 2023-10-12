
function callPoll(secret, variant) {
    const request = new Request(`../api/poll.php?secret=${secret}&variant=${variant}`);
    request.method = 'GET';

    return fetch(request)
        .then((resp) => {
            if (!resp.ok) {
                throw new Error('Network response was not ok');
            }
            return resp.json();
        })
        .then((data) => {
            return data.result;
        })
        .catch((error) => {
            return error.message;
        });
}

function callAnswer(secret, user, answer) {
    const request = new Request(`../api/answer.php?secret=${secret}&a=${answer}`);
    request.method = 'GET';
    request.headers.append('Nightbot-User', 'demo&' + user);

    return fetch(request)
        .then((resp) => {
            if (!resp.ok) {
                throw new Error('Network response was not ok');
            }
            return resp.json();
        })
        .then((data) => {
            return data.result;
        })
        .catch((error) => {
            return error.message;
        });
}

function createListHistoryElement(text, command) {
    const liElem = document.createElement('li');
    liElem.textContent = text;
    liElem.appendChild(document.createTextNode(' ('));

    const spanElem = document.createElement('span');
    spanElem.className = 'command';
    spanElem.textContent = command;

    liElem.appendChild(spanElem);
    liElem.appendChild(document.createTextNode(')'));
    return liElem;
}

function updateHistoryList(listElem, listEntryElem, historyCount) {
    if (historyCount > 0) {
        listElem.prepend(listEntryElem);
        if (historyCount > 5 || historyCount === 1) {
            listElem.removeChild(listElem.lastChild);
        }
    }
}

function createApiTester(secret) {

    const tester = {
        pollHistCount: 0,
        pollHistLastElem: null,
        answerHistCount: 0,
        answerHistLastElem: null
    };

    const createHistListElem = (text, cmd) => {
        const liElem = document.createElement('li');
        liElem.innerText = text;

        const spanElem = document.createElement('span');
        spanElem.className = 'command';
        liElem.appendChild(spanElem);
        return liElem;
    };

    tester.callPoll = async (variant) => {
        callPoll(secret, variant)
            .then(result => {
                document.getElementById('pollresult').innerText = result;

                const historyList = document.getElementById('pollhistory');
                updateHistoryList(historyList, tester.pollHistLastElem, tester.pollHistCount);

                ++tester.pollHistCount;
                tester.pollHistLastElem = createListHistoryElement(result, `!q ${variant}`.trim());
            });
    };

    tester.callAnswer = async (answer, name) => {
        callAnswer(secret, name, answer)
            .then(result => {
                document.getElementById('anwserresult').innerText = result;

                const historyList = document.getElementById('answerhistory');
                updateHistoryList(historyList, tester.answerHistLastElem, tester.answerHistCount);

                ++tester.answerHistCount;
                tester.answerHistLastElem = createListHistoryElement(result, `${name}: !a ${answer}`);
            });
    };

    return tester;
}



