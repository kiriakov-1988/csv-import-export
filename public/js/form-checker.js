function checkForm(form){
    if (document.getElementById('file').value === "") {
        document.getElementById('file').classList.add('text-danger');
        document.getElementById('file').classList.add('font-weight-bold');
        return false;
    }
    return true;
}

function allowDeletion() {
    return confirm("Really delete all records ?")
}