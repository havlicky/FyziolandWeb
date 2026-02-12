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
                D.id,
                D.date,
                CONCAT (AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) as vkladatel,
                D.amount,
                CASE WHEN D.id IS NOT NULL 
                        THEN 'Jednoúčelový (rekondice)'
                        ELSE NULL
                END as type,                
                (SELECT GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'),' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) ORDER BY 1 ASC SEPARATOR ', ') FROM relationdepositsallowedclients LEFT JOIN clients ON clients.id = relationdepositsallowedclients.clientId WHERE DepositId = D.id) AS allowedClients,
                al.displayName as userSurname,
                CONCAT(SUBSTRING(D.note,1,80),'...') as note,
                D.paymentDate,
                CASE WHEN D.id IS NOT NULL 
                        THEN 'SKUP'
                        ELSE NULL
                END as zdroj

                FROM deposits AS D

                LEFT JOIN clients AS c ON c.id = D.clientId			            
                LEFT JOIN adminLogin AS al ON al.id= D.userId                            

               WHERE                        
                    D.date BETWEEN :dateFrom AND :dateTo		              
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);