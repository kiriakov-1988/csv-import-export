<?php

// Отключить вывод ошибок на реальном сервере !!
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Настройки для подключения к базе данных
define('CONFIG_DB', [
    'driver' => 'mysql',
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '789521',
    'name' => 'csv_data',
]);