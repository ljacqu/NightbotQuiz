(function handleSmartCheckboxes() {
    for (let checkbox of document.getElementsByClassName('smart-checkbox')) {
        if (!checkbox.id) {
            console.error('Smart checkbox does not have an ID');
            continue;
        }

        // Initialize value
        const value = localStorage.getItem('nq-' + checkbox.id);
        if (!(value === null && checkbox.checked)) {
            checkbox.checked = !!value;
            onSmartCheckboxChange(checkbox);
        }

        checkbox.addEventListener('change', e => {
            onSmartCheckboxChange(checkbox);
        });
    }
})();

function onSmartCheckboxChange(elem) {
    const value = elem.checked;
    localStorage.setItem(`nq-${elem.id}`, value ? 'true' : '');
    console.log(elem.dataset.textId);
    if (elem.dataset.textId) {
        if (!value) {
            document.getElementById(elem.dataset.textId).style.textDecoration = 'line-through';
        } else {
            document.getElementById(elem.dataset.textId).style.textDecoration = 'none';
        }
    }
}
