function onResolveClick(buttonElem, resultContainerId, promptForDelete) {
    buttonElem.disabled = true;

    if (promptForDelete) {
        const deleteConf = confirm('The question has no answers. Do you want to delete the question?');
        if (deleteConf) {
            fetchPostRequest('overview.php', 'del')
                .then(result => {
                    document.getElementById(resultContainerId).innerText = result;
                })
                .catch(error => {
                    document.getElementById(resultContainerId).innerText = 'Error: ' + error.message;
                })
                .finally(() => {
                    updatePageAfterRequest(buttonElem);
                })
            return;
        }
    }


    fetchPostRequest('overview.php', 'solve')
        .then(result => {
            document.getElementById(resultContainerId).innerText = result;
        })
        .catch(error => {
            document.getElementById(resultContainerId).innerText = 'Error: ' + error.message;
        })
        .finally(() => {
            updatePageAfterRequest(buttonElem);
        })
}

function updatePageAfterRequest(buttonElem) {
    buttonElem.style.display = 'none';
    document.getElementById('last-question').style.textDecoration = 'line-through';
}

function fetchPostRequest(url, postProperty) {
    const formData = new FormData();
    formData.append(postProperty, '1');

    const request = new Request(url, {
        method: 'POST',
        body: formData
    });

    return fetch(request)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.text();
        });
}
