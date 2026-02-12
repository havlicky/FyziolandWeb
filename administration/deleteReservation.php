<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
$messageBox = new MessageBox();

session_start();

if (isset($_POST["id"])) {        
    
    //zjištění uživatele, který provedl smazání rezervace
    $query = "SELECT id, cancelReservationCheck, displayName FROM adminLogin WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_POST["user"], PDO::PARAM_INT);
    $stmt->execute();
    $resultUser = $stmt->fetch(PDO::FETCH_OBJ);
    
    // označení rezervace jako neaktivní
    $query = "  UPDATE reservations
                SET 
                    active = 0,
                    deleteUser = :deleteUser,
                    deleteTimeStamp = :deleteTimestamp,
                    deleteReason = :deleteReason
                WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
    $stmt->bindParam(":deleteUser", $resultUser->id, PDO::PARAM_INT);
    $stmt->bindValue(":deleteTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $stmt->bindParam(":deleteReason", $_POST["deletereason"], PDO::PARAM_STR);
    
    //načtení základních dat o rezervaci pro mailing
    if ($stmt->execute()) {        
        $messageBox->addText("Rezervace byla v pořádku smazána.<br> ");
        $query = "  
            SELECT
                r.id,
                services.id as serviceId,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                r.date,
                r.hour,
                r.minute,
                r.note,
                adminLogin.displayName AS terapist,
                adminLogin.email AS terapistEmail,
                adminLogin.cancelReservationAlerts,
                a.displayName as deleteUser,
                services.name AS service,
                r.personnel as therapistId,
                
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as time
            FROM reservations AS r
            LEFT JOIN adminLogin ON adminLogin.id = r.personnel
            LEFT JOIN adminLogin a ON a.id = r.deleteUser
            LEFT JOIN services ON services.id = r.service
            WHERE
                r.id = :id
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        // smazání přiřazené místnosti v personAvailabilityTimeTable                
        $query = "  UPDATE personAvailabilityTimetable SET room = NULL 
                        WHERE 
                    time = :time AND
                    date = :date AND
                    person = :person
                    ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":time", $result->time, PDO::PARAM_STR);
        $stmt->bindParam(":date", $result->date, PDO::PARAM_STR);
        $stmt->bindParam(":person", $result->terapistId, PDO::PARAM_INT);
        $stmt->execute();
        
        //***************************************************
        //ODESLÁNÍ EMAILŮ V SOUVISLOSTI SE ZRUŠENOU REZERVACÍ
        //***************************************************       
                
        $therapist = $result->therapistId;
        $service = $result->serviceId;
        $send_email_to_Client = $_POST["sendEmail"];      
        $clientEmail = $result->email;
        $name = $result->name;
        $surname = $result->surname;
        $phone = $result->phone;
        $hour = $result->hour;
        $minute = $result->minute;
        $date = $result->date;
        $hash = null;
        $note = $result->note;      
        $deleteReason = $_POST["deletereason"];
        $deleteSource = $result->deleteUser . ' (res)';
        $addMessageBox = 'Y';
        
        $type = 'cancelGeneral'; //poslat emaily, které se mají poslat při zrušení rezervace z administrátorského rozhraní
        include("../emailResSend.php");
        
        //notifikace pro mě, pokud rezervaci zrušil terapeut
        if ($resultUser->cancelReservationCheck == 1) {
            $type = 'cancelbytherapist'; 
            include("../emailResSend.php");
        }        
    
    } else {
        $messageBox->addText("Rezervaci se nepodařilo odstranit.");
        $messageBox->setClass("alert-danger");
    }
}

$_SESSION["messageBox"] = $messageBox;

if ($_POST["source"]==1) { header("Location: viewReservations.php?date=" . $_POST["returnTo"]);}
if ($_POST["source"]==2) { header("Location: viewAllReservations.php");}

