function toggleSpoiler(elem) {
    if (elem.dataset.visible) {
        elem.classList.remove('visible');
        delete elem.dataset.visible;
    } else {
        elem.classList.add('visible');
        elem.dataset.visible = '1';
    }
}

function toggleAllSpoilers(titleElem) {
    if (titleElem.dataset.visible) {
        for (const answerElem of document.getElementsByClassName('answer')) {
            answerElem.classList.remove('visible');
            delete answerElem.dataset.visible;
        }
        delete titleElem.dataset.visible;
    } else {
        for (const answerElem of document.getElementsByClassName('answer')) {
            answerElem.classList.add('visible');
            answerElem.dataset.visible = '1';
        }
        titleElem.dataset.visible = '1';
    }
}
