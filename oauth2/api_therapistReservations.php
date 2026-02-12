<?php

// include our OAuth2 Server object
require_once __DIR__.'/server.php';

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $server->getResponse()->send();
    die;
}

require_once "../php/class.settings.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

if ($_GET["action"] === "getTherapistReservations") {
    $query = "  SELECT
                r.id,
                r.date,
                DATE_FORMAT(CAST(CONCAT(hour, ':', minute, ':00') AS TIME), '%H:%i') AS time,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS surname,
                s.shortcut as service,
                r.service as serviceId,
                r.note
            FROM reservations r
           LEFT JOIN services s ON s.id = r.service
            WHERE
                r.personnel = :person AND
                r.date BETWEEN :dateFrom AND :dateTo AND
                r.active = '1'
            ORDER BY r.date, time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo json_encode($results);
}