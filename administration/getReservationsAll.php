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

$query = "  SELECT
                r.id,
                r.date,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS time,
                CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ', AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')) AS client,
                r.personnel,
                s.shortcut
                
            FROM reservations r
            
            LEFT JOIN adminLogin al ON al.id = r.personnel
            LEFT JOIN services s ON s.id = r.service

            WHERE                
                al.active = 1 AND
                r.date = :date AND
                r. active = '1'
            ORDER BY r.date, time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);