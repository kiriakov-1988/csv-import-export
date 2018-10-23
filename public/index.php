<?php

require_once '../settings/config.php';
require_once '../vendor/autoload.php';

$router = new App\Controller\Router();
$router->run();