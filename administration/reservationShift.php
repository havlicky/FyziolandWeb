<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
$messageBox = new MessageBox();

session_start();

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

if (empty($_POST["freeslotid"])) {
    $messageBox->addText("Chybí id volného slotu.");
    $messageBox->setClass("alert-danger");
    $_SESSION["messageBox"] = $messageBox;
    die();
}

if (empty($_POST["id"])) {
    $messageBox->addText("Chybí id rezervace.");
    $messageBox->setClass("alert-danger");
    $_SESSION["messageBox"] = $messageBox;
    die();
}

//dotažení hodnot z původní rezervace
$query = "  SELECT 	
                r.personnel,
                al.displayname,
                al.shortcut,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') as name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') as surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') as email,
                r.date,
                r.hour,
                r.minute,
                r.service,
                r.internalNote,
                r.note,
                r.deleteHash
            FROM reservations r
            LEFT JOIN adminLogin al ON al.id=r.personnel
            WHERE
                r.id = :id
            ";
$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":id", $_POST["id"], PDO::PARAM_INT);
$stmt->execute();
$resultformerreservation = $stmt->fetch(PDO::FETCH_OBJ);

//dotažení hodnot z volného slotu pro udate rezervace
$query = "  SELECT 	
                person,
                date,
                HOUR(time) as hour,
                MINUTE(time) as minute
            FROM `personAvailabilityTimetable`                         

            WHERE
                id = :freeslotid
            ";
$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":freeslotid", $_POST["freeslotid"], PDO::PARAM_INT);
$stmt->execute();
$resultfreeslot = $stmt->fetch(PDO::FETCH_OBJ);

