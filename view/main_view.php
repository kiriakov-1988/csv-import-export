<div class="container text-center pt-5">

    <h1 class="h3">Выберите файл для импорта на сервер</h1>
    <p class="text-monospace text-info">(только CSV формат, размер не более 1 Мб)</p>

    <form enctype="multipart/form-data" method="post" action="/import-csv">

        <input type="hidden" name="MAX_FILE_SIZE" value="1048576" />

        <p class="pl-4" style="line-height: 3">
            <input name="userfile" type="file" id="file" />

            <input class="btn btn-outline-success" type="submit" value="Import" />
        </p>
    </form>

</div>
