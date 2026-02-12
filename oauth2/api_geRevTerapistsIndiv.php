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
                'Nerealizováno' as person,                
                (SELECT COUNT(code) from reservations WHERE code > 0 AND date BETWEEN :dateFrom AND :dateTo  ) as pocetNavstev,
                CAST(SUM(code)*100 / (SELECT COUNT(code) from reservations WHERE code > 0 AND date BETWEEN :dateFrom AND :dateTo) AS DECIMAL) as prumCena,
                SUM(code)*100 as revenues,
                NULL as persNaklady,
                NULL as otherCosts,
                SUM(code)*100 as marze
            FROM reservations 
                
            WHERE date BETWEEN :dateFrom AND :dateTo                 
            
            UNION
			
                SELECT
                        'POTENCIÁL' as person,
                        COUNT(v.id) as pocetNavstev,
                        NULL as prumCena,
                        NULL as revenues,
                        NULL as persNaklady,
                        NULL as otherCosts,
                        NULL as marze
                FROM visits AS v

            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);