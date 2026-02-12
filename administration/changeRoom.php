<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";


//if (intval($resultAdminUser->slotChange) !== 1) {
//    die();
//}


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
            FROM personAvailabilityTimetable
            WHERE
                person = :person AND
                date = :date AND
                time = :time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

if ($result === FALSE) {
    
} else {
    $query = "  UPDATE personAvailabilityTimetable SET room = :room
                WHERE
                    person = :person AND
                    date = :date AND
                    time = :time";
}

$stmt = $dbh->prepare($query);
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":room", $_POST["room"], PDO::PARAM_STR);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);

if ($stmt->execute()) {
    echo "1";
} else {
    echo "0";
}