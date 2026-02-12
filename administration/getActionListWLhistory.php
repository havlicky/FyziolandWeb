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

$query = "  SELECT                
                DATE_FORMAT(logWL.actionTimestamp,'%d.%m.%Y %H:%i') as date,
                DATE_FORMAT(logWL.WLdate,'%d.%m.%Y') as WLdate,
                logWL.type,   
                logWL.note,                
                DATE_FORMAT(logWL.WLtime,'%H:%i') as WLtime,
                al.displayName,
                IF(logWL.utilized = '1', 'checked', NULL) as utilized,                
                IF(logWL.rejected = '1', 'checked', NULL) as rejected                                
            FROM logWL 
            LEFT JOIN adminLogin al ON al.id = logWL.therapist
            WHERE
                logWL.client=:client AND
                ((logWL.WLdate=:WLdate AND logWL.WLtime<:WLtime) OR logWL.WLdate<:WLdate)
                
            ORDER BY logWL.WLdate DESC, logWL.WLtime DESC ";
$stmt = $dbh->prepare($query);                                            
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":WLdate", $_POST["date"], PDO::PARAM_STR);
$stmt->bindParam(":WLtime", $_POST["time"], PDO::PARAM_STR);    
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);

