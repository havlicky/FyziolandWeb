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

    $query = "
                SELECT                     
                    SUM(code)*100 as unrealized
                FROM reservations 
                
                WHERE date BETWEEN :dateFrom AND :dateTo 
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    echo json_encode($result);
