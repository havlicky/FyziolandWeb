<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "  SELECT 
                t.id,
                t.plan,
                t.reality
            FROM tasks t

            WHERE 
                personnel = :person AND
                date = :date AND
                DATE_FORMAT(CAST(CONCAT(t.hour, ':', t.minute, ':00') AS TIME), '%H:%i') = :time
            ";
$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindValue(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->bindValue(":time", $_POST["time"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

echo json_encode($result);
