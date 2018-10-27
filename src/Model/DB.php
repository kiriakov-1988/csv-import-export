<?php

namespace App\Model;


class DB
{
    const DB = CONFIG_DB;

    private $connection;

    public function __construct()
    {

        $this->connection = new \PDO(self::DB['driver'].':host='. self::DB['host'] .';dbname='. self::DB['name'],
            self::DB['user'],
            self::DB['pass']);

    }

    public function addCsvData(array $csvData): bool
    {
        // По хорошему при добавлении масива данных можно использовать транзакции,
        // и в случае ошибки возвращать Базу данных к первоначальному состоянию.
        // Но здесь будет реализован базовый функционал с сохранением удавшихся подзапросов.

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

    public function deleteCsvData(): bool
    {
        $sqlQuery = 'TRUNCATE TABLE `csv`';

        $stmt = $this->connection->prepare($sqlQuery);

        if ($success = $stmt->execute()) {

            return true;
        }

        return false;

    }


    // http://thisinterestsme.com/pdo-prepared-multi-inserts/
    // в теории можно превысить длину sql запроса ... но обычно по умлочанию 16 МБ
    private function pdoMultiInsert($data): bool
    {

        //Will contain SQL snippets.
        $rowsSQL = array();

        //Will contain the values that we need to bind.
        $toBind = array();

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