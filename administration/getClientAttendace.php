<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "  SELECT                                 
                gep.client as clientId,
                CONCAT(AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'), ', ', AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')) AS client,
                gep.note,
                IF((SELECT COUNT(client) FROM attendance WHERE attendance.ge = :ge AND attendance.client = gep.client AND attendance.ucast = '1') > 0, 'checked', NULL) as ucast,
                IF((SELECT COUNT(client) FROM attendance WHERE attendance.ge = :ge AND attendance.client = gep.client AND attendance.omluva = '1') > 0, 'checked', NULL) as omluva,
                IF((SELECT COUNT(client) FROM attendance WHERE attendance.ge = :ge AND attendance.client = gep.client AND attendance.nechodi = '1') > 0, 'checked', NULL) as nechodi
            FROM groupExcercisesParticipants gep            
            LEFT JOIN clients c ON gep.client = c.id
            WHERE gep.groupExcercise = :ge           
            ORDER BY AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')";
$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":ge", $_POST["ge"], PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
