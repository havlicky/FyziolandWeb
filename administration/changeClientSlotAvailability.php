<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

/*
if (intval($resultAdminUser->slotChange) !== 1) {
    die();
}
*/

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
                id
            FROM clientAvailabilityTimetable
            WHERE
                client = :client AND
                dayOfWeek = :dayOfWeek AND
                time = :time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":dayOfWeek", $_POST["dayOfWeek"], PDO::PARAM_INT);
$stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

if ($result === FALSE) {
    $query = "  INSERT INTO clientAvailabilityTimetable (
                    client,
                    dayOfWeek,
                    time
                ) VALUES (
                    :client,
                    :dayOfWeek,
                    :time
                )";
} else {
    $query = "  DELETE FROM clientAvailabilityTimetable 
                WHERE
                    client = :client AND
                    dayOfWeek = :dayOfWeek AND
                    time = :time";
}

$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":dayOfWeek", $_POST["dayOfWeek"], PDO::PARAM_INT);
$stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);

if ($stmt->execute()) {
    $query = "UPDATE clients SET lastSlotsUpdate = NOW() WHERE id=:client";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
    
    echo "1";
} else {
    echo "0";
}