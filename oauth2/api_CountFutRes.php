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
                IFNULL(COUNT(r.id),0) as pocet
            FROM reservations r
                                
                WHERE
                    (r.client = :client OR 
                     AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR
                     AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone    
                    ) AND
                    CAST(CONCAT(r.date, ' ', r.hour, ':', r.minute) as datetime) > CURRENT_TIMESTAMP() AND                    
                    r.active = 1
                ";
$stmt = $dbh->prepare($query);    
$stmt->execute();
$results = $stmt->fetch(PDO::FETCH_OBJ);

echo json_encode($results);
