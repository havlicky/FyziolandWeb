<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

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

$query = "  SELECT
                cawl.id,
                cawl.date,
                DATE_FORMAT(cawl.time, '%H:%i') AS time,
                cawl.client,
                al.shortcut as user,
                DATE_FORMAT(cawl.lastEditDate, '%d.%m.') as lastEditDate
            FROM clientAvailabilityWL cawl
            LEFT JOIN adminLogin al ON al.id = cawl.user
            WHERE
                cawl.client = :client AND
                cawl.date BETWEEN :dateFrom AND :dateTo
            ORDER BY cawl.date, cawl.time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"] , PDO::PARAM_STR);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);