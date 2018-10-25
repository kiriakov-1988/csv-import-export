<?php

namespace App\Controller;


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

    public function importData()
    {
        if (isset($_FILES['userfile']) || !empty($_FILES['userfile'])) {
            if (!$_FILES['userfile']['error']) {

                if (($_FILES['userfile']['size'] <= self::MAX_FILE_SIZE) && ($_FILES['userfile']['type'] == self::FILE_TYPE)) {

                    $csvFile = new \SplFileObject($_FILES['userfile']['tmp_name']);
                    $csvFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);

                    echo '<pre>';

                    $csvData = $this->getCsvArrayWithoutTitle($csvFile);
                    // TODO проверить на пустоту

                    print_r($csvData);

                    echo '</pre>';

                } else {
                    echo 'Превышен размер';
                }

            } else {
                // Ошибка - вернуть код ошибки
            }
        } else {
            // Не был выбран файл для загрузки !
        }

        return true;
    }

    public function exportData()
    {
        echo 'export data';
        return true;
    }

    private function getCsvArrayWithoutTitle(\SplFileObject $csvFile): array
    {
        $csvData = [];

        $flag = true;

        foreach ($csvFile as $row) {
            // выполняет проверку только один раз в начале
            if ($flag) {
                if (count($row) != count(self::CSV_TITLE)) {
                    echo 'кол-во столбиков не равняется заданому';
                    break;
                }

                if ($this->checkTitles($row)) {
                    echo 'Порядок/тип столбиков не соответствуют заданому';
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
                $csvData[] = $row;
            }
        }

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
}