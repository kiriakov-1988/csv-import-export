<?php

namespace App\Model;


/**
 * Class DB
 * Данный класс предназначен для работы с базой данной.
 * Выполняет все запросы к ней.
 * В структуре данного MVC приложения является Моделью.
 *
 * @package App\Model
 */
class DB
{
    /**
     * Настройки подключения к бае данных.
     * Подгружаются в константу класса, для наглядности при дальнейшем использовании.
     */
    const DB = CONFIG_DB;

    /**
     * Хранит ссылку на подключение к базе данных.
     * @var \PDO
     */
    private $connection;

    /**
     * DB constructor.
     * Выполняет подключение к базе данных.
     */
    public function __construct()
    {
        $this->connection = new \PDO(self::DB['driver'].':host='. self::DB['host'] .';dbname='. self::DB['name'],
            self::DB['user'],
            self::DB['pass']);
    }

    /**
     * Выполняет внесение данных в таблицу.
     * Данные могут как добавляться, так и обновляться в зависимость от самих данных.
     * Происходят различные проверки и делегаирования заданий другим приватным методам.
     *
     * Используется в App\Controller\CSV::importData()
     *
     * @param array $csvData
     * @return bool
     */
    public function addCsvData(array $csvData): bool
    {
        // По хорошему при добавлении масива данных можно использовать транзакции,
        // и в случае ошибки возвращать Базу данных к первоначальному состоянию.
        // Но здесь будет реализован базовый функционал с сохранением удавшихся подзапросов.

        // Если бы база данных была пустой, то все данные сразу можно было внести одним запросом.
        if ($this->checkNotEmptyTable()) {

            $filtredCsvData = [];

            foreach ($csvData as $row) {
                if ($this->checkIssetUid($row['UID'])) {

                    if (!$this->updateRow($row)) {
                        return false;
                    }

                } else {
                    $filtredCsvData[] = $row;
                }
            }

            if (count($filtredCsvData)) {

                if (!$this->pdoMultiInsert($filtredCsvData)) {
                    return false;
                }

            }

            return true;

        } else {

            if ($this->pdoMultiInsert($csvData)) {
                return true;
            }

        }

        return false;

    }

    /**
     * Возвращает массив с данными из базы даннных.
     * В случае ошибки или отсутсвия данных возвращается пустой масив.
     *
     * Используется в App\Controller\View::getResults()
     * Используется в \App\Controller\CSV::exportData()
     *
     * @return array
     */
    public function getCsvData(): array
    {
        $sqlQuery = 'SELECT * FROM `csv` ORDER BY `uid`';

        $stmt = $this->connection->prepare($sqlQuery);

        if ($success = $stmt->execute()) {
            $data = [];

            while ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $result;
            }

            return $data;
        }

