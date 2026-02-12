<?php

//require_once "../header.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/SMTP.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

//najdu id rezervace dle hash
$stmt = $dbh->prepare("SELECT id, service, date FROM reservations WHERE deleteHash = :hash");
$stmt->bindParam(":hash", htmlentities($_POST["hash"]), PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);	 

//uložím informaci o dokončeném dotazníku
$query = "UPDATE reservations SET qFinished = NOW() WHERE id = :idres";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":idres", $result->id, PDO::PARAM_INT);
$stmt->execute();

//zjištění info o klientovi/rezervaci pro email terapeutovi.
$stmt = $dbh->prepare("SELECT
        AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
        AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
        r.hour,
        r.minute,
        al.displayName,
        s.name as serviceName,
        r.note,
        r.internalNote,
        r.date,
        r.attName
        
        FROM reservations r 
        
        LEFT JOIN adminLogin al ON al.id = r.personnel
        LEFT JOIN services s ON s.id = r.service

        WHERE r.id = :idres");
$stmt->bindParam(":idres", $result->id, PDO::PARAM_INT);
$stmt->execute();
$reservation = $stmt->fetch(PDO::FETCH_OBJ);	 

//poslat e-mail do dokončeném dotazníku
$mail = new PHPMailer\PHPMailer\PHPmailer();
$mail->Host = "localhost";
$mail->SMTPKeepAlive = true;
$mail->CharSet = "utf-8";
$mail->IsHTML(true);

$mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
//$mail->AddAddress("blazkova@fyzioland.cz");
$mail->AddBCC("rezervace@fyzioland.cz");
$mail->Subject = "Klient DOKONČIL vyplňování senzorického dotazníku";

$mail->Subject = "Dotazník - vyplněn klientem";
$mail->Body = "Vážená kolegyně, vážený kolego,<br><br>";
$mail->Body .= "následující klient dokončil vyplňování senzorického dotazníku:<br><br>";

$mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $reservation->name . " " . $reservation->surname. "</td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $reservation->date)->format("j. n. Y") . " " . $reservation->hour . ":" . str_pad($reservation->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($reservation->displayName) . "</b></td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($reservation->serviceName) . "</b></td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Poznámka klienta k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($reservation->note) . "</td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Interní poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($reservation->internalNote) . "</td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Název souboru s indikací od lékaře</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($reservation->attName) . "</td></tr>";
$mail->Body .= "</table>";
$mail->Body .= "<br>";

$mail->Body .= "Automatický email systému Fyzioland.";      

$mail->Body .= "<br><br>";        

$mail->Body .= "<table style='border-collapse: collapse;'><tr>";
$mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
$mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
$mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
$mail->Body .= "Kašovická 1608/4, 104 00 Praha 22 - Uhříněves<br>";
$mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
$mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
$mail->Body .= "</td>";
$mail->Body .= "</tr></table>";

$mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");

$mail->Send();