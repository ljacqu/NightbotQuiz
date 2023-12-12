function onUserFieldChange(elem) {
    document.getElementById('userbtn').disabled = false;

    const container = elem.parentElement;
    const emptyFields = [];
    const allFields = container.querySelectorAll('input');
    for (const field of allFields) {
        if (field.type === 'text' && !field.value.trim()) {
            emptyFields.push(field);
        }
    }

    for (let i = emptyFields.length - 2; i >= 0; --i) {
        emptyFields[i].remove();
    }
    if (emptyFields.length === 0 && allFields.length < 10) {
        const newField = document.createElement('input');
        newField.type = 'text';
        newField.style.display = 'block';

        container.appendChild(newField);
        newField.onkeyup = () => onUserFieldChange(newField);
    }
}

function onUserFieldButton() {
    const seenUsers = {};
    const usersToList = [];
    for (const field of document.querySelectorAll('#userform input')) {
        const value = field.value.trim();
        if (value) {
            if (!seenUsers[value]) {
                usersToList.push(value);
            } else {
                seenUsers[value] = true;
            }
        }
    }

    const nameList = usersToList.join(',');
    // allhist is used for the languages quiz
    window.location.search = '?allhist&users=' + encodeURIComponent(nameList);
}
