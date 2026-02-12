<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

session_start();

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

if (isset($_POST["client"])) {    
    
    $query = "
            SELECT 
                COUNT(logWL.id) as pocetSMSveFronte,
                IFNULL(GROUP_CONCAT(CONCAT(DATE_FORMAT(logWL.WLdate, '%d. %m. %Y'), ' ', DATE_FORMAT(logWL.WLtime, '%H:%i'), ' ', al.shortcut) SEPARATOR ' | '), '') as WLdate
            FROM logWL 
            LEFT JOIN adminLogin al ON al.id = logWL.therapist
            WHERE 
                logWL.client = :client AND 
                logWL.WLdate>=CURDATE() AND 
                logWL.actionTimestamp IS NULL";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":client", empty($_POST["client"]) ? NULL : $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);        
        
    if ($stmt->execute()) {
        echo json_encode($result);
    }
}