<!doctype html>
<html lang="ru">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?=$data['title'] ?? 'Импорт/экспорт CSV файлов!' ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">

  </head>
  <body>

      <div class="container text-center">

        <?php
            // данная переменная получается из App\Controller\View::generate()
            // в т.ч. и заголовок страницы
            include $content_view;
        ?>

      </div>

      <script src="/js/scripts.js"></script>

  </body>
</html>