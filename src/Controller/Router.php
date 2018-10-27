<?php

namespace App\Controller;


/**
 * Class Router
 * Обрабатывает все запросы к данному приложению
 * и перенаправляет на нужный обработчик (класс/метод)
 * @package Controller
 */
class Router
{
    /**
     * Список поддерживаемых маршрутов
     */
    const ROUTES = [
        'import-csv' => 'CSV/importData',
        'view-results' => 'View/getResults',
        'export-to-csv' => 'CSV/exportData',
        'clear-all-records' => 'CSV/deleteData',
        '' => 'View/getIndexPage',
    ];

    /**
     * Неймспейс основных классов.
     * В данном случае, при нахождении всех классов контролерров и Роутера в одном месте,
     * его можно и не указывать.
     */
    const NAMESPACE_FOR_CLASS = '\App\Controller\\';

    /**
     * Основной метод класса, который обрабатывает все поступающие запросы
     */
    public function run(): void
    {
        $uri = $this->getURI();

        foreach (self::ROUTES as $uriPattern => $path) {
            // так как здесь обычные постоянные маршруты,
            // то можно просто сравнивать ("==") строки
            // но тут как пример для более машстабного приложения
            if (preg_match("~^{$uriPattern}$~", $uri)) {

                $segments = explode('/', $path);

                $controllerName = self::NAMESPACE_FOR_CLASS . array_shift($segments);

                $actionName = array_shift($segments);

                $controllerObject = new $controllerName;

                $result = $controllerObject->$actionName();

                if ($result != null) {
                    return;
                }
            }
        }

        header("HTTP/1.1 404 Not Found");
        $view = new View();
        $view->showError();
        return;
    }

    /**
     * Обрабатывает введенный адрес и возвращает его
     * @return string
     */
    private function getURI():string
    {
        if (! empty($_SERVER['REQUEST_URI'])) {
            return trim($_SERVER['REQUEST_URI'], '/');
        }

        return '';
    }
}