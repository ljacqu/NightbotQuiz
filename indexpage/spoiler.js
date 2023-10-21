function toggleSpoiler(elem) {
    if (elem.classList.contains('visible')) {
        elem.classList.remove('visible');
    } else {
        elem.classList.add('visible');
    }
}

function toggleAllSpoilers(titleElem) {
    const answerElements = document.getElementsByClassName('answer');

    let isAnyHidden = false;
    for (const answerElem of answerElements) {
        if (!answerElem.classList.contains('visible')) {
            answerElem.classList.add('visible');
            isAnyHidden = true;
        }
    }

    if (!isAnyHidden) {
        for (const answerElem of answerElements) {
            answerElem.classList.remove('visible');
        }
    }
}
