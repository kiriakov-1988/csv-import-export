<?php
    // данная переменная получается из App\Controller\View::generate()
    if (count($data['csv'])):
?>

    <h1 class="h3 p-3">Ранее загруженные данные:</h1>

    <p class="text-muted">
        Для сортировки - кликайте по названию столбика. <br>
        Для фильтрации - введите текст в соответствующее поле (и нажмите Enter) или выберите из выпадающего списка.
    </p>

    <table class="table table-bordered table-striped" id="result-table">
        <thead class="thead-dark">
            <tr>
                <th>UID</th>
                <th>Name</th>
                <th>Age</th>
                <th>Email</th>
                <th>Phone</th>
                <th style="min-width: 100px">Gender</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['csv'] as $row): ?>
                <tr>
                    <th><?=$row['uid']?></th>
                    <td><?=$row['name']?></td>
                    <td><?=$row['age']?></td>
                    <td><?=$row['email']?></td>
                    <td><?=$row['phone']?></td>
                    <td><?=$row['gender']?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p>
        <a href="/export-to-csv" class="btn btn-info w-100">Export to CSV</a>
    </p>

<?php else: ?>

    <?php if ($data['error']): ?>

        <p class="text-danger">Ошибка подключения к базе данных - <?=$data['error']?> !'</p>

    <?php else: ?>

        <h1 class="h3 p-3 text-danger">На данный момент в базе еще нету загруженных данных !</h1>

    <?php endif; ?>

<?php endif; ?>

<?php include 'session-message.php'?>

<p class="m-3">
    <a href="/" class="btn btn-outline-primary w-25">Import data</a>
</p>

<script src='/js/tablesort/tablesort.min.js'></script>
<script src='/js/tablesort/tablesort.number.min.js'></script>
<script src='/js/tablefilter/tablefilter.js'></script>