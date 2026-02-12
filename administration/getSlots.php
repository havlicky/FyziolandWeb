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

if (isset($_POST["service"])) {
    
    $query = "  SELECT
                    pat.id,
                    pat.date,
                    DATE_FORMAT(pat.time, '%H:%i') AS time,
                    pat.person
                FROM personAvailabilityTimetable pat
                WHERE
                    person = :person AND
                    date BETWEEN :dateFrom AND :dateTo AND
                    NOT EXISTS (
                        SELECT
                            pbs.id
                        FROM patBanServices pbs
                        WHERE
                            pbs.patId = pat.id AND
                            pbs.serviceId = :service
                    )
                ORDER BY date, time";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":person", $_POST["person"] , PDO::PARAM_INT);   
    $stmt->bindParam(":service", $_POST["service"] , PDO::PARAM_INT);   
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
} else {    
    $query = "  SELECT
                    id,
                    date,
                    DATE_FORMAT(time, '%H:%i') AS time,
                    person
                FROM personAvailabilityTimetable
                WHERE
                    (:person1 IS NULL OR person = :person2) AND
                    date BETWEEN :dateFrom AND :dateTo
                ORDER BY date, time";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":person1", $_POST["person"] , PDO::PARAM_INT);
    $stmt->bindParam(":person2", $_POST["person"] , PDO::PARAM_INT);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
}

echo json_encode($results);