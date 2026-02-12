<?php
require_once "../php/class.settings.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

//najdu id rezervace dle hash
$stmt = $dbh->prepare("SELECT id FROM reservations WHERE deleteHash = :hash");
$stmt->bindParam(":hash", htmlentities($_POST["hash"]), PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);	 

//zjistím již všechny uložené odpovědi na otázky
$query = "SELECT idq, ans FROM questionnaire where idres = :idres";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":idres", $result->id, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);	 

echo json_encode($results);