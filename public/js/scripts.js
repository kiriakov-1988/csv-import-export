var elem = document.getElementById("message");
if (elem) {
    setTimeout(function() {
        elem.parentNode.removeChild(elem);
    }, 5000);
}

if (document.getElementById('result-table')) {
    new Tablesort(document.getElementById('result-table'));
}