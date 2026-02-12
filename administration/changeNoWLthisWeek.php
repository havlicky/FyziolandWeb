<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

/*
if (intval($resultAdminUser->slotChange) !== 1) {
    die();
}
*/

session_start();

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$query = "DELETE FROM weeknotes WHERE client = :client AND firstDayOfWeek=:lastmonday";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":lastmonday", $_POST["lastmonday"], PDO::PARAM_STR);
$stmt->execute();

if ($_POST["note"] != null || $_POST["noWL"] == 1 ) {

    $query = "INSERT INTO weeknotes (client, firstDayofWeek, note, noWL) VALUES (:client, :lastmonday, :note, :noWL)";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":lastmonday", $_POST["lastmonday"], PDO::PARAM_STR);
    $stmt->bindParam(":note", $_POST["note"], PDO::PARAM_STR);
    $stmt->bindParam(":noWL", $_POST["noWL"], PDO::PARAM_INT);
    $stmt->execute();
}
