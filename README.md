MVC web-application for import/export csv-data 
====

Данное веб-приложение выполняет импорт данных из **csv**-файла в Базу данных. Перед обработкой данных, сам файл и данные в нем соответсвующе проверяются.

На главной странице приложения находится форма для выбора файла, а так же кнопка удаления всех записей из базы данных и ссылка на страницу с таблицей, отображающей уже загруженные ранее данные.

Средствами **JS** реализована дополнительная проверка на указание файла для загрузке в соответствующем поле.
Так же на стороне клиента выполняется предварительная проверка (подтверждение) перед удалением всех данных в базе.

Данные из файла (_в csv-формате, и размером не больше 1 МБ_) импортируются в базу данных на следующих условиях: 
- Колонка **UID** — уникальна для записи в csv-файле, поэтому если в БД нет записи с данным UID, то она создаётся, а если уже есть, то данные в БД для этой строки обновляются.
- Если же файл имеет неправильную структуру, то данные в БД не пишутся, а пользователю выдаётся ошибка о том, что файл не корректный.

***

На странице с результатами ранее загруженных данных предусмотренна кнопка для скачивания (экспорта) все данных из таблицы базы данных.
Сами данные в таблице можно как сортировать, так и фильтровать, при этом в случае экспорта - данные берутся непосредственно из базы.
_Сортировка и фильтрация реализована путем подключения сторонних **JS** библиотек (всё-таки здесь пример **PHP** приложения)._

Так же пользователю отображаются различные информационные сообщения, как об успехе, так и о неудаче.

Считываение csv-данных с импортированного файла происходит непосредственно с папки сервера для временных файлов, поэтому "загруженный" файл удаляется после выполнения самого процеса считывания данных.
Файл с данным для экспорта генерируется на лету и отдается пользователю сразу для скачивания. В название файла добавляется метка с текущей датой и временем.

## Установка

Для работы приложения необходима 7-ая версия `PHP` (разработка выполнялась на версии `7.2`).
К базе данных (`MySQL`) обращение выполняется через `PDO`.

Перед началом работы с приложением, необходимо указать настройки для подключения к базе данных в файле `/settings/config.php`.
Далее выполнить соответствующую подготовку базы данных и таблицу в ней - выполнив из корня проекта приложения в терминале - `php settings/create-db-and-table.php`.
Создать базу данных и импорт дампа таблицы (`/settings/dump/csv.sql`) так же можно выполнить и через **Админку СУБД**.

***

Для быстрого ознакомления с приложением можно воспользоваться возможностями Встроенного веб-сервера в PHP, выполнив из корневой папки проекта следующую команду в консоли - `php -S 127.0.0.1:8000 -t public`.
А доступ к приложению на локальной машине можно получить по ссылке [http://127.0.0.1:8000/](http://127.0.0.1:8000/).

Для запуска данного приложения на сервере Apache2 уже имеются некоторые настройки: в папке `/public/` находится файл `.htaccess`, который выполняет необходимую обработку поступающих запросов и пеернаправляет на ФронтКонтроллер приложения.
Так же для работы сайта, необходимо добавить следующие настройки в сам сервер **Apache2**, например, можно добавить следующий код в конец файла `/etc/apache2/sites-available/000-default.conf`
```
<VirtualHost *:80>

  DocumentRoot /var/www/app/public

  <Directory /var/www/app/public/>

      AllowOverride All

  </Directory>

  ErrorLog ${APACHE_LOG_DIR}/error.log
  CustomLog ${APACHE_LOG_DIR}/access.log combined

</VirtualHost>
```

***
Посмотреть как выглядить примерно данное приложение можно по ссылке [GitHub Page of this project](https://kiriakov-1988.github.io/csv-import-export/ "Example of app (only HTML and JS)").
Но там доступен только функционал фронтенда...