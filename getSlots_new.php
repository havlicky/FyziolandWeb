<?php

require_once "php/class.settings.php";
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

$query = "  SELECT DISTINCT	
                TIME_FORMAT(pat.time, '%H:%i') as time,
                pat.date               
            FROM personAvailabilityTimetable pat
            LEFT JOIN relationPersonService rps ON rps.person=pat.person

            WHERE 
                (rps.service = :service) AND	
                (:person1 IS NULL OR pat.person = :person2) AND 
                DATE_ADD(CAST(CONCAT(pat.date, ' ', pat.time) AS datetime), INTERVAL -2 HOUR) > NOW() AND
                pat.date BETWEEN :dateFrom AND :dateTo AND

                (NOT EXISTS (
                     SELECT 
                            r.id
                     FROM reservations AS r
                     WHERE
                        r.personnel = pat.person AND
                        r.active = 1 AND
                        r.date = pat.date AND
                        CAST(CONCAT(r.hour, ':', r.minute) as time) = pat.time
                 ) AND
                 NOT EXISTS (
                        SELECT
                            pbs.id
                        FROM patBanServices pbs
                        WHERE
                            pbs.patId = pat.id AND
                            pbs.serviceId = :service
                    )
                )
                 ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
$stmt->bindValue(":person1", empty($_POST["person"]) ? NULL : $_POST["person"], PDO::PARAM_INT);
$stmt->bindValue(":person2", empty($_POST["person"]) ? NULL : $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);