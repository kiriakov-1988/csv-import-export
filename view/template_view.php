<!doctype html>
<html lang="ru">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?=$data['title'] ?? 'Импорт/экспорт CSV файлов!' ?></title>
    <!-- TODO динамически менять заголовок -->

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">


  </head>
  <body>

      <div class="container text-center">

        <?php include $content_view; ?>

      </div>

      <script src="/js/scripts.js"></script>

  </body>
</html>