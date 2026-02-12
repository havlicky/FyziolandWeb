<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";


if (intval($resultAdminUser->slotChange) !== 1) {
    die();
}


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


//zjištění, zda daný uživatel má nebo nemá mít viditelné sloty na webu
$query = "  SELECT defWebSlots FROM adminLogin WHERE id = :person";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->execute();
$resultWeb = $stmt->fetch(PDO::FETCH_OBJ);

$query = "  SELECT
                id                
            FROM personAvailabilityTimetable 
           
            WHERE
                person = :person AND
                date = :date AND
                time = :time";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
$stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

if ($result === FALSE) {
    $query = "  INSERT INTO personAvailabilityTimetable (
                    person,
                    date,
                    time,
                    web
                ) VALUES (
                    :person,
                    :date,
                    :time,
                    :defWebSlots
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":defWebSlots", $resultWeb->defWebSlots, PDO::PARAM_INT);
    $stmt->execute();
    $patId = $dbh->lastInsertId();
    
    //načtení šablony zakázaných služeb pro daný čas, datum a osobu
    $query = "  SELECT
                    service
            FROM patsbTemplates
            WHERE
                person = :person AND
                dayOfWeek = DAYOFWEEK(:date) AND
                time = :time";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
    $stmt->execute();
    $banServices = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    //vložení zakázaných služeb do tabulky 'patBanServices'
    foreach ($banServices as $banService) {
                
        $service = $banService->service;
        $query = "  INSERT INTO patBanServices (
                        patId,
                        serviceId                        
                    ) VALUES (
                        :patid,
                        :serviceid                        
                    )";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":patid",  $patId, PDO::PARAM_INT);
        $stmt->bindParam(":serviceid",$service, PDO::PARAM_INT);        
        $stmt->execute();        
         
        /*
        if ($stmt->execute()) {
            echo "1";
        } else {
            echo "0";
        }      
         */                
    }
    echo "1";
    
} else {
    $query = "  DELETE FROM personAvailabilityTimetable 
                WHERE
                    person = :person AND
                    date = :date AND
                    time = :time";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time", $_POST["time"], PDO::PARAM_STR);
    $stmt->execute();
    
    
    $patId = $result->id;
    $query = "  DELETE FROM patBanServices 
                WHERE
                    patId = :patid";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":patid", $patId, PDO::PARAM_INT);    
    $stmt->execute();            
    echo "1";
}

