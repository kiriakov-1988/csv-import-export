<h1 class="h3 pt-5">Выберите файл для импорта на сервер</h1>
<p class="text-monospace text-info">(только CSV формат, размер не более 1 Мб)</p>

<form enctype="multipart/form-data" method="post" action="/import-csv" onsubmit="return checkForm(this)">

    <input type="hidden" name="MAX_FILE_SIZE" value="1048576" />

    <p class="pl-4" style="line-height: 3">
        <input name="userfile" type="file" id="file" />

        <input class="btn btn-success" type="submit" value="Import" />
    </p>
</form>

<?php include 'session-message.php'?>

<form method="post" action="/clear-all-records">
        <input class="btn btn-danger" type="submit" name="clear" value="Clear all records" onclick="return allowDeletion();" />
</form>

<p class="p-4">
    <a href="/view-results" class="btn btn-outline-primary w-25">View results</a>
</p>

<script src="/js/form-checker.js"></script>