<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";
require_once "../php/class.settings.php";

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
        al.shortcut,
        al.id as person,
        (SELECT COUNT(pat.id) FROM fyziolandc.personAvailabilityTimetable pat
            WHERE
                pat.person = al.id AND pat.date BETWEEN :dateFrom AND :dateTo AND 
                    ! EXISTS(SELECT
                                r.id
                                FROM fyziolandc.reservations r
                                WHERE
                                r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
                                LIMIT 1
                            )
        ) as W1,
        (SELECT COUNT(pat.id) FROM fyziolandc.personAvailabilityTimetable pat
            WHERE
                pat.person = al.id AND pat.date BETWEEN DATE_ADD(:dateFrom , INTERVAL 7 DAY) AND DATE_ADD(:dateTo , INTERVAL 7 DAY) AND 
                    ! EXISTS(SELECT
                                r.id
                                FROM fyziolandc.reservations r
                                WHERE
                                r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
                                LIMIT 1
                            ) 
        ) as W2,
        (SELECT COUNT(pat.id) FROM fyziolandc.personAvailabilityTimetable pat
            WHERE
                pat.person = al.id AND pat.date BETWEEN DATE_ADD(:dateFrom , INTERVAL 14 DAY) AND DATE_ADD(:dateTo , INTERVAL 14 DAY) AND 
                    ! EXISTS(SELECT
                                r.id
                                FROM fyziolandc.reservations r
                                WHERE
                                r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
                                LIMIT 1
                            ) 
        ) as W3,
        (SELECT COUNT(pat.id) FROM fyziolandc.personAvailabilityTimetable pat
            WHERE
                pat.person = al.id AND pat.date BETWEEN DATE_ADD(:dateFrom , INTERVAL 21 DAY) AND DATE_ADD(:dateTo , INTERVAL 21 DAY) AND 
                    ! EXISTS(SELECT
                                r.id
                                FROM fyziolandc.reservations r
                                WHERE
                                r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
                                LIMIT 1
                            ) 
        ) as W4
        
    FROM adminLogin al
    WHERE
        al.active = 1 AND
        al.indiv = 1 AND
        al.isErgo = 1
        
    ";

$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);