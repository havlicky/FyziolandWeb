<?php

require_once "../php/class.settings.php";
require_once "../php/class.messagebox.php";
require_once "checkLogin.php";

/*
if (intval($resultAdminUser->isSuperAdmin) !== 1) {
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

$query = "UPDATE reservations SET service = :newService WHERE id = :id";

$stmt = $dbh->prepare($query);
$stmt->bindValue(":newService", $_POST["newService"], PDO::PARAM_STR);
$stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
$stmt->execute();

$messageBox->addText("Služba byla úspěšně změněna.");
//$messageBox->setClass("alert-danger");
$_SESSION["messageBox"] = $messageBox;
