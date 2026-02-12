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

$query = "   
            SELECT
                s.id,
                s.name
            FROM services AS s                                                                    
            WHERE 
            s.id IN 
                (SELECT 
                    service FROM relationPersonService 
                    WHERE 
                        relationPersonService.person = :person
                ) AND
            s.id NOT IN 
                (SELECT
                    pbs.serviceId
                FROM patBanServices pbs                               
                WHERE
                    pbs.patId = (SELECT id FROM personAvailabilityTimetable pat WHERE pat.person = :person2 AND pat.date = :date AND pat.time = CAST(CONCAT(:hour, ':', :minute) as time) )
                )           
            ORDER BY s.order";
$stmt = $dbh->prepare($query);                                            
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":person2", $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_STR);
$stmt->bindParam(":minute", $_POST["minute"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);