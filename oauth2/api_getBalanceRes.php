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
                a.displayName,
                (SELECT
                    COUNT(r.id)                   
                    FROM reservations r                    
                        WHERE
                            DATE(r.creationTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                            r.active = 1 AND
                            r.personnel = a.id AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                            
                    ) as prirustek,
                (SELECT
                    -COUNT(r.id)                   
                    FROM reservations r                    
                        WHERE
                            DATE(r.deleteTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                            r.active = 0 AND
                            r.personnel = a.id AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    ) as ubytek,
                (SELECT
                    (SELECT
                        COUNT(r.id)                   
                        FROM reservations r
                         
                            WHERE
                                DATE(r.creationTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 1 AND
                                r.personnel = a.id AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    ) -
                    (SELECT
                        COUNT(r.id)                   
                        FROM reservations r                          
                            WHERE
                                DATE(r.deleteTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 0 AND
                                r.personnel = a.id AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    )
                ) as saldo
                FROM adminLogin a
                LEFT JOIN reservations res ON a.id = res.personnel
                WHERE a.active = 1 AND a.indiv = 1
                GROUP BY a.displayName
                
                UNION
                
                SELECT                                                                            
                'CELKEM ERGO' as displayName,
                (SELECT
                    COUNT(r.id)                   
                    FROM reservations r
                    LEFT JOIN adminLogin a ON a.id = r.personnel
                        WHERE
                            DATE(r.creationTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                            r.active = 1 AND
                            a.isergo = 1  AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    ) as prirustek,
                (SELECT
                    -COUNT(r.id)                   
                    FROM reservations r
                    LEFT JOIN adminLogin a ON a.id = r.personnel
                        WHERE
                            DATE(r.deleteTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                            r.active = 0 AND
                            a.isergo = 1 AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'                        
                    ) as ubytek,
                (SELECT
                    (SELECT
                        COUNT(r.id)                   
                        FROM reservations r
                        LEFT JOIN adminLogin a ON a.id = r.personnel
                            WHERE
                                DATE(r.creationTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 1 AND
                                a.isergo = 1 AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    ) -
                    (SELECT
                        COUNT(r.id)                   
                        FROM reservations r
                        LEFT JOIN adminLogin a ON a.id = r.personnel
                            WHERE
                                DATE(r.deleteTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 0 AND
                                a.isergo = 1 AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    )
                ) as saldo               
                
                UNION
                
                SELECT                                                                            
                'CELKEM' as displayName,
                (SELECT
                    COUNT(r.id)                   
                    FROM reservations r
                    LEFT JOIN adminLogin a ON a.id = r.personnel
                        WHERE
                            DATE(r.creationTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                            r.active = 1 AND
                            a.indiv = 1 AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    ) as prirustek,
                (SELECT
                    -COUNT(r.id)                   
                    FROM reservations r
                    LEFT JOIN adminLogin a ON a.id = r.personnel
                        WHERE
                            DATE(r.deleteTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                            r.active = 0 AND
                            a.indiv = 1 AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'                          
                    ) as ubytek,
                (SELECT
                    (SELECT
                        COUNT(r.id)                   
                        FROM reservations r
                        LEFT JOIN adminLogin a ON a.id = r.personnel
                            WHERE
                                DATE(r.creationTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 1 AND
                                a.indiv = 1 AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    ) -
                    (SELECT
                        COUNT(r.id)                   
                        FROM reservations r
                        LEFT JOIN adminLogin a ON a.id = r.personnel
                            WHERE
                                DATE(r.deleteTimestamp) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 0 AND
                                a.indiv = 1 AND
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') !='jiri.havlicky@fyzioland.cz'
                    )
                ) as saldo 
                
                ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);
