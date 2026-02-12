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
                r.service as serviceId,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') as surname,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') as name,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') as phone,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') as email,                                
                r.date as dateformatted,
                CONCAT
                    (
                    DAY(r.date), '. ',
                    MONTH(r.date), '. ',
                    YEAR(r.date)
                    ) as date,                
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS timeFrom,
                CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ', AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')) AS clientName,
                s.name as service,
                a.displayName AS personnel,
                r.note,
                r.entrynote
            FROM reservations r
            LEFT JOIN adminLogin a ON a.id = r.personnel
            LEFT JOIN services s ON s.id = r.service
            WHERE
                date < CURRENT_TIMESTAMP() + INTERVAL 2 DAY AND
                date > CURRENT_TIMESTAMP() - INTERVAL 45 DAY AND
                r.active = 0
            ORDER BY
                dateformatted, personnel, timeFrom";
$stmt = $dbh->prepare($query);    
$stmt->execute();
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
