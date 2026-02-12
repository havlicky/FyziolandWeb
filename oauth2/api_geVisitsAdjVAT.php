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
                    v.id,
                    v.date,
                    CONCAT (AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) as client,
                    v.prepaid as amount,
                    CASE WHEN v.id IS NOT NULL 
                        THEN 'Jednoúčelový (rekondice)'
                        ELSE NULL
                    END as typVkladu,
                    CASE WHEN v.id IS NOT NULL 
                        THEN CONCAT (ge.title, ', ', al.displayName)
                        ELSE NULL
                    END as služba,
                    d.id as depositId,
                    CASE WHEN v.id IS NOT NULL 
                        THEN 'SKUP'
                        ELSE NULL
                    END as zdroj
            FROM `visits` v

            LEFT JOIN deposits d ON d.id = v.depositId
            LEFT JOIN clients c ON c.id = v.client
            LEFT JOIN groupExcercises ge ON ge.id = v.ge
            LEFT JOIN adminLogin al ON al.id = ge.instructor
            

            WHERE
                    v.prepaid>0 AND                    
                    v.date BETWEEN :dateFrom AND :dateTo		              
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);