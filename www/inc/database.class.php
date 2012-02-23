<?php

class DBException extends Exception {
    public function DBException($db_link, Exception $prev = null) {
        $message = "[MySQL] " . mysql_error($db_link);
        $code = mysql_errno($db_link);
        parent::__construct($message, $code, $prev);
    }
}

class Database {
    private $_link;
    private static $_instance;

    /**
     * Реализация паттерна "синглтон"
     * @static
     * @return Database
     */
    public static function Get() {
        if (!self::$_instance) {
            self::$_instance = new self(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        }
        return self::$_instance;
    }

    /**
     * Создать подключение к базе данных
     * @param string $host адрес сервера MySql
     * @param string $user имя пользователя
     * @param string $pass пароль пользователя
     * @param string $db_name название базы данных
     * @param bool $permanent использовать постоянное соединение
     * @return Database
     */
    public function Database($host, $user, $pass, $db_name, $permanent = false) {
        if ($permanent) {
            $this->_link = @mysql_pconnect($host, $user, $pass);
        } else {
            $this->_link = @mysql_connect($host, $user, $pass);
        }
        if (!$this->_link) {
            throw new Exception("Доступ закрыт для '$user'@'$host'");
        }
        if (!mysql_select_db($db_name)) {
            throw new DBException($this->_link);
        }
    }

    /**
     * @param string $table таблица БД
     * @param string $columns список полей через запятую
     * @param null $where список условий сравнения
     * @param string $whereCond оператор "AND" или "OR"
     * @param string $cond дополнительные условия (ORDER, LIMIT)
     * @return array
     */
    public function Select($table, $columns, $where = null, $whereCond = "AND", $cond = null) {
        $columns = explode(",", $columns);
        foreach ($columns as $col) {
            if ($col != "*")
                $col = "`" . trim($col) . "`";
            $sql_columns[] = $col;
        }
        $sql_columns = implode(", ", $sql_columns);
        $sql_table = "`" . $table . "`";

        $sql = "SELECT " . $sql_columns . " FROM " . $sql_table;

        if (is_array($where)) {
            $sql_where = array();
            foreach($where as $key => $val) {
                $sql_where[] = "`" . $key . "` = \"" . mysql_real_escape_string($val) . "\"";
            }
            $sql_where = implode(" " . $whereCond . " ", $sql_where);
            $sql .= " WHERE " . $sql_where;
        }

        if ($cond) {
            $sql .= " " . $cond;
        }

        return $this->Query($sql);
    }

    /**
     * @param string $table таблица БД
     * @param array $values ассоциативный массив значений (имя поля => данные)
     * @return boolean
     */
    public function Insert($table, $values) {
        foreach($values as $val) {
            $sql_values[] = "'" . mysql_real_escape_string($val) . "'";
        }
        $sql_values = implode(", ", $sql_values);
        $sql_table = "`" . $table . "`";
        $sql = "INSERT INTO $sql_table VALUES ($sql_values)";

        return $this->Query($sql);
    }

    /**
     * @param string $table таблица БД
     * @param array $values ассоциативный массив значений (имя поля => данные)
     * @param null $where список условий сравнения
     * @param string $whereCond оператор "AND" или "OR"
     * @return boolean
     */
    public function Update($table, $values, $where = null, $whereCond = "AND") {
        foreach ($values as $field => $value) {
            $sql_values[] = "`" . $field . "`='" . mysql_real_escape_string($value) . "'";
        }
        $sql_values = implode(", ", $sql_values);
        $sql_table = "`" . $table . "`";

        $sql = "UPDATE " . $sql_table . " SET " . $sql_values;

        if (is_array($where)) {
            $sql_where = array();
            foreach($where as $key => $val) {
                $sql_where[] = "`" . $key . "` = \"" . mysql_real_escape_string($val) . "\"";
            }
            $sql_where = implode(" " . $whereCond . " ", $sql_where);
            $sql .= " WHERE " . $sql_where;
        }

        return $this->Query($sql);
    }

    /**
     * @param string $sql SQL-запрос
     * @return array|boolean
     * @throws DBException
     */
    public function Query($sql) {
        if (DEBUG) {
            file_put_contents(FILE_SQLLOG, $sql . "\r\n\r\n", FILE_APPEND);
        }

        $res = mysql_query($sql);
        if(!$res) {
            throw new DBException($this->_link);
        }
        $data = array();

        if (!is_resource($res)) {
            return true;
        }
        while ($row = mysql_fetch_assoc($res)) {
            $data[] = $row;
        }

        mysql_free_result($res);

        return $data;
    }


}