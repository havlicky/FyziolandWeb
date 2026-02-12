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
                s.shortcut,
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service =  logWL.service AND wl.utilized = 1), ' / ', (SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND actionTimestamp IS NOT NULL)) as celkem,                
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.utilized = 1 AND wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp > CURDATE()), ' / ',(SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp > CURDATE() )) as budoucí,
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.utilized = 1 AND wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -7 DAY AND CURDATE()), ' / ',(SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -7 DAY AND CURDATE())) as 1W,
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.utilized = 1 AND wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -14 DAY AND CURDATE()+ INTERVAL -8 DAY), ' / ',(SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -14 DAY AND CURDATE()+ INTERVAL -8 DAY)) as 2W,
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.utilized = 1 AND wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -30 DAY AND CURDATE()+ INTERVAL -15 DAY), ' / ',(SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -30 DAY AND CURDATE()+ INTERVAL -15 DAY)) as 1M,
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.utilized = 1 AND wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -60 DAY AND CURDATE()+ INTERVAL -31 DAY), ' / ',(SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -60 DAY AND CURDATE()+ INTERVAL -31 DAY)) as 2M,
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.utilized = 1 AND wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -90 DAY AND CURDATE()+ INTERVAL -61 DAY), ' / ',(SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -90 DAY AND CURDATE()+ INTERVAL -61 DAY)) as 3M,
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.utilized = 1 AND wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -180 DAY AND CURDATE()+ INTERVAL -91 DAY), ' / ',(SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -180 DAY AND CURDATE()+ INTERVAL -91 DAY)) as 6M,
                CONCAT((SELECT COUNT(wl.id) FROM logWL wl WHERE wl.utilized = 1 AND wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -360 DAY AND CURDATE()+ INTERVAL -181 DAY), ' / ',(SELECT COUNT(wl.id) FROM logWL wl WHERE wl.client = :client AND wl.service = logWL.service AND wl.actionTimestamp BETWEEN CURDATE() + INTERVAL -360 DAY AND CURDATE()+ INTERVAL -181 DAY)) as 12M
            FROM logWL
            LEFT JOIN services s ON s.id = logWL.service
            WHERE 
                logWL.client = :client
            GROUP BY logWL.service
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
