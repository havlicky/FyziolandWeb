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

$query = "UPDATE waitinglist SET solved= :solved WHERE id = :id";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":solved", $_POST["solved"], PDO::PARAM_INT);
$stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
$stmt->execute();