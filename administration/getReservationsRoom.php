<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";
require_once "../php/class.settings.php";

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

$query = "  SELECT                
                'res' as type,
                r.id,
                r.date,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS time,
                CONCAT(
                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),
                    ', ', 
                    AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')                      
                ) AS client,
                (SELECT 
                    c.difficulty 
                 FROM clients c 
                 WHERE 
                    c.id = r.client OR 
                    (r.client is null AND (r.phone = c.phone OR r.email = c.email))                     
                 ORDER BY c.difficulty DESC 
                 LIMIT 1
                ) as difficulty,
                s.shortcut as service,
                r.personnel
            FROM reservations r
            
            LEFT JOIN adminLogin al ON al.id = r.personnel
            LEFT JOIN services s ON s.id = r.service            
            
            WHERE
                (al.roomAllocation = 1) AND
                al.active = 1 AND
                r.date = :date AND
                r. active = '1'
            
            UNION
            
            SELECT
                'task' as type,
                t.id,
                t.date,
                DATE_FORMAT(CAST(CONCAT(t.hour, ':', t.minute, ':00') AS TIME), '%H:%i') AS time,
                '' as client,
                '' as difficulty,
                CONCAT(t.plan, IF(t.reality IS NULL OR t.reality = '', '', '<FONT color = RED><big> <b>+<b></FONT></big>')) as service,
                t.personnel
            FROM tasks t
            
            LEFT JOIN adminLogin al ON al.id = t.personnel

            WHERE 
                (al.roomAllocation = 1) AND
                al.active = 1 AND
                t.date = :date 

            ORDER BY date, time
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);