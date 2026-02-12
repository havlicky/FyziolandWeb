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

if ($_POST["type"] == "newRes") {
    $query = " 
        UPDATE reservations 
        SET
            automatActionByCreation = :action,
            automatTimestampByCreation = NOW()        
        WHERE
            id = :id 
        ";
    $stmt = $dbh->prepare($query);      
    $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
    $stmt->bindParam(":action", $_POST["action"], PDO::PARAM_STR);    
    $stmt->execute();
}

if ($_POST["type"] == "deletion") {
    $query = " 
        UPDATE reservations 
        SET
            automatActionByDeletion = :action,
            automatTimestampByDeletion = NOW()        
        WHERE
            id = :id 
        ";
    $stmt = $dbh->prepare($query);      
    $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
    $stmt->bindParam(":action", $_POST["action"], PDO::PARAM_STR);    
    $stmt->execute();
}



echo " ..zapsáno do tabulky rezervací..";