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

$query = " SELECT                                        
                COUNT(id) as WL
            FROM clientAvailabilityWL 
                         
            WHERE
                client = :clientId AND
                CAST(date as datetime) >= :dateFrom                 
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":clientId", $_POST["clientId"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);




 