// kontrola, že na nový vybraný čas a den ještě rezervace žádná neexistuje
    $query = "  SELECT
                    COUNT(*) AS count
                FROM reservations
                WHERE
                    date = :date AND
                    hour = :hour AND
                    minute = :minute AND
                    personnel = :personnel AND
                    active = 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":date", $resultfreeslot->date, PDO::PARAM_STR);
    $stmt->bindParam(":hour", $resultfreeslot->hour, PDO::PARAM_INT);
    $stmt->bindParam(":minute", $resultfreeslot->minute, PDO::PARAM_INT);
    $stmt->bindParam(":personnel", $resultfreeslot->person, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (intval($result["count"]) === 1) {
        $messageBox->addText("Na tento čas již existuje jiná rezervace. Vyberte prosím jiný termín.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;        
        die();
    }

//update rezervace na základě požadavku na přesun rezervace na dřívější termín
$newinternalnote = $resultformerreservation->internalNote . ' Presun z ' .  $resultformerreservation->shortcut . ', '. (new DateTime())->createFromFormat("Y-m-d", $resultformerreservation->date)->format("j. n. Y") .' '.  $resultformerreservation->hour . ":" . str_pad($resultformerreservation->minute, 2, "0", STR_PAD_LEFT) ;
$query = "  UPDATE reservations SET
                date = :date,
                personnel = :personnel,
                hour = :hour,
                minute = :minute,
                internalNote = :internalnote,
                sooner = NULL,
                shiftUser = :user,
                shiftTimestamp = NOW(),
                shiftReason = :shiftreason
            WHERE
                id = :id
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":personnel", $resultfreeslot->person, PDO::PARAM_INT);
$stmt->bindParam(":date", $resultfreeslot->date, PDO::PARAM_STR);
$stmt->bindParam(":hour", $resultfreeslot->hour, PDO::PARAM_STR);
$stmt->bindParam(":minute", $resultfreeslot->minute, PDO::PARAM_INT);
$stmt->bindParam(":internalnote", $newinternalnote, PDO::PARAM_STR);
$stmt->bindValue(":id", $_POST["id"], PDO::PARAM_INT);
$stmt->bindValue(":user", $_POST["user"], PDO::PARAM_INT);
$stmt->bindParam(":shiftreason", $_POST["shiftreason"], PDO::PARAM_STR);

if ($stmt->execute()) {                                        
    //dotažení názvu služby a jméno terapeuta a dalích info pro email 
    
    $query = "  SELECT
                        (SELECT displayName FROM adminLogin WHERE id = :terapist) AS terapist,
                        (SELECT newReservationAlerts FROM adminLogin WHERE id = :terapist) AS terapistEmailYN,
                        (SELECT name FROM services WHERE id = :service) AS service";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":terapist", $resultfreeslot->person, PDO::PARAM_INT);
    $stmt->bindParam(":service", $resultformerreservation->service, PDO::PARAM_INT);
    $stmt->execute();
    $resultDetail = $stmt->fetch(PDO::FETCH_OBJ);
        
    //poslání informačního emailu terapeutovi bez tel. čísla a emailu                
    if ($resultDetail->terapistEmailYN == 1) {                                

        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");            
        $mail->Subject = "Fyzioland - PŘESUN rezervace";

        $mail->Body = "<html><head></head><body style='padding: 10px;'>";
        $mail->Body .= "Vážená kolegyně, vážený kolego,<br><br>";
        $mail->Body .= "rádi bychom Vás informovali, že následující rezervace byla přesunuta na jiný den a čas (viz níže):<br><br>";

        $mail->Body .= "Původní rezervace, která byla přesunem <b>ZRUŠENA</b>:<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $resultformerreservation->name. " " . $resultformerreservation->surname . "</td></tr>";            
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $resultformerreservation->date)->format("j. n. Y") . " " . $resultformerreservation->hour . ":" . str_pad($resultformerreservation->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultformerreservation->displayname) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->service) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($resultformerreservation->note) . "</td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br>";

        $mail->Body .= "<b>Nová platná rezervace:</b><br><br>";
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $resultformerreservation->name. " " . $resultformerreservation->surname . "</td></tr>";            
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $resultfreeslot->date)->format("j. n. Y") . " " . $resultfreeslot->hour . ":" . str_pad($resultfreeslot->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->terapist) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->service) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($resultformerreservation->note) . "</td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br>";        

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
        $mail->Body .= "Více informací o našich službách najdete na našem webu <a href='fyzioland.cz'>fyzioland.cz</a>";
        $mail->Body .= "</body></html>";

        $mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");                              

        $queryTerapistMail = "  SELECT
                                    displayName,
                                    email
                                FROM adminLogin
                                WHERE
                                    id = :terapist";
        $stmtTerapistMail = $dbh->prepare($queryTerapistMail);
        $stmtTerapistMail->bindParam(":terapist", $resultfreeslot->personnel, PDO::PARAM_INT);
        $stmtTerapistMail->execute();
        $resultTerapistMail = $stmtTerapistMail->fetch(PDO::FETCH_OBJ);

        $mail->AddAddress($resultTerapistMail->email, $resultTerapistMail->displayName);        
        $mail->Send();
    }
    
    //email o vytvořené rezervaci klientovi a na rezervace@fyzioland.cz (včetně telefonu a emailové adresy)
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");

    $mail->Subject = "Fyzioland - PŘESUN rezervace";

    $mail->Body = "<html><head></head><body style='padding: 10px;'>";
    $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
    
    $mail->Body .= "rádi bychom Vás informovali, že následující rezervace byla přesunuta na jiný den a čas (viz níže):<br><br>";

    $mail->Body .= "Původní rezervace, která byla přesunem <b>ZRUŠENA</b>:<br><br>";
    $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $resultformerreservation->name. " " . $resultformerreservation->surname . "</td></tr>";            
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $resultformerreservation->date)->format("j. n. Y") . " " . $resultformerreservation->hour . ":" . str_pad($resultformerreservation->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultformerreservation->displayname) . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->service) . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($resultformerreservation->note) . "</td></tr>";
    $mail->Body .= "</table>";                
    $mail->Body .= "<br>";

    $mail->Body .= "<b>Nová platná rezervace:</b><br><br>";
    $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $resultformerreservation->name. " " . $resultformerreservation->surname . "</td></tr>";            
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $resultfreeslot->date)->format("j. n. Y") . " " . $resultfreeslot->hour . ":" . str_pad($resultfreeslot->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->terapist) . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->service) . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($resultformerreservation->note) . "</td></tr>";
    $mail->Body .= "</table>";
    $mail->Body .= "<br>";

    $mail->Body .= "V případě, že je třeba rezervaci z&nbsp;vážných důvodů zrušit, můžete tak učinit maximálně 48 hodin předem. Ke zrušení rezervace dojde kliknutím na následující odkaz <a href='https://fyzioland.cz/cancelReservation.php?email=" . urlencode($resultformerreservation->email) . "&hsh=" . $resultformerreservation->deleteHash . "'>zrušení rezervace</a>. ";
    $mail->Body .= "Potvrzení o zrušení rezervace Vám bude zasláno obratem na Váš email.";
    $mail->Body .= "<br><br>";    

    $mail->Body .= "<b>Storno podmínky:</b> V případě, že se klient nedostaví na terapii nebo dojde ke zrušení rezervace ze strany klienta z libovolného důvodu později než 48 hodin před zahájením terapie, je klient povinen v souladu se všeobecnými obchodními podmínkami uhradit společnosti Fyzioland 100% z ceny terapie jako storno poplatek. Rezervací termínu na terapii vyjadřuje klient souhlas s těmito storno podmínkami.";
    $mail->Body .= "<br><br>";
    $mail->Body .= "<b>Důležité upozornění pro nezletilé klienty:</b> V případě, že se jedná o rezervaci pro nezletilého klienta, je nezbytné, aby jeho zákonný zástupce byl přítomen po celou dobu terapie v prostorách Fyzioland.";
    $mail->Body .= "<br><br>";  
    $mail->Body .= "Děkujeme Vám za Vaši rezervaci a&nbsp;těšíme se na Vaši návštěvu.<br><br>";
    $mail->Body .= "<br><br>";  

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
    $mail->Body .= "Více informací o našich službách najdete na našem webu <a href='fyzioland.cz'>fyzioland.cz</a>";
    $mail->Body .= "</body></html>";

    $mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");        
    $mail->AddAddress("rezervace@fyzioland.cz", "Fyzioland - rezervace");
    
    if ( $_POST["sendEmail"]!=true ) {
            $messageBox->addText("Rezervace byla v pořádku přesunuta a notifikační e-mail klientovi <b>NEBYL</b> odeslán.");
        } else {
            $messageBox->addText("Rezervace byla v pořádku přesunuta a notifikační e-mail klientovi <b>BYL</b> odeslán.");    
            $mail->AddAddress($resultformerreservation->email, $resultformerreservation->name . " " . $resultformerreservation->surname);
        }
        
    $mail->Send();
        
} else {
    $messageBox->addText("Požadavek se nepodařilo vytvořit. Zkuste to prosím znovu.");
    $messageBox->setClass("alert-danger");
}

$_SESSION["messageBox"] = $messageBox;

header("Location: viewReservations.php?date=" . $_POST["returnTo"]);