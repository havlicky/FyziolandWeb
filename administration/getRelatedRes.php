<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "  SELECT 	                
                CONCAT(
                    DAY(r.date), 
                    '.', 
                    MONTH(r.date), 
                    '.', 
                    YEAR(r.date)) as date,
                SUBSTRING(DAYNAME(r.date),1,3) as denTydne,
                CAST(CONCAT(r.hour, ':', r.minute) as time) as timeFrom,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') as surname,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') as name,                
                s.name as service,
                a.shortcut AS personnel
                
            FROM reservations r
            
            LEFT JOIN adminLogin a ON a.id = r.personnel
            LEFT JOIN services s ON s.id = r.service

            WHERE
                r.date>=:date AND
                r.active = 1 AND

                ( AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone OR
                  AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "')  = :email OR
                  r.client = :client)
            ORDER BY r.date ASC
        ";

$stmt = $dbh->prepare($query);
$stmt->bindValue(":date", $_POST["date"] , PDO::PARAM_STR);
$stmt->bindValue(":email", $_POST["email"] , PDO::PARAM_STR);
$stmt->bindValue(":phone", $_POST["phone"] , PDO::PARAM_STR);
$stmt->bindValue(":client", $_POST["client"] , PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
