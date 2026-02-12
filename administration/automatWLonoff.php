<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";


//if (intval($resultAdminUser->slotChange) !== 1) {
//    die();
//}

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

$query = " UPDATE settings SET value = :value WHERE name = 'automatWL' ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":value", $_POST["value"], PDO::PARAM_INT);
$stmt->execute();
