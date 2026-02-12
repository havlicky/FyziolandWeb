<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "                 
            SELECT 
                al.limitW_ErgoClients as hranice
            FROM adminLogin al
            
            WHERE 
                al.id = :person  
                    ";
$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->execute();
$person = $stmt->fetch(PDO::FETCH_OBJ);

$query = "                 
            SELECT 
                IF(COUNT(r.id)>:limit, CONCAT(COUNT(r.id), ' - PŘES LIMIT'), COUNT(r.id))  as count
            FROM reservations r 
            
            WHERE 
                EXISTS(
                    SELECT
                        res.id
                    FROM reservations res                    
                    WHERE
                        (res.service = 2 OR res.service = 10) AND
                        res.date > '2023-06-30' AND AES_DECRYPT(res.email, '" . Settings::$mySqlAESpassword . "')!='jiri.havlicky@fyzioland.cz' AND
                        ((res.client = r.client) OR (res.client != r.client AND (res.phone = r.phone OR res.email = r.email)))
                    ) AND 
                r.date BETWEEN :dateFrom AND :dateTo AND
                r.personnel = :person AND
                r.active = 1  
                    ";
$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindValue(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindValue(":limit", $person->hranice, PDO::PARAM_INT);
$stmt->bindValue(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

echo json_encode($result);
