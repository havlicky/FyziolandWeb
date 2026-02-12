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

if ($_POST["type"]=="ALL") {
    
    $query = "  SELECT
                    logWL.id,
                    DATE_FORMAT(logWL.actionTimestamp,'%d.%m.%Y %H:%i') as date,
                    logWL.actionTimestamp as dateFormatted,
                    CONCAT(DATE_FORMAT(logWL.WLdate,'%d.%m.%Y'),'<br>',DATE_FORMAT(logWL.WLtime,'%H:%i')) as WLdate,
                    logWL.type,  
                    al.displayName,
                    logWL.note,
                    IF(logWL.utilized = '1', 'checked', NULL) as utilized,                
                    IF(logWL.rejected = '1', 'checked', NULL) as rejected,                
                    '' as akce
                FROM logWL 
                LEFT JOIN adminLogin al ON al.id = logWL.therapist
                WHERE
                    logWL.client=:client 
                    
                UNION
                
                SELECT
                    id,
                    DATE_FORMAT(firstDayOfWeek,'%d.%m.%Y') as date,
                    firstDayOfWeek as dateFormatted,
                    '' as WLdate,
                    '' as type,
                    '' as displayName,                    
                    note,
                    NULL as utilized,
                    NULL as rejected,   
                    '' as akce
                FROM weeknotes
                WHERE client = :client
                
                ORDER BY 3 DESC ";
    $stmt = $dbh->prepare($query);                                            
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
} else {
    $query = "  SELECT
                    logWL.id,
                    DATE_FORMAT(logWL.actionTimestamp,'%d.%m.%Y %H:%i') as date,
                    logWL.type,  
                    al.displayName,
                    logWL.note,
                    IF(logWL.utilized = '1', 'checked', NULL) as utilized,                
                    IF(logWL.rejected = '1', 'checked', NULL) as rejected,                
                    '' as akce
                FROM logWL 
                LEFT JOIN adminLogin al ON al.id = logWL.therapist
                WHERE
                    logWL.client=:client AND
                    logWL.WLdate=:WLdate AND
                    logWL.WLtime=:WLtime

                ORDER BY logWL.WLdate, logWL.WLtime DESC ";
    $stmt = $dbh->prepare($query);                                            
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":WLdate", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":WLtime", $_POST["time"], PDO::PARAM_STR);    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
}

echo json_encode($results);

