<?php

//require_once "../header.php";
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


//smažu původní odpověď na danou otázku
$stmt = $dbh->prepare("DELETE FROM questionnaire WHERE idq = :idq AND idres = :idres");
$stmt->bindParam(":idq", $_POST["idq"], PDO::PARAM_INT);
$stmt->bindParam(":idres", $result->id, PDO::PARAM_INT);
$stmt->execute();

//uložím odpověď na danou otázku
$query = "INSERT INTO questionnaire (idres, idq, ans) VALUES (:idres, :idq, :ans)";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":idres", $result->id, PDO::PARAM_INT);
$stmt->bindParam(":idq", $_POST["idq"], PDO::PARAM_INT);
$stmt->bindParam(":ans", $_POST["ans"], PDO::PARAM_INT);
$stmt->execute();

echo json_encode($result);