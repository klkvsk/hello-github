<?php
/**
 * @author Михаил Кулаковский <m@klkvsk.ru>
 */

define("DEBUG", true);

define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "test");
define("DB_TABLE", "users");

define("FILE_DATA", "data.csv");
define("FILE_SCHEMA", "schema.sql");
defile("FILE_SQLLOG", "sql.log");

// обработчик исключений
set_exception_handler("showException");

require_once("inc/csv.class.php");        // класс парсинга CSV файлов
require_once("inc/database.class.php");   // класс работы с БД MySQL
require_once("inc/user.class.php");       // класс для работы с таблицей пользователей

/*
 * 1. Создать MySQL таблицу под данную структуру файла.
 */
$db = Database::Get();
$db->Query("DROP TABLE IF EXISTS `" . DB_TABLE . "`");
$sql_create = sprintf( file_get_contents(FILE_SCHEMA) , DB_TABLE );
$db->Query($sql_create);

/*
 * 2. Все данные из CSV-файла экспортировать в созданную таблицу.
 */
$csv = new CSV();
$csv->FromFile(FILE_DATA);

foreach($csv->data as $row) {
    $user = new User();
    $user->FullName     = $row[0];
    $user->Email        = $row[1];
    $user->Birthday     = $row[2];
    $user->Registered   = $row[3];
    $user->Status       = $row[4];
    $user->Save();
}

/*
 * 3. Изменить для одной случайной записи в таблице статус на противоположный
 */
$random = $db->Select(DB_TABLE, User::GetPK(), null, null, "ORDER BY RAND() LIMIT 1");
if(empty($random)) {
    throw new Exception("Таблица пуста");
}
$randomPK = $random[0][User::GetPK()];

$user = new User($randomPK);
$user->Status = ($user->Status == "On" ? "Off" : "On");
$user->Save();

/*
 * 4. Вывести эту запись на экран. При этом данные этой записи должны
 * выводиться в том же виде, в каком они были получены из CSV-файла.
 */
$csv = new CSV();
$csv->data = array( $user->GetArray() );
showResult($csv->ToString());

function showResult($data) {
    include("inc/result.page.php");
}
function showException(Exception $e) {
    include("inc/exception.page.php");
}
?>