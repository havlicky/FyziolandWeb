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
            r.service as serviceId,
            adminLogin.displayName AS terapist,
            adminLogin.email AS terapistEmail,
            adminLogin.cancelReservationAlerts,
            services.name AS service
        FROM reservations AS r
        LEFT JOIN adminLogin ON adminLogin.id = r.personnel
        LEFT JOIN services ON services.id = r.service
        WHERE
            r.id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");
    $mail->AddAddress($result->email, $result->name . " " . $result->surname);
    $mail->AddBCC("rezervace@fyzioland.cz");            

    $mail->Subject = "Fyzioland - připomenutí vyplnění senzorického dotazníku";

    $mail->Body = "<html><head></head><body style='padding: 10px;'>";
    $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
    $mail->Body .= "rádi bychom Vám připomněli vyplnění senzorického dotazníku, který nám slouží jako jeden z důležitých podkladů pro Vaše blížící se vstupní vyšetření na ergoterapii. Na jeho vyplnění si, prosím, vyhraďte 30-45 minut. Dotazník je elektronický a zobrazí se Vám po kliknutí na následující odkaz: ";
    $mail->Body .= "<a href='https://www.fyzioland.cz/dotaznik/index.php?id=" . $result->deleteHash  . "'>senzorický dotazník</a><br><br>";                     

    $mail->Body .= "Jakékoliv dotazy Vám velice rádi zodpovíme na tel. čísle:  +420 775 910 749 nebo emailem na adrese info@fyzioland.cz.<br><br>";
    $mail->Body .= "Těšíme se na Vás.<br><br>";

    $mail->Body .= "Automatický email systému Fyzioland<br><br>";
    $mail->Body .= "<table style='border-collapse: collapse;'><tr>";
    $mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
    $mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
    $mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
    $mail->Body .= "Kašovická 1608/4, 104 00 Praha 10 - Uhříněves<br>";
    $mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
    $mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
    $mail->Body .= "</td>";
    $mail->Body .= "</tr></table><br>";    
    $mail->Body .= "</body></html>";

    $mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");        

    $mail->Send();
    $messageBox->addText("Klientovi byl odeslán notifikační e-mail.");                            
    
}
    
 else {
    $messageBox->addText("Email se NEPODAŘILO odeslat.");
    $messageBox->setClass("alert-danger");
}

$_SESSION["messageBox"] = $messageBox;
header("Location: viewVstupKontrolALL.php");

