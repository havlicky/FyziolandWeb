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
                r.client,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') as email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') as phone,
                CONCAT(DAY(r.date),'.',MONTH(r.date),'.',YEAR(r.date)) as date,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as time,
                r.date as dateFormatted,
                a.shortcut as therapist                                                                                     
            FROM reservations r
            LEFT JOIN adminLogin a ON a.id = r.personnel               
            LEFT JOIN services s ON s.id = r.service
            WHERE
                (s.isFyzio = 1) AND
                CAST(CONCAT(r.date, ' ', r.hour, ':', r.minute) as datetime) >= :dateFrom AND
                r.active = 1           
            ORDER BY
                r.date, r.hour, r.minute";
    $stmt = $dbh->prepare($query);      
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);




 