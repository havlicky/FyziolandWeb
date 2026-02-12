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
                r.id,
                CONCAT
                    (
                    DAY(r.date), '. ',
                    MONTH(r.date), '. ',
                    YEAR(r.date), ' ',
                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'), ', ',
                    AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'), ', ',
                    a.displayName, ', ',                    
                    s.shortcut
                    ) as rezervace                
                
            FROM reservations r
            LEFT JOIN adminLogin a ON a.id = r.personnel
            LEFT JOIN services s ON s.id = r.service
            WHERE
                r.date BETWEEN :dateFrom AND :dateTo AND
                r.active = 1 AND
                (r.service = 10  OR r.service = 12)
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
