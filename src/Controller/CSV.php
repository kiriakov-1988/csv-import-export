<?php

namespace App\Controller;


use App\Model\DB;

/**
 * Class CSV
 * Основной класс приложения, который выполняет обработку данных в csv-формате.
 * Выполняет их приобразование как для импорта в базу данных, так и для экспорта.
 *
 * Некоторую логику данного класса, согласно MVC, возможно нужно
 * перенести в Модель (отдельный класс, а не имеющийся DB).
 * Но согласно MVC, логика приложения может находиться и в Контролере(ах).
 * В данном случае можно было бы перенести большинство проверок (а это восновном приватные методы,
 * но и часть из публичных) в какойто отдельный класс Модели.
 *
 * @package App\Controller
 */
class CSV
{
    /**
     * Хранит заголовки заданого формата, которые допускаются в csv-файлах.
     */
    const CSV_TITLE = [
        'UID',
        'Name',
        'Age',
        'Email',
        'Phone',
        'Gender'
    ];

    /**
     * Максимальный размер файла, доступного для импортирование в базу данных сайта
     */
    const MAX_FILE_SIZE = 1048576;

    /**
     * MIME-тип файла, доступного для импорта.
     */
    const FILE_TYPE = 'text/csv';

    /**
     * Дополнительное значение расширения файла, доступного для импорта.
     * В принципе можно было бы проверять только MIME-тип.
     */
    const FILE_EXTENSION = 'csv';

    /**
     * Выполняет импортирование csv-файла, переданного в соответствующее поле формы на сайте.
     * Перед обработкой файла выполняется предвариательная проверка как самого файла, так и его содержимого.
     * Делегирует отдельные задачи различным приватным методам.
     *
     * В базу данных добавляются строки только с уникальным индефикатором, а с неуникальным - обновляются.
     * Файлы, не соответствующие заданым критериям, отклоняются.
     *
     * Импорт происходит из временного файла, который создается средствами сервера и
     * удаляется автоматически после выполнения данного скрипта.
     * Т.е. импортируемый файл не попадает в папки данного проекта.
     *
     * @return bool
     */
    public function importData(): bool
    {
        if (isset($_FILES['userfile']) || !empty($_FILES['userfile'])) {
            if (!$_FILES['userfile']['error']) {

                $fileExtension = new \SplFileInfo($_FILES['userfile']['name']);
                $fileExtension = $fileExtension->getExtension();

                if (($_FILES['userfile']['size'] <= self::MAX_FILE_SIZE)
                        && ($_FILES['userfile']['type'] == self::FILE_TYPE)
                            && ($fileExtension == self::FILE_EXTENSION)) {

                    $csvFile = new \SplFileObject($_FILES['userfile']['tmp_name']);
                    $csvFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);

                    if ($csvData = $this->getCsvArrayWithoutTitle($csvFile)) {
                        // Выполняться будет только в случае наличия соответствующих данных (и при отсутсвии ошибок
                        // Сообщение самой ошибки уже было сформировано

                        try {
                            $db = new DB();
                            if ($db->addCsvData($csvData)) {
                                $this->addSessionStatus('Csv-данные успешно загруженны в Базу данных!', true);
                            } else {
                                $this->addSessionStatus('Произошла ошибка при загрузке csv-данных в Базу данных!');
                            }

                        } catch (\PDOException $e) {
                            $this->addSessionStatus('Ошибка подключения к базе данных - ' . $e->getMessage() . '!');
                        }
                    }

                } else {
                    $this->addSessionStatus('Превышен размер или неправильное расширение/тип файла !');
                }

            } else {
                $this->addSessionStatus('При загрузе файла произошла ошибка - код "' . $_FILES['userfile']['error'] . '"!');
            }
        } else {
            $this->addSessionStatus('Не был выбран файл для загрузки !');
        }

        // для наглядности просто добавлена небольшая пауза - 0,5 секунд,
        // а то на локальной машине происходит моментальная перезагрузка страницы
        usleep(500000);

