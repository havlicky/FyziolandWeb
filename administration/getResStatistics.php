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

$query = "SELECT value as pocatekRezervaci FROM settings WHERE name = 'PocRezProUpdateSlotuClient'";
$stmt = $dbh->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

$query = "  SELECT
                c.email,
                c.phone                                
            FROM clients c            
            WHERE
                c.id = :client
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_OBJ);

$query = "  SELECT                
                COUNT(r.id) as pocet,
                WEEKDAY(r.date) as dayOfWeek,
                r.hour,                
                r.minute                                
            FROM reservations r                        
            WHERE
                (r.client = :client OR 
                 r.email = :email OR
                 r.phone = :phone) AND
                r.active = '1' AND
                r.date>:pocatekRezervaci
            GROUP BY WEEKDAY(r.date), r.hour
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":phone", $client->phone, PDO::PARAM_STR);
$stmt->bindParam(":email", $client->email, PDO::PARAM_STR);
$stmt->bindParam(":pocatekRezervaci", $result->pocatekRezervaci, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);