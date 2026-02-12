<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

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

$query = "UPDATE groupExcercises SET canceled = :canceled WHERE id = :id";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":canceled", $_POST["canceled"], PDO::PARAM_INT);
$stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);