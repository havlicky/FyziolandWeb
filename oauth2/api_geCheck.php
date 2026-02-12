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

// kontrola vyplnění skupinových cvičení pro alert (pokud je vše OK, vrátí selecet 0; jinak vrátí číslo v podobě počtu cvičení, které nejsou vyplněna)
$query = "  SELECT
                SUM(IF(ge.cash>0 OR ge.QR>0 OR ge.Benefit>0 OR ge.free>0 OR (SELECT COUNT(id) FROM visits v WHERE v.ge = ge.id) >0 OR (SELECT COUNT(id) FROM attendance a WHERE a.ge = ge.id)>0 OR ge.canceled = 1 OR ge.recorded = 1, 0, 1)) as kontrola                
            FROM groupExcercises ge                        
            WHERE                
                ge.date BETWEEN :dateFrom AND :dateTo
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetch(PDO::FETCH_OBJ);

echo json_encode($results);