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
        DATE_FORMAT(pat.date,'%d.%m.%Y') as date,
        DATE_FORMAT(pat.time,'%H:%i') as time,        
        pat.date as dateFormatted,        
        al.displayName AS therapist,
        al.id as therapistId,
        '' akce
    FROM
        personAvailabilityTimetable pat
    LEFT JOIN adminLogin al ON al.id = pat.person
    WHERE
        pat.date >= CURRENT_TIMESTAMP() AND ! EXISTS(
        SELECT
            r.id
        FROM
            reservations r
        WHERE
            r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND CAST(
                CONCAT(r.hour, ':', r.minute) AS TIME
            ) = pat.time
        LIMIT 1) AND EXISTS(
        SELECT
            wl.id            
        FROM clientAvailabilityWL wl
        WHERE
            wl.date = pat.date AND
            wl.time = pat.time AND
            wl.client = :client AND
            pat.person IN (SELECT therapist FROM clienttherapists ct WHERE ct.client = :client) 
         LIMIT 1
        )
        
    ORDER BY dateFormatted, time
";

$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();
$results= $stmt->fetchAll(PDO::FETCH_OBJ);                                

echo json_encode($results);

