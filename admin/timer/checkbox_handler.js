(function () {
    for (let checkbox of document.getElementsByClassName('smart-checkbox')) {
        if (!checkbox.id) {
            console.error('Smart checkbox does not have an ID');
            continue;
        }

        // Initialize value
        const value = localStorage.getItem('nq-' + checkbox.id);
        // Do nothing if we don't have a value in local storage, and the checkbox is defined to be checked
        if (value !== null || !checkbox.checked) {
            checkbox.checked = !!value;
            onSmartCheckboxChange(checkbox);
        }

        checkbox.addEventListener('change', e => {
            onSmartCheckboxChange(checkbox);
        });
    }
})();

function onSmartCheckboxChange(elem) {
    const isChecked = elem.checked;
    localStorage.setItem(`nq-${elem.id}`, isChecked ? 'true' : '');
    if (elem.dataset.textId) {
        if (!isChecked) {
            document.getElementById(elem.dataset.textId).style.textDecoration = 'line-through';
        } else {
            document.getElementById(elem.dataset.textId).style.textDecoration = 'none';
        }
    }
}
