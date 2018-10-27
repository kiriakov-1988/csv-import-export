<?php

namespace App\Controller;


use App\Model\DB;

class CSV
{
    const CSV_TITLE = [
        'UID',
        'Name',
        'Age',
        'Email',
        'Phone',
        'Gender'
    ];

    const MAX_FILE_SIZE = 1048576;

    const FILE_TYPE = 'text/csv';

    const FILE_EXTENSION = 'csv';

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

    // TODO отметить в документации, что данный функционал можно перенести и в МОДЕЛЬ
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
                    $this->addSessionStatus('Порядок/тип столбиков в csv-файле не соответствует заданому (файл не корректный)!');
                    break;
                }

                $flag = false;
            } else {

                if (!$this->checkFormatOfRowData($row)) {
                    // Строки с неверным форматом будут пропускаться ...
                    // Данную проверку можно и пропустить
                    // ожидая что таблика будет содержать коректные данные.
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

    // как вариант можно вынести в отдельный класс с соответствующим статическим методом ...
    // но пока эта опция используется только в этом классе
    private function addSessionStatus(string $message, bool $success = false): void
    {
        $_SESSION['status'] = [
            'success' => $success,
            'message' => $message
        ];
    }

}