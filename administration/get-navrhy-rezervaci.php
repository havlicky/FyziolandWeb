<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

session_start();


try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$date = (new DateTime($_POST["date"]));
$dayOfWeek = intval($date->format("N"));
$differenceFromMonday = $dayOfWeek - 1;
$lastMonday = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P" . $differenceFromMonday . "D"));
$lastMondayString = $lastMonday->format('Y-m-d H:i:s');

$firstDayOfMonth = date('Y-m-01');
$lastDayOfMonth = date('Y-m-t');

$query = "  
            SELECT                
                CONCAT(AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'),', ', AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')) AS client,
                AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "'),
                AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "'),
                CONCAT(IF(freqM IS NULL, FreqW, FreqM), IF(freqM IS NULL, 'x za týden', 'x za měsíc')) as frekvence,
                
                CASE
                    WHEN c.slotTypes = 'nicetohave' THEN 'preferované'
                    WHEN c.slotTypes = 'must' THEN 'jediné možné'
                END as slotTypes,
                (SELECT                    
                   CONCAT(
                    DAY(r.date), 
                    '. ', 
                    MONTH(r.date), 
                    '. ', 
                    YEAR(r.date))                                                              
                    FROM reservations r                               
                    WHERE
                        (r.client = cat.client OR 
                         r.email = c.email OR
                         r.phone = c. phone    
                        ) AND
                        r.active = 1 AND
                        r.date < :date                    
                    ORDER BY
                        r.date DESC
                    LIMIT 1) as posledniRezervace,
                (SELECT                    
                    GROUP_CONCAT(CONCAT(DAY(r.date),'.',MONTH(r.date),'.',YEAR(r.date), ' ', DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i'),  ' (', a.shortcut,')') SEPARATOR ', ') as date                                                              
                    FROM reservations r               
                    LEFT JOIN adminLogin a ON a.id = r.personnel
                    WHERE
                        (r.client = cat.client OR 
                         r.email = c.email OR
                         r.phone = c. phone    
                        ) AND
                        r.active = 1 AND
                        r.date >= :date                        
                        
                    ORDER BY
                        r.date) as budouciRezervace

            FROM clientAvailabilityTimetable cat
    
            LEFT JOIN clients c ON c.id = cat.client
            
            WHERE
                (c.activeErgoClient = 'Y' OR c.activeErgoClient is NULL) AND
                cat.time = :time AND
                cat.dayOfWeek = WEEKDAY(:date) AND
                
                ((SELECT
                    COUNT(r.id)
                    FROM reservations r
                    WHERE 
                        (r.client = cat.client OR 
                         r.email = c.email OR
                         r.phone = c. phone    
                        ) AND
                        r.active = 1 AND
                        r.date BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY)                        
                ) < c.freqW OR
                (SELECT                    
                    COUNT(r.id)
                    FROM reservations r
                    WHERE 
                        (r.client = cat.client OR 
                         r.email = c.email OR
                         r.phone = c. phone    
                        ) AND
                        r.active = 1 AND
                        date BETWEEN :firstDayOfMonth AND :lastDayOfMonth
                ) <c.freqM
                ) AND
                (SELECT
                    COUNT(r.id)
                    FROM reservations r
                    WHERE 
                        (r.client = cat.client OR 
                         r.email = c.email OR
                         r.phone = c. phone    
                        ) AND
                        r.active = 0 AND
                        r.date = :date AND
                        CAST(CONCAT(r.hour, ':', r.minute) as time) = :time
                ) = 0 AND
                (:therapist IN (SELECT personnel FROM reservations res 
                    WHERE (res.client = cat.client OR 
                           res.email = c.email OR
                           res.phone = c. phone    
                          ) AND
                          res.active = 1))
                
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->bindParam(":lastMonday", $lastMondayString, PDO::PARAM_STR);
$stmt->bindParam(":firstDayOfMonth", $firstDayOfMonth, PDO::PARAM_STR);
$stmt->bindParam(":lastDayOfMonth", $lastDayOfMonth, PDO::PARAM_STR);
$stmt->bindParam(":therapist", $_POST["therapist"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);