<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
$messageBox = new MessageBox();

session_start();

if (isset($_GET["id"])) {
   
    $query = "  SELECT
            r.id,            
            AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
            AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
            AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
            r.date,
            r.hour,
            r.minute,
            r.note,
            r.deleteHash,
            r.personnel,
            r.service as serviceId            
        FROM reservations AS r
        LEFT JOIN adminLogin ON adminLogin.id = r.personnel
        LEFT JOIN services ON services.id = r.service
        WHERE
            r.id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    $therapist = $result->personnel;
    $service = $result->serviceId;
    $send_email_to_Client = 1;      
    $clientEmail = $result->email;
    $name = $result->name;
    $surname = $result->surname;
    $phone = $result->phone;
    $hour = $result->hour;
    $minute = $result->minute;
    $date = $result->date;
    $hash = $result->deleteHash;
    $note = $result->note;      
    $source = $resultAdminUser->displayName . ' (res)';
    $deleteReason = null;
    $addMessageBox = 'Y';

    $type = 'new'; //poslat emaily, které se mají poslat při zrušení rezervace z administrátorského rozhraní
    include("../emailResSend.php");    
} else {
    $messageBox->addText("Chybí ID rezervace, email nebyl odeslán");
    $messageBox->setClass("alert-danger");
}

$_SESSION["messageBox"] = $messageBox;
header("Location: viewReservations.php?date=" . $_GET["returnTo"]);

