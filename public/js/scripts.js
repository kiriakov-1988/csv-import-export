var elem = document.getElementById("message");
if (elem) {
    setTimeout(function() {
        elem.parentNode.removeChild(elem);
    }, 2000);
}