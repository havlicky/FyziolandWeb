<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";


if (intval($resultAdminUser->slotChange) !== 1) {
    die();
}


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
            FROM tasks 
           
            WHERE
                personnel = :person AND
                date = :date AND
                DATE_FORMAT(CAST(CONCAT(hour, ':', minute, ':00') AS TIME), '%H:%i') = :time
        ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

if ($result === FALSE) {
    $query = "  INSERT INTO tasks (
                    personnel,
                    date,
                    hour,
                    minute,
                    plan,
                    reality
                ) VALUES (
                    :person,
                    :date,
                    HOUR(:time),
                    MINUTE(:time),
                    :plan,
                    :reality
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":plan", $_POST["plan"], PDO::PARAM_STR);
    $stmt->bindParam(":reality", $_POST["reality"], PDO::PARAM_STR);
    $stmt->execute();
    $patId = $dbh->lastInsertId();

    echo "1";
    
} else {
     $query = "  UPDATE tasks 
                    SET 
                        personnel = :person,
                        date = :date,
                        hour = HOUR(:time),
                        minute = MINUTE(:time),
                        plan = :plan,
                        reality = :reality 
                    WHERE
                        id = :id
                ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
    $stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":plan", $_POST["plan"], PDO::PARAM_STR);
    $stmt->bindParam(":reality", $_POST["reality"], PDO::PARAM_STR);
    $stmt->execute();
    
    echo "1";
}

