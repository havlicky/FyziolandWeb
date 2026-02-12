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
                'Počet' as pocet,
                COUNT(r.id) as celkem,
                (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))<=12) as 12H,                
                (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))>12 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))<=24) as 24H,
                (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))>24 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))<=48) as 48H,
                (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))>48 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))<=72) as 3D,
                (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))>72 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))<=168) as 7D,
                (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))>168 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))<=336) as 14D,
                (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND TIMESTAMPDIFF(HOUR, res.deleteTimestamp, DATE_FORMAT(CONCAT(res.date, ' ', res.hour,':',res.minute),'%Y.%m.%d %H:%i'))>336) as vetsi1M
            FROM reservations r
            WHERE 
                (r.client = :client OR 
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND
                r.active = 0
                
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
$stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
