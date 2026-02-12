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
                CONCAT('<b>', COUNT(r.id), '</b> / ', (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1)) as celkem,
                CONCAT('<b>',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND res.date BETWEEN CURDATE()+INTERVAL +1 DAY AND CURDATE() + INTERVAL 14 DAY),'</b> / ',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1 AND res.date BETWEEN CURDATE()+INTERVAL +1 DAY AND CURDATE() + INTERVAL 14 DAY)) as bud,
                CONCAT('<b>',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND res.date BETWEEN CURDATE() + INTERVAL -7 DAY AND CURDATE()), '</b> / ', (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1 AND res.date BETWEEN CURDATE() + INTERVAL -7 DAY AND CURDATE())) as 1W,
                CONCAT('<b>',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND res.date BETWEEN CURDATE() + INTERVAL -14 DAY AND CURDATE()+ INTERVAL -8 DAY), '</b> / ', (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1 AND res.date BETWEEN CURDATE() + INTERVAL -14 DAY AND CURDATE()+ INTERVAL -8 DAY)) as 2W,
                CONCAT('<b>',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND res.date BETWEEN CURDATE() + INTERVAL -30 DAY AND CURDATE()+ INTERVAL -15 DAY), '</b> / ', (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1 AND res.date BETWEEN CURDATE() + INTERVAL -30 DAY AND CURDATE()+ INTERVAL -15 DAY)) as 1M,
                CONCAT('<b>',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND res.date BETWEEN CURDATE() + INTERVAL -60 DAY AND CURDATE()+ INTERVAL -31 DAY), '</b> / ', (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1 AND res.date BETWEEN CURDATE() + INTERVAL -60 DAY AND CURDATE()+ INTERVAL -31 DAY)) as 2M,
                CONCAT('<b>',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND res.date BETWEEN CURDATE() + INTERVAL -90 DAY AND CURDATE()+ INTERVAL -61 DAY), '</b> / ', (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1 AND res.date BETWEEN CURDATE() + INTERVAL -90 DAY AND CURDATE()+ INTERVAL -61 DAY)) as 3M,
                CONCAT('<b>',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND res.date BETWEEN CURDATE() + INTERVAL -180 DAY AND CURDATE()+ INTERVAL -91 DAY), '</b> / ', (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1 AND res.date BETWEEN CURDATE() + INTERVAL -180 DAY AND CURDATE()+ INTERVAL -91 DAY)) as 6M,
                CONCAT('<b>',(SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 0 AND res.date BETWEEN CURDATE() + INTERVAL -360 DAY AND CURDATE()+ INTERVAL -181 DAY), '</b> / ', (SELECT COUNT(res.id) FROM reservations res WHERE (res.client = :client OR AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(res.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND res.active = 1 AND res.date BETWEEN CURDATE() + INTERVAL -360 DAY AND CURDATE()+ INTERVAL -181 DAY)) as 12M
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
