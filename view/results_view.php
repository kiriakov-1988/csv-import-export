<h1 class="h3">Результат ранее загруженных данных</h1>

<?php if (count($data['csv'])): ?>

    <table class="table table-striped">
        <thead class="thead-dark">
            <tr>
                <th>UID</th>
                <th>name</th>
                <th>age</th>
                <th>email</th>
                <th>phone</th>
                <th>gender</th>
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

    <a href="/export-to-csv" class="btn btn-info w-100">Export to CSV</a>

<?php else: ?>

    <p class="text-danger">На данный момент в базе еще нету загруженных данных !</p>

<?php endif; ?>

<p class="m-3">
    <a href="/" class="btn btn-outline-primary w-25">Import data</a>
</p>