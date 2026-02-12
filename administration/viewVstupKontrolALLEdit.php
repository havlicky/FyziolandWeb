<?php

require_once "../php/class.settings.php";
require_once "../php/class.messagebox.php";
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

$allowedColumns = array(
    
    "internalNote" => false,
    "recomCheck" => false,    
    "recomSaved" => false    
    
);

if (!in_array($_POST["field"], array_keys($allowedColumns))) {
    die();
} else {
    $crypted = $allowedColumns[$_POST["field"]];
}

$query = "UPDATE reservations SET {$_POST["field"]} = ";
if ($crypted) {
    $query .= "AES_ENCRYPT(:value, '" . Settings::$mySqlAESpassword . "')";
} else {
    $query .= ":value";
}
$query .= " WHERE id = :id";

$stmt = $dbh->prepare($query);
$stmt->bindValue(":value", empty($_POST["value"]) ? NULL : $_POST["value"], PDO::PARAM_STR);
$stmt->bindParam(":id", $_POST["id"], PDO::PARAM_STR);
$stmt->execute();
