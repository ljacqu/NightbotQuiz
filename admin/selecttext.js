// https://stackoverflow.com/a/1173319
function selectAndCopyText(container) {
    const range = document.createRange();
    range.selectNode(container);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);

    navigator.clipboard.writeText(container.innerText.trim());
}
