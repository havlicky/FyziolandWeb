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
                DATE_FORMAT(CONCAT(r.date, ' ', r.hour,':',r.minute),'%d.%m.%Y %H:%i') as date,
                DATE_FORMAT(r.deleteTimestamp, '%d.%m.%Y %H:%i') as deleteTimestamp,
                al.shortcut,
                (
                    IF(
                        TIMESTAMPDIFF(HOUR, r.deleteTimestamp, DATE_FORMAT(CONCAT(r.date, ' ', r.hour,':',r.minute),'%Y.%m.%d %H:%i'))<49,
                        CONCAT(TIMESTAMPDIFF(HOUR, r.deleteTimestamp, DATE_FORMAT(CONCAT(r.date, ' ', r.hour,':',r.minute),'%Y.%m.%d %H:%i')), ' h') ,
                        CONCAT(TIMESTAMPDIFF(DAY, r.deleteTimestamp, DATE_FORMAT(CONCAT(r.date, ' ', r.hour,':',r.minute),'%Y.%m.%d %H:%i')), ' DEN')
                    )
                ) as predstihZruseni,
                IFNULL(r.deleteReason, 'Nevyplněno') as deleteReason
            FROM reservations r
            LEFT JOIN adminLogin al ON al.id = r.personnel
            WHERE 
                (r.client = :client OR 
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND
                r.active = 0            
            ORDER BY r.deleteTimestamp DESC
            LIMIT 10
                
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
$stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
