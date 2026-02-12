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
                patt.id,
                patt.date,
                DATE_FORMAT(patt.time, '%H:%i') AS time,
                patt.person,
                patt.room
            FROM personAvailabilityTimetable patt
            
            LEFT JOIN adminLogin al ON al.id = patt.person
            
            WHERE                
                date = :date AND
                (al.roomAllocation = 1)
            ORDER BY date, time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);