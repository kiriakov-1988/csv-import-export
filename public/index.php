<?php

require_once '../settings/config.php';
require_once '../vendor/autoload.php';

// для вывода сообщений об успехе или ошибке
session_start();

$router = new App\Controller\Router();
$router->run();