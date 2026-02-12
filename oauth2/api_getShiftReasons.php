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
                r.shiftReason,
                count(r.id) as pocet
            FROM reservations r
            WHERE r.date BETWEEN :dateFrom AND :dateTo AND r.active = 1 AND shiftReason IS NOT NULL AND AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
            GROUP BY r.shiftReason
        
            UNION
            
            SELECT                                                                            
                'CELKEM',
                count(r.id) as pocet
            FROM reservations r
            WHERE r.date BETWEEN :dateFrom AND :dateTo AND r.active = 1 AND shiftReason IS NOT NULL AND AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'          

        ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);
