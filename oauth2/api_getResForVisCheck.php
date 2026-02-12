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

$query = "  SELECT                    
                r.id,
                r.client,                
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') as surname,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') as name,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') as phone,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') as email,                                
                r.date as dateformatted,                             
                DATE_FORMAT(CAST(CONCAT(hour, ':', minute, ':00') AS TIME), '%H:%i') AS timeFrom,
                r.hour,                
                s.name as service,
                a.displayName AS personnel,
                a.id as user,
                r.code,
                CONCAT(
                    DAY(r.date), '. ',
                    MONTH(r.date), '. ',
                    YEAR(r.date), '; ',
                     DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i'), '; ',
                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'), ', ',
                    AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'), '; ',    
                    s.name, '; ',
                    a.shortcut
                ) as matchtable                                
                
            FROM reservations r
            LEFT JOIN adminLogin a ON a.id = r.personnel
            LEFT JOIN services s ON s.id = r.service
            WHERE
                r.date BETWEEN :dateFrom AND :dateTo AND                
                r.active = 1 AND
                a.visitsCheck = 1 AND
                r.code = 0 AND
                (r.client IS NULL OR r.client<>'b4632a7b-c0a8-11e7-afef-f82819489752' AND r.client<>'0f7ce09c-4b8e-11ed-90e9-f48e3873b598')
                
                
            ORDER BY
                dateformatted, HOUR(timeFrom), personnel";

// klienti, kteří se nemají zobrazovat v reportu jsem já, Katka a Monika K.

$stmt = $dbh->prepare($query);    
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
