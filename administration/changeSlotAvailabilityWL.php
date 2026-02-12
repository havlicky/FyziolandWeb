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
            FROM clientAvailabilityWL
            WHERE
                client = :client AND
                date = :date AND
                time = :time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

if ($result === FALSE) {
    $query = "  INSERT INTO clientAvailabilityWL (
                    client,
                    date,
                    time,
                    user,
                    lastEditDate
                ) VALUES (
                    :client,
                    :date,
                    :time,
                    :user,
                    NOW()
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
} else {
    $query = "  DELETE FROM clientAvailabilityWL 
                WHERE
                    client = :client AND
                    date = :date AND
                    time = :time";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
}


if ($stmt->execute()) {
    echo "1";
} else {
    echo "0";
}