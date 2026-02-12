<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "  SELECT
                gep.id,                
                ge.id as geId,
                c.id as clientId,
                gep.paid,
                gep.faPohoda,
                AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email,
                SUBSTRING(AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "'),6,11) AS phone,
                CONCAT(
                    DAY(gep.registrationDate), 
                    '. ', 
                    MONTH(gep.registrationDate), 
                    '. ', 
                    YEAR(gep.registrationDate)) as registrationDate,                
                CONCAT (
                (SELECT COUNT(gep2.id) 
                        FROM groupExcercisesParticipants as gep2
                        LEFT JOIN groupExcercises AS ge2 ON ge2.id = gep2.groupExcercise
                        WHERE 
                        client = gep.client AND
                        ge2.date <=ge.date
                        ), '-',
                (SELECT COUNT(gep2.id) 
                        FROM groupExcercisesParticipants as gep2
                        LEFT JOIN groupExcercises AS ge2 ON ge2.id = gep2.groupExcercise
                        WHERE 
                        gep2.client = gep.client AND 
                        ge2.instructor = ge.instructor AND
                       ge2.date <=ge.date
                       ),'-',
                (SELECT COUNT(gep2.id) 
                        FROM groupExcercisesParticipants as gep2
                        LEFT JOIN groupExcercises AS ge2 ON ge2.id = gep2.groupExcercise
                        WHERE 
                        gep2.client = gep.client AND 
                        ge2.instructor = ge.instructor AND
                        ge2.title =ge.title AND
                        ge2.date<=ge.date)
                ) AS NrReg,
                gep.note,
                ge.semestralCourse, 
                gep.smsIdentification
            FROM groupExcercisesParticipants AS gep
            LEFT JOIN clients AS c ON c.id = gep.client
            LEFT JOIN groupExcercises AS ge ON ge.id = gep.groupExcercise
            WHERE gep.groupExcercise = :id AND :person1 IS NULL";
$stmt = $dbh->prepare($query);
$stmt->bindParam("id", $_POST["id"], PDO::PARAM_INT);
$stmt->bindValue(":person1", intval($resultAdminUser->isSuperAdmin) === 1 ? NULL : $resultAdminUser->id, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

header("Content-type: application/json; charset=utf-8");

foreach ($results as $result) {
    if (empty($result->smsIdentification)) {
        $result->phone = $result->phone . ' <input type="checkbox" name="sms" title="Označit rezervaci pro odeslání SMS">';
    } else {
        $result->phone = $result->phone . ' <span class="glyphicon glyphicon-phone" aria-hidden="true" title="" name="smsDetailIcon"></span>';
    }
    
    $result->note = '<span data-role="content">'. $result->note . '</span>' . '<a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>';        
    $result->faPohoda = '<span data-role="content">'. $result->faPohoda . '</span>' . '<a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>';    
    
    if ($result->paid == "1") {$result->paid = '<input type="checkbox" name="groupExcercisePaid" checked>';}
    if ($result->paid == "0") {$result->paid = '<input type="checkbox" name="groupExcercisePaid" "">';}
    
    $result->akce = '<a href="deleteGroupExcerciseParticipant.php?id=' . $result->id . '" name="deleteLink"><span class="glyphicon glyphicon-trash" title="Smazat rezervaci"></span></a>
                     <a href="sendGroupReservation.php?geId=' . $result->geId . '&clientId=' . $result->clientId . '" name="emailLink"><span class="glyphicon glyphicon-envelope" title="Poslat klientovi potvrzení rezervace"></span></a>
                     <a href="#" data-type="QRsms" title="Ukázat QR kód pro SMS"> <span class="glyphicon glyphicon-phone"></span></a>';
}

echo json_encode($results);