        // Тут надо учитывать что согласно php manual не все клиенты (браузеры) могут
        // правильно принять данный относительный адрес.
        // Поэтому в реальном приложении может следует использовать или константу с адресом
        // или получать из переменных масива $_SERVER
        header('Location: /');

        // возвращаем true так как маршрут отработал (здесь и далее)
        return true;
    }

    /**
     * Экспортирует данные из базы даных в соответствующий csv-файл.
     * Сам файл генерируется на лету (т.е. не создается отдельно в папках сайта) и сразу отдается для скачивания.
     * Данные записываются в том же формате, в котором допустим импорт на сайт.
     *
     * @return bool
     */
    public function exportData(): bool
    {
        try {
            $db = new DB();
            $data = $db->getCsvData();

            if ($data) {

                $now = date("Ymd-His");

                $fileName = 'export-from-db(' . $now . ').csv';

                header('Content-Type: application/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $fileName);

                $fp = fopen('php://output', 'w');

                fputcsv($fp, self::CSV_TITLE);

                foreach ($data as $row) {
                    fputcsv($fp, $row);
                }

            } else {
                $_SESSION['error'] = [
                    'message' => 'В базе данных уже нет доступных csv-данных для скачивания! Возможно их удалили.'
                ];

                header('Location: /view-results');
            }

        } catch (\PDOException $e) {
            // данное сессионное сообщение пока используется дважды, поэтому в отдельный метод не будет вынесено
            $_SESSION['error'] = [
                'message' => 'Ошибка подключения к базе данных - ' . $e->getMessage() . '!'
            ];

            header('Location: /view-results');
        }

        return true;
    }

    /**
     * Выполняет удаление данных из таблицы.
     * Вызвается соответствующей кнопкой (а не просто ссылкой) и post методом
     * для избежания прямого обращения по данному адресу (например, в строке браузера).
     *
     * На стороне клиента желательно получать дополнительное подтверждение перед удалением всех данных.
     * В данном случае была реализована проверка на JS.
     *
     * @return bool
     */
    public function deleteData(): bool
    {
        if (isset($_POST['clear'])) {
            try {
                $db = new DB();
                $result = $db->deleteCsvData();

                if ($result) {

                    $this->addSessionStatus('Csv-данные в таблице базы дынных успешно удалены!', true);

                } else {
                    $this->addSessionStatus('Произошла ошибка при удалении данных из таблицы базы!');
                }

            } catch (\PDOException $e) {
                $this->addSessionStatus('Ошибка подключения к базе данных - ' . $e->getMessage() . '!');
            }
        } else {
            $this->addSessionStatus('К маршруту "Удалить все записи" нельзя напрямую обращаться!');
        }

        header('Location: /');
        return true;
    }

    /**
     * Возвращает данные в виде многомерного ассоциативного масива.
     * Выполняет обработку данных полученных при считывании csv-файла.
     * На выход возвращается преобразованный ассоциативный масив, без строки заголовка csv-файла,
     * с выполненными различными проверками.
     *
     * @param \SplFileObject $csvFile
     * @return array
     */
    private function getCsvArrayWithoutTitle(\SplFileObject $csvFile): array
    {
        $csvData = [];

        $flag = true;

        foreach ($csvFile as $row) {
            // выполняет проверку только один раз в начале
            if ($flag) {
                if (count($row) != count(self::CSV_TITLE)) {
                    $this->addSessionStatus('Количество столбиков в csv-файле не соответствует заданому (файл не корректный)!');
                    break;
                }

                if ($this->checkTitles($row)) {

                    // можно предусмотреть как дополнительный вариант, когда в загружамеых
                    // данных содержатся нужные столбики, но они перемешены

                    $this->addSessionStatus('Порядок/тип столбиков в csv-файле не соответствует заданому (файл не корректный)!');
                    break;
                }

                $flag = false;
            } else {

                if (!$this->checkFormatOfRowData($row)) {
                    // Строки с неверным форматом будут пропускаться ...
                    // Данную проверку можно и пропустить
                    // ожидая что таблица будет содержать коректные данные.
                    // Достаточно первых двух проверок в самом начале цикла
                    continue;
                }
                $csvData[] = $this->getAssocArr($row);
            }
        }

        // дополнительная проверка на наличие уже сообщения (об ошибке) в сессии
        if (!count($csvData) && !isset($_SESSION['status'])) {
            $this->addSessionStatus('В csv-файле нет допустимых данных для загрузки! В т.ч. после проведения валидации над данными ячеек!');
        }

        // в случае ошибки - не соответствие кол-ва или порядка/типа столбиков - данный масив так же будет пуст
        return $csvData;
    }


    /**
     * Проверяет на соответствие заголовков столбцев в csv-файле заданому варианту.
     * Сюда передается первая строка csv-файла в виде масива.
     *
     * @param array $row
     * @return bool
     */
    private function checkTitles(array $row): bool
    {
        if ($row[0] != self::CSV_TITLE[0] &&
            $row[1] != self::CSV_TITLE[1] &&
            $row[2] != self::CSV_TITLE[2] &&
            $row[3] != self::CSV_TITLE[3] &&
            $row[4] != self::CSV_TITLE[4] &&
            $row[5] != self::CSV_TITLE[5]) {
            return true;
        }
        return false;
    }

    /**
     * Выполняется проверка данных на соответствующие типы.
     * Сюда передается каждый раз новая строка csv-файла (кроме первой)
     * и выполняется проверка каждой ячейки на соответствие допустимому типу.
     *
     * @param array $row
     * @return bool
     */
    private function checkFormatOfRowData(array $row): bool
    {
        if (!filter_var($row[0], FILTER_VALIDATE_INT)) {
            return false;
        }

        // $row[1] - Имя - проверять не будем,
        // так как там может быть любого типа/формата "строка"

        if (!filter_var($row[2], FILTER_VALIDATE_INT,
            [
                "options" => [
                    "min_range" => 1,
                    "max_range" => 150
                ]
            ])) {

            // 150 - предполагается как максимальное значение возраста
            return false;
        }

        if (!filter_var($row[3], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!filter_var($row[4], FILTER_VALIDATE_REGEXP,
            [
                "options" => [
                    "regexp" => "/^0[0-9]{10}/"
                ]
            ])) {
            return false;
        }

        if ($row[5] != 'male' && $row[5] != 'female') {
            return false;
        }

        return true;
    }

    /**
     * Возвращает ассоциативный масив
     * Получает на вход строку csv-файла в виде обычного масива
     * и возвращет эти же данные в виде ассоциативного масива.
     *
     * Ключи в масиве не переводятся в нижний регистр, так как база данных (в данном случае MySQL)
     * в имеющихся условиях использования не чувствительна к регистру.
     *
     * @param array $row
     * @return array
     */
    private function getAssocArr(array $row): array
    {
        $assocArr = [
            self::CSV_TITLE[0] => $row[0],
            self::CSV_TITLE[1] => $row[1],
            self::CSV_TITLE[2] => $row[2],
            self::CSV_TITLE[3] => $row[3],
            self::CSV_TITLE[4] => $row[4],
            self::CSV_TITLE[5] => $row[5],
        ];

        return $assocArr;
    }

    /**
     * Создает информационные сообщения, которые выводятся на странице.
     * Подготовливаются как сообщения об успехе, так и об ошибках.
     * Данные сообщения отлавливаются соответствующим шаблоном, который подключен в нужных страницах.
     *
     * Как вариант можно вынести в отдельный класс с соответствующим статическим методом ... ,
     * но пока эта опция используется только в этом классе.
     *
     * @param string $message
     * @param bool $success
     */
    private function addSessionStatus(string $message, bool $success = false): void
    {
        $_SESSION['status'] = [
            'success' => $success,
            'message' => $message
        ];
    }

}