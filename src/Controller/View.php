<?php

namespace App\Controller;


use App\Model\DB;

/**
 * Class View
 * Данный класс отвечает за генерацию различных страниц.
 * Осуществляет связь между ФронтКонтроллером и шаблонами представления контента на страницах сайта.
 * При необходимости с подгрузкой соответствующих данных из Модели (класс DB)
 *
 * @package App\Controller
 */
class View
{
    /**
     * Отображает главную страницу сайта
     * Дополнительно можно так же передать вторым параметром определенные данные.
     * Например в формате масива [ 'title' => 'Заголовок страницы' ]
     * В случае отсутствия переданого заголовка, в шаблоне используется значение по умолчанию.
     *
     * @return bool
     */
    public function getIndexPage(): bool
    {
        $this->generate('main_view.php');
        return true;
    }

    /**
     * Отображает страницу с результатами.
     * Дополнительно в шаблон передаются данные из БД
     * для формирования результирующей таблицы.
     *
     * В случае ошибки запроса к БД или отсутствия данных в таблице,
     * в шаблоном выводится сообщение об отсутствии данных.
     *
     * @return bool
     */
    public function getResults(): bool
    {
        try {
            $db = new DB();
            $csv = $db->getCsvData();
            $error = false;
        } catch (\PDOException $e) {
            $csv = [];
            $error = $e->getMessage();
        }


        $data = [
            'title' => 'CSV result from DB',
            'csv'   => $csv,
            'error' => $error
        ];

        $this->generate('results_view.php', $data);
        return true;
    }

    /**
     * Отображает страницу, информирующую о 404 ошибке.
     * Данная страница выводится при обращении к странице,
     * маршрут которой не задан/не предусмотрен в Роутере
     */
    public function showError(): void
    {
        $this->generate('404-error_view.php', [
            'title' => 'Ошибка 404',
        ]);
    }

    /**
     * Генерирует различные страницы, которые отдаются пользователю.
     * Переданные параметры используются далее в соответствующих шаблонах
     *
     * @param string $content_view шаблон, который будет отображаться
     * @param array|null $data     информация, отображаемая в шаблоне
     */
    private function generate(string $content_view, array $data = null): void
    {
        include '../view/template_view.php';
    }

}