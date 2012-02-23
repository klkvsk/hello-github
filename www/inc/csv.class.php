<?php
/**
 * Класс для работы с CSV данными
 */
class CSV {
    public $header = array();
    public $data = array();

    public $withHeader = true;
    public $delimiter = ";";

    /**
     * Получить массив из CSV-строки
     * @static
     * @param string $string строка
     * @param string $delimiter разделитель
     * @return array
     */
    public static function ParseString($string, $delimiter) {
        if (function_exists("str_getcsv")) {
            // только PHP >5.3.0
            $row = str_getcsv($string, $delimiter);
        } else {
            $row = explode($delimiter, $string);
        }
        return array_map("trim", $row);
    }

    /**
     * Распарсить CSV-файл.
     * @param string $filename имя файла
     * @throws Exception
     */
    public function FromFile($filename) {
        if (!file_exists($filename)) {
            throw new Exception("CSV файл не существует");
        }

        $csvData = file_get_contents($filename);
        $this->FromString($csvData);
    }

    /**
     * Распарсить CSV-данные
     * @param string $data строка с данными (можно многострочный текст)
     */
    public function FromString($data) {
        $strings = preg_split("/\r\n|\n\r|\n/", $data);
        if ($this->withHeader) {
            $row = self::ParseString(array_shift($strings), $this->delimiter);
            $this->header = array_map("trim", $row);
        }
        while ($string = array_shift($strings)) {
            $row = self::ParseString($string, $this->delimiter);
            $this->data[] = array_map("trim", $row);
        }
    }

    /**
     * Получить CSV-данные в виде строки.
     * @return string
     */
    public function ToString() {
        $csv = "";
        if ($this->withHeader && !empty($this->header)) {
            $csv .= implode("; ", $this->header) . PHP_EOL;
        }

        foreach($this->data as $data) {
            $csv .= implode("; ", $data) . PHP_EOL;
        }

        return $csv;
    }
}
