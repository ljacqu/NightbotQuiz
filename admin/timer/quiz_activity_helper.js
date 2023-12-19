(() => {
    const saveQuizActivitySetting = (activeMode, onSuccessFn, errorContainerId) => {
        const formData = new FormData();
        formData.append('mode', activeMode);
        const request = new Request('js_save_activity_mode.php', {
            method: 'POST',
            body: formData
        });

        fetch(request)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not OK');
                }
                return response.json();
            })
            .then(data => {
                if (!data.result || !data.result.startsWith('Success')) {
                    throw new Error(data.result ?? 'No response');
                }
                onSuccessFn();
            })
            .catch(error => {
                document.getElementById(errorContainerId).innerText = `Error: ${error.message}`;
            });
    };

    const turnQuizOnBtn = document.getElementById('quiz-activity-on-btn');
    turnQuizOnBtn.onclick = () => {
        const successHandler = () => {
            document.getElementById('quiz-settings-off').style.display = 'none';
        };
        saveQuizActivitySetting('ON', successHandler, 'quiz-activity-on-error');
    };

    const turnQuizOffBtn = document.getElementById('quiz-activity-off-btn');
    turnQuizOffBtn.onclick = () => {
        const successHandler = () => {
            document.getElementById('quiz-activity-off-result').innerText = 'Quiz activity has been disabled.';
            turnQuizOffBtn.disabled = true;
        };
        saveQuizActivitySetting('OFF', successHandler, 'quiz-activity-off-result');
    };
})();
