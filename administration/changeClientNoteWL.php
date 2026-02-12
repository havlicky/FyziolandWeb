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

$today = new DateTime();
$today = $today->format("d.m.y");

if($_POST["note"]!=="") {
    $prvniZnak = substr($_POST["note"],0,1);    
    if ($prvniZnak == '0' || $prvniZnak == '1' || $prvniZnak == '2' || $prvniZnak == '3' || $prvniZnak == '4' || $prvniZnak == '5' || $prvniZnak == '6' || $prvniZnak == '7' || $prvniZnak == '8' || $prvniZnak == '9') {
        $note = $_POST["note"];
    } else {
        $note = $today . ' ' . $resultAdminUser->shortcut .': '.  $_POST["note"];
    }
        
} else {
    $note = '';                      
}

$query = "UPDATE clients SET noteWL = IF(:note='', NULL, :note) WHERE id = :client";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":note", $note, PDO::PARAM_STR);
$stmt->execute();
