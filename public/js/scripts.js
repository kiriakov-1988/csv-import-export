var elem = document.getElementById("message");
if (elem) {
    setTimeout(function() {
        elem.parentNode.removeChild(elem);
    }, 5000);
}

if (document.getElementById('result-table')) {
    new Tablesort(document.getElementById('result-table'));

    var tf = new TableFilter(document.querySelector('#result-table'), {
        base_path: '/js/tablefilter/',
        col_5: 'select',
        fixed_headers: true,
        rows_counter: {
            text: 'Users: '
        },
        btn_reset: {
            text: 'Очистить фильтры'
        },
        col_types: [
            'number',
            'string',
            'number',
            'string',
            'string',
            'string'
        ],
        highlight_keywords: true
    });
    tf.init();
}