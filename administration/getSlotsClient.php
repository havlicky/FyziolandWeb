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
                id,                
                dayOfWeek,
                DATE_FORMAT(time, '%H:%i') AS time,
                client
            FROM clientAvailabilityTimetable
            WHERE
                client = :client ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"] , PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);