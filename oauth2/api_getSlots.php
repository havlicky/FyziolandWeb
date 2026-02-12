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

if ($_GET["action"] === "getSlots") {
    $query = "  SELECT
                id,
                date,
                DATE_FORMAT(time, '%H:%i') AS time
            FROM personAvailabilityTimetable
            WHERE
                person = :person AND
                date BETWEEN :dateFrom AND :dateTo
            ORDER BY date, time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo json_encode($results);
}