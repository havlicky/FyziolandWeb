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

$query = "  UPDATE clients SET fyzBezLehatka = :fyzBezLehatka, lastEditDate = NOW() WHERE id = :client";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":fyzBezLehatka", $_POST["fyzbezlehatka"], PDO::PARAM_STR);

if ($stmt->execute()) {
    echo "1";
} else {
    echo "0";
}