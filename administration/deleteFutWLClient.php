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
    
    $query = " SELECT COUNT(id) as pocetSmazanych FROM logWL WHERE client = :client AND WLdate>=CURDATE() AND actionTimestamp IS NULL";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":client", empty($_POST["client"]) ? NULL : $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    $query = "DELETE FROM logWL WHERE client = :client AND WLdate>=CURDATE() AND actionTimestamp IS NULL";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":client", empty($_POST["client"]) ? NULL : $_POST["client"], PDO::PARAM_STR);
        
    if ($stmt->execute()) {
        echo json_encode($result);
    }
}