        return [];
    }

    /**
     * Выполняет удаление данных из таблицы.
     * Удаление происходит путем очистки таблицы.
     *
     * Используется в App\Controller\CSV::deleteData()
     *
     * @return bool
     */
    public function deleteCsvData(): bool
    {
        $sqlQuery = 'TRUNCATE TABLE `csv`';

        $stmt = $this->connection->prepare($sqlQuery);

        if ($success = $stmt->execute()) {

            return true;
        }

        return false;

    }

    /**
     * Выполняет множественное внесенние данных в таблицу.
     * Добавление нескольких строк данных выполняется за счет одного запроса.
     * За счет этого достигается оптимизация количества обращений к серверу базы данных.
     *
     * Но в случае очень длинной строки запроса можно превысить допустимую длину sql запроса.
     * Так как по умолчанию максимальное значение составляет 16 МБ, а размер импортированых
     * файлов не больше 1 МБ, данная ситуация в данном случае маловероятна.
     *
     * Используется готовое решение http://thisinterestsme.com/pdo-prepared-multi-inserts/
     *
     * @param array $data
     * @return bool
     */
    private function pdoMultiInsert(array $data): bool
    {

        //Will contain SQL snippets.
        $rowsSQL = array();

        //Will contain the values that we need to bind.
        $toBind = array();

        // В данном случае ключи масива, не в нижнем регистре, хотя заголовки в таблице базы данных все в нижнем.
        // Но база данных (в данном случае MySQL) в имеющихся условиях использования не чувствительна к регистру.
        //Get a list of column names to use in the SQL statement.
        $columnNames = array_keys($data[0]);

        //Loop through our $data array.
        foreach($data as $arrayIndex => $row){
            $params = array();
            foreach($row as $columnName => $columnValue){
                $param = ":" . $columnName . $arrayIndex;
                $params[] = $param;
                $toBind[$param] = $columnValue;
            }
            $rowsSQL[] = "(" . implode(", ", $params) . ")";
        }

        //Construct our SQL statement
        $sql = "INSERT INTO `csv` (" . implode(", ", $columnNames) . ") VALUES " . implode(", ", $rowsSQL);

        //Prepare our PDO statement.
        $pdoStatement = $this->connection->prepare($sql);

        //Bind our values.
        foreach($toBind as $param => $val){
            $pdoStatement->bindValue($param, $val);
        }

        //Execute our statement (i.e. insert the data).
        return $pdoStatement->execute();
    }

    /**
     * Проверяет на на наличие записей в таблице.
     * В случае успеха возвращается количество записей.
     * Само количество не используется, просто возвращается,
     * главное что б было больше нуля.
     *
     * Ноль будет возвращен в случае отсутствия записей или ошибки запроса.
     * При наличии ошибки, метод множественной вставки вернет так же ошибку,
     * если тут все таки были записи с теми же индексами что и в новом запросе.
     *
     * Ошибка отдельно не возвращается, так как множественная вставка
     * выполнится либо полностью, либо никак.
     *
     * @return int
     */
    private function checkNotEmptyTable():int
    {
        $sqlQuery = 'SELECT COUNT(`uid`) AS `total_rows` FROM `csv`';

        $stmt = $this->connection->prepare($sqlQuery);

        if ($success = $stmt->execute()) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result['total_rows'];
        }

        return 0;
    }

    /**
     * Проверяет наличие записи с соответствующим идентификатором.
     * В случае наличия такой записи, будет вызван метод для обновления этой строки в таблице.
     * Иначе в случае отсутствия - данная новая строка будет добавлена в таблицу.
     *
     * @param int $uid
     * @return bool
     */
    private function checkIssetUid(int $uid): bool
    {
        // проверка (подготовка) параметра выполняться не будет,
        // так как сюда по любому попадет число
        $sqlQuery = 'SELECT COUNT(`uid`) AS `num`
                      FROM `csv`
                        WHERE `uid` = '.$uid;

        $stmt = $this->connection->prepare($sqlQuery);

        if ($success = $stmt->execute()) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            // будет либо 1 (true), либо 0 (false)
            return $result['num'];
        }

        // вернетчя в случае ошибки, может нужно как то и разграничить с пустым результатом
        // но данная ошибка маловероятна при общей положительной работе СУБД
        return false;
    }

    /**
     * Выполняет обновление одной записи в таблице.
     * Обновление записи происходит по уникальному "uid" для данной таблицы
     *
     * @param array $row
     * @return bool
     */
    private function updateRow(array $row): bool
    {

        $sqlQuery = 'UPDATE `csv` 
                        SET `name` = :name, `age` = :age, `email` = :email, `phone` = :phone, `gender` = :gender
                          WHERE `uid` = :uid';

        $stmt = $this->connection->prepare($sqlQuery);

        // тут можно было бы проверять только "name",
        // но проверка элементов масива выполняется в другом классе (CSV)
        // поэтому здесь используются "подготовленные" параметры - на случай вызова из другого класса
        $stmt->bindParam(':uid',   $row['UID']);
        $stmt->bindParam(':name',  $row['Name']);
        $stmt->bindParam(':age',   $row['Age']);
        $stmt->bindParam(':email', $row['Email']);
        $stmt->bindParam(':phone', $row['Phone']);
        $stmt->bindParam(':gender',$row['Gender']);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}