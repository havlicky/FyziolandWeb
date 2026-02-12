<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "  SELECT                
                GROUP_CONCAT(DISTINCT AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') ORDER BY AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') SEPARATOR '; ') as emails                
            FROM groupExcercisesParticipants AS gep
            LEFT JOIN clients AS c ON c.id = gep.client            
            WHERE gep.groupExcercise = :id AND :person1 IS NULL";
$stmt = $dbh->prepare($query);
$stmt->bindParam("id", $_POST["id"], PDO::PARAM_INT);
$stmt->bindValue(":person1", intval($resultAdminUser->isSuperAdmin) === 1 ? NULL : $resultAdminUser->id, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

echo json_encode($result);