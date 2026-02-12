<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
$messageBox = new MessageBox();

session_start();

if (isset($_GET["id"])) {        

    //zjištění uživatele, který provedl smazání rezervace
    $query = "SELECT id, cancelReservationCheck, displayName FROM adminLogin WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_GET["user"], PDO::PARAM_INT);
    $stmt->execute();
    $resultUser = $stmt->fetch(PDO::FETCH_OBJ);
    
    // označení rezervace jako neaktivní
    $query = "  UPDATE reservations
                SET 
                    active = 0,
                    deleteUser = :deleteUser,
                    deleteTimeStamp = :deleteTimestamp
                WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->bindParam(":deleteUser", $resultUser->id, PDO::PARAM_INT);
    $stmt->bindValue(":deleteTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    
    if ($stmt->execute()) {        
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
                    adminLogin.displayName AS terapist,
                    adminLogin.email AS terapistEmail,
                    adminLogin.cancelReservationAlerts,                    
                    services.name AS service,
                    r.personnel as terapistId,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as time
                FROM reservations AS r
                LEFT JOIN adminLogin ON adminLogin.id = r.personnel
                LEFT JOIN services ON services.id = r.service
                WHERE
                    r.id = :id";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
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
                
        // email pro JH, pokud terapeut smazal rezervaci
        if ($resultUser->cancelReservationCheck == 1) {                                    
            $mail = new PHPMailer\PHPMailer\PHPmailer();
            $mail->Host = "localhost";
            $mail->SMTPKeepAlive = true;
            $mail->CharSet = "utf-8";
            $mail->IsHTML(true);

            $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");
            $mail->AddAddress("jiri.havlicky@fyzioland.cz");            

            $mail->Subject = "Zrušení rezervace TERAPEUTEM";
            $mail->Body = "Vážená kolegyně, vážený kolego,<br>";
            $mail->Body .= "upozorňujeme, že níže uvedená rezervace byla <b>zrušena terapeutem</b>:<br><br>";
            $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->name . " " . $result->surname . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Telefonní kontakt</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->phone . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $result->date)->format("j. n. Y") . " " . $result->hour . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT) . " - " . (intval($result->hour) + 1) . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($result->terapist) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($result->service) . "</b></td></tr>";            
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->note) . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Rezervaci smazal</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultUser->displayName) . "</b></td></tr>";
            $mail->Body .= "</table>";
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
        }
        
        if ( !isset($_GET["sendEmail"]) ) {
            $messageBox->addText("Rezervace byla v pořádku odstraněna a notifikační e-mail klientovi <b>nebyl</b> odeslán.");
        } else {            
            
            // informační email pro klienta o zrušené rezervaci
            $mail = new PHPMailer\PHPMailer\PHPmailer();
            $mail->Host = "localhost";
            $mail->SMTPKeepAlive = true;
            $mail->CharSet = "utf-8";
            $mail->IsHTML(true);

            $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");
            $mail->AddAddress($result->email, $result->name . " " . $result->surname);
            $mail->AddBCC("rezervace@fyzioland.cz");            

            $mail->Subject = "Zrušení rezervace - Fyzioland";
            $mail->Body = "Vážená klientko, vážený kliente,<br>";
            $mail->Body .= "potvrzujeme, že Vaše rezervace byla úspěšně <b>zrušena</b>:<br><br>";
            $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->name . " " . $result->surname . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Telefonní kontakt</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->phone . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $result->date)->format("j. n. Y") . " " . $result->hour . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT) . " - " . (intval($result->hour) + 1) . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($result->terapist) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($result->service) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->note) . "</td></tr>";
            $mail->Body .= "</table>";
            $mail->Body .= "<br><br>";
            $mail->Body .= "Těšíme se, že Vás budeme moci u nás přivítat v jiném termínu.<br><br>";
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
            $messageBox->addText("Rezervace byla v pořádku odstraněna a klientovi byl odeslán notifikační e-mail.");
        
            //e-mail terapeutovi
            if ($result->cancelReservationAlerts == 1) {
                $mail = new PHPMailer\PHPMailer\PHPmailer();
                $mail->Host = "localhost";
                $mail->SMTPKeepAlive = true;
                $mail->CharSet = "utf-8";
                $mail->IsHTML(true);

                $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");
                //$mail->AddBCC("rezervace@fyzioland.cz");
                $mail->AddAddress($result->terapistEmail);

                $mail->Subject = "Zrušení rezervace - Fyzioland";
                $mail->Body = "Vážená kolegyně, vážený kolego,<br>";
                $mail->Body .= "upozorňujeme, že níže uvedená rezervace byla klientem <b>ZRUŠENA</b>:<br><br>";
                $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->name . " " . $result->surname . "</td></tr>";            
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $result->date)->format("j. n. Y") . " " . $result->hour . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT) . " - " . (intval($result->hour) + 1) . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($result->terapist) . "</b></td></tr>";
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($result->service) . "</b></td></tr>";
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->note) . "</td></tr>";
                $mail->Body .= "</table>";
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
            }
        }
    } else {
        $messageBox->addText("Rezervaci se nepodařilo odstranit.");
        $messageBox->setClass("alert-danger");
    }
}

$_SESSION["messageBox"] = $messageBox;


if ($_GET["source"]==1) { header("Location: viewReservations.php?date=" . $_GET["returnTo"]);}
if ($_GET["source"]==2) { header("Location: viewAllReservations.php");}

