<?php

require_once 'config.php';

// Небольшой скрипт для подготовки соответствующей базы данных с таблицей,
// необходимых для работы данного приложения.

// Создать базу данных и импорт дампа таблицы так же можно выполнить через Админку СУБД.

echo "1. Подготовка соответствующей структуры базы данных!\n";

// Добавлены паузы, что б показать процесс установки во времени (иначе все выполняется моментально).
// Этот момент не обязателен.
usleep(200000);

try {
    $connection = new PDO(CONFIG_DB['driver'].':host='. CONFIG_DB['host'], CONFIG_DB['user'], CONFIG_DB['pass']);

    $sqlCreateDB = 'CREATE DATABASE IF NOT EXISTS ' . CONFIG_DB['name'] . ' CHARACTER SET utf8 COLLATE utf8_unicode_ci;';

    $stmt = $connection->prepare($sqlCreateDB);

    if ($success = $stmt->execute()) {

        // переподключаем соединение с указанием базы данных, иначе не получается проверить наличие таблицы,
        // даже если предварительно в запросе указать " 'USE ' . CONFIG_DB['name'] ';' "
        // запрос с импортом таблицы при этом работал бы.
        $connection = new PDO(CONFIG_DB['driver'].':host='. CONFIG_DB['host'] .';dbname='. CONFIG_DB['name'],
            CONFIG_DB['user'],
            CONFIG_DB['pass']);

        $sqlCHeckIssetTable = "SHOW TABLES LIKE 'csv'";

        $stmt2 = $connection->query($sqlCHeckIssetTable);

        $table = $stmt2->fetch(PDO::FETCH_NUM);

        if (isset($table[0])) {
            echo "2. Развертывание приложения уже было раннее выполнено!\n";
            exit();
        }

        echo '2. База данных ' . CONFIG_DB['name'] . ' создана!' . "\n";
        usleep(200000);

        $tableDump = __DIR__ . '/dump/csv.sql';

        if (file_exists($tableDump)) {
            $sqlImportTable = file_get_contents($tableDump);

            $stmt3 = $connection->prepare($sqlImportTable);

            if ($success = $stmt3->execute()) {

                echo "3. В базу данных импортирована основная таблица 'csv'!\n";
                usleep(200000);
                echo "4. Развертывание приложения выполнено успешно!\n";

            } else {
                echo "3. Ошибка - в базу данных не получилось импортировать таблицу!\n";
            }

        } else {
            echo " 3. Ошибка - не обнаружен дамп таблицы для импорта!\n";
        }

    } else {
        echo '2. Ошибка - база данных не создана!'."\n";
    }

} catch (PDOException $e) {
    echo "   Ошибка базы данных - {$e->getMessage()} !\n";
}