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
                    GROUP_CONCAT(CONCAT(DAY(r.date),'.',MONTH(r.date),'.',YEAR(r.date), ' (', a.shortcut,')') SEPARATOR ', ') as date                                                              
                FROM reservations r
                LEFT JOIN adminLogin a ON a.id = r.personnel               
                WHERE
                    (r.client = :clientId OR 
                     AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR
                     AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone    
                    ) AND
                    CAST(CONCAT(r.date, ' ', r.hour, ':', r.minute) as datetime) >= :dateFrom AND                    
                    r.active = 1
                ORDER BY
                    r.date";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":clientId", $_POST["clientId"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);    
    $stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);




 