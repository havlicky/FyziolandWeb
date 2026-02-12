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

for ($i = 1; $i <= 1; $i++) {

    $query = " 
                SELECT                                                                            
                    CONCAT(
                    DAY(:dateFrom), '.',
                    MONTH(:dateFrom), '.',
                    YEAR(:dateFrom)) as datum,
                    SUBSTRING(DAYNAME(:dateFrom),1,3) as denTydne,                
                    a.displayName,
                    a.id as userid,
                    DATE(:dateFrom) as date,
                    (SELECT
                        COUNT(r.id)                   
                        FROM reservations r                    
                            WHERE
                                DATE(r.date) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 1 AND
                                r.personnel = a.id AND
                               (AES_DECRYPT(r.email, 'wbQbRR9ddbtR9hG5FBzE') != 'jiri.havlicky@fyzioland.cz' OR AES_DECRYPT(r.email, 'wbQbRR9ddbtR9hG5FBzE') IS NULL)
                        ) as pocetRezervaci,
                    (SELECT
                        COUNT(pat.id)
                        FROM personAvailabilityTimetable pat
                           WHERE
                                pat.person = a.id AND pat.date BETWEEN :dateFrom AND :dateTo AND ! EXISTS(
                                    SELECT
                                        res.id
                                    FROM
                                        reservations res
                                    WHERE
                                        res.active = 1 AND res.personnel = pat.person AND res.date = pat.date AND CAST(
                                            CONCAT(res.hour, ':', res.minute) AS TIME
                                        ) = pat.time
                                    LIMIT 1)) as volneSloty,
                    (SELECT
                        IFNULL(SUM(s.price),0)                   
                        FROM reservations r
                        LEFT JOIN services s ON s.id = r.service
                            WHERE
                                DATE(r.date) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 1 AND
                                r.personnel = a.id AND
                                (AES_DECRYPT(r.email, 'wbQbRR9ddbtR9hG5FBzE') != 'jiri.havlicky@fyzioland.cz' OR AES_DECRYPT(r.email, 'wbQbRR9ddbtR9hG5FBzE') IS NULL)
                        ) as trzby,
                        NULL as persCosts,
                        NULL as otherCosts,
                        NULL as PL

                    FROM adminLogin a
                    LEFT JOIN reservations res ON a.id = res.personnel
                    WHERE a.active = 1 AND a.indiv = 1
                    GROUP BY 3

                UNION

                SELECT

                    CONCAT(
                    DAY(:dateFrom), '.',
                    MONTH(:dateFrom), '.',
                    YEAR(:dateFrom)) as datum,
                    SUBSTRING(DAYNAME(:dateFrom),1,3) as denTydne,  
                    'CELKEM' as displayName,
                    NULL as userid,
                    DATE(:dateFrom) as date,
                    (SELECT
                        COUNT(r.id)
                        FROM reservations r                    
                        LEFT JOIN adminLogin a ON a.id = r.personnel
                            WHERE
                                DATE(r.date) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 1 AND
                                a.indiv = 1 AND
                                (AES_DECRYPT(r.email, 'wbQbRR9ddbtR9hG5FBzE') != 'jiri.havlicky@fyzioland.cz' OR AES_DECRYPT(r.email, 'wbQbRR9ddbtR9hG5FBzE') IS NULL)
                    ) as pocetRezervaci,
                    (SELECT
                        COUNT(pat.id)
                        FROM personAvailabilityTimetable pat
                        LEFT JOIN adminLogin a ON a.id = pat.person
                           WHERE
                               a.indiv = 1 AND pat.date BETWEEN :dateFrom AND :dateTo AND ! EXISTS(
                                    SELECT
                                        res.id
                                    FROM
                                        reservations res
                                    WHERE
                                        res.active = 1 AND res.personnel = pat.person AND res.date = pat.date AND CAST(
                                            CONCAT(res.hour, ':', res.minute) AS TIME
                                        ) = pat.time
                                    LIMIT 1)) as volneSloty,
                    (SELECT
                        IFNULL(SUM(s.price),0)                   
                        FROM reservations r
                        LEFT JOIN services s ON s.id = r.service
                        LEFT JOIN adminLogin a ON a.id = r.personnel
                            WHERE
                                DATE(r.date) BETWEEN :dateFrom AND :dateTo AND                 
                                r.active = 1 AND
                                a.indiv = 1 AND
                                (AES_DECRYPT(r.email, 'wbQbRR9ddbtR9hG5FBzE') != 'jiri.havlicky@fyzioland.cz' OR AES_DECRYPT(r.email, 'wbQbRR9ddbtR9hG5FBzE') IS NULL)
                        ) as trzby,
                        NULL as persCosts,
                        (DATEDIFF(:dateTo, :dateFrom) + 1) as otherCosts,                        
                        NULL as PL

                FROM reservations r
                GROUP BY 3

                    ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchALL(PDO::FETCH_OBJ);   
}

echo json_encode($results);
