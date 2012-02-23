<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Exception</title>
</head>
<body>

<? /* @var $e Exception */ ?>
<h2>
    Ошибка <?= $e->getCode() > 0 ? " #" . $e->getCode() : "" ?>: <?= $e->getMessage() ?>
</h2>

<?
if (DEBUG) {
    $trace = $e->getTraceAsString();
    echo nl2br($trace, true);
}
?>

</body>
</html>
