<?php
/**
 * Класс для работы с записями пользователей
 *
 * @property $FullName
 * @property $Email
 * @property $Birthday
 * @property $Registered
 * @property $Status
 */
class User extends DBObject {
    const TABLE_NAME = DB_TABLE;

    /*
     * Геттеры/сеттеры для преобразования данных
     * из отображаемого вида в сохраняемый и наоборот.
     */

    protected function getRegistered($value) {
        return date("d.m.Y G:i", $value);
    }

    protected  function setRegistered($value) {
        // strtotime не надежен, т.к. парсит в зависимости от локали
        list($day, $month, $year, $hour, $min) = sscanf($value, "%d.%d.%d %d:%d");
        return mktime($hour, $min, 0, intval($month), intval($day), intval($year));
    }

    protected  function setStatus($value) {
        return $value == "On" ? "On" : "Off";
    }

}

class DBObject {
    const TABLE_NAME = "";

    /**
     * @return string имя таблицы БД
     */
    protected function tableName() {
        if (function_exists("get_called_class")) {
            $table_name = constant(get_called_class() . "::TABLE_NAME");
        } else {
            eval('$table_name = ' . get_class($this). '::TABLE_NAME;');
        }
        return $table_name;
    }

    private static $_fields = null;
    /**
     * @static
     * @return array список полей БД
     */
    public static function GetFields() {
        self::checkFields();
        return self::$_fields;
    }

    private static $_PK = null;
    /**
     * @static
     * @return string первичный ключ таблицы
     */
    public static function GetPK() {
        self::checkFields();
        return self::$_PK;
    }

    /**
     * @var array данные в том виде, в котором они лежат в БД
     */
    public $_data;

    /**
     * @var bool определяет нужно ли делать INSERT или UPDATE
     */
    private $_isNew = true;

    /**
     * получить информацию о структуре таблицы из БД
     */
    private function checkFields() {
        if (!self::$_fields) {
            // mysql_list_fields - deprecated
            $fields = Database::Get()->Query("SHOW COLUMNS FROM `" . $this->tableName() . "`");
            self::$_fields = array();

            foreach ($fields as $f) {
                self::$_fields[] = $f['Field'];
                if ($f['Key'] == "PRI") {
                    self::$_PK = $f['Field'];
                }
            }
        }
    }

    /**
     * Создание или загрузка данных пользователя
     * @param null $pk первичный ключ, если задан, будут загружены данные
     * @throws Exception
     */
    public function __construct($pk = null) {
        if ($this->tableName() == null) {
            throw new Exception("Не определена таблица базы");
        }

        self::checkFields();

        if ($pk != null) {
            $data = Database::Get()->Select(DB_TABLE, "*", array(self::$_PK => $pk));
            if (count($data) != 1) {
                throw new Exception("Не найдена запись " . $pk);
            }
            $this->_data = $data[0];
            $this->_isNew = false;
        }
    }

    /**
     * Сохранить изменения объекта
     */
    public function Save() {
        $data = array();
        foreach(self::$_fields as $field) {
            $data[$field] = $this->_data[$field];
        }
        if ($this->_isNew) {
            Database::Get()->Insert($this->tableName(), $data);
            $this->_isNew = false;
        } else {
            Database::Get()->Update($this->tableName(), $data, array(self::$_PK => $this->_data[self::$_PK]));
        }
    }

    /**
     * Получить все свойства объекта в виде массива
     * @param bool $assoc создавать ассоциативный массив
     * @return array
     */
    public function GetArray($assoc = true) {
        $data = array();
        foreach(self::$_fields as $field) {
            if ($assoc) {
                $data[$field] = $this->$field;
            } else {
                $data[] = $this->$field;
            }
        }
        return $data;
    }


    public function __set($name, $value) {
        // вызвать метод setFieldName(..) если такой существует
        if (method_exists($this, "set" . $name)) {
            $value = call_user_func(array($this, "set" . $name), $value);
        }
        $this->_data[$name] = $value;
    }

    public function __get($name) {
        // вызвать метод getFieldName(..) если такой существует
        $value = $this->_data[$name];
        if (method_exists($this, "get" . $name)) {
            $value = call_user_func(array($this, "get" . $name), $value);
        }
        return $value;
    }


}
