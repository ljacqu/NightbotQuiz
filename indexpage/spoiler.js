function toggleSpoiler(elem) {
    elem.classList.toggle('visible');
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
