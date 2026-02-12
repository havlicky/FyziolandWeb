<?php

require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";

if ( $_GET["hash"] !== "sendFromCron" ) {
    echo "Tento skript je spouštěn pouze automaticky.";
    die();
}

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

//************************************************************
// připomenutí rezervací
//************************************************************

$targetDate = (new DateTime())->add(new DateInterval("P2D"))->format("Y-m-d");

$query = "  SELECT
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                r.date,
                r.hour,
                r.minute,
                s.name AS service,
                a.displayName AS terapist,
                r.note,
                r.deleteHash
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE
                r.alert = 'email' AND
                r.date = :date AND
                r.active = 1 AND
                r.email IS NOT NULL";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":date", $targetDate, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

if (count($results) != 0) {
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);
    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");

    foreach ($results as $result) {
        $mail->clearAllRecipients();
        $mail->addAddress($result->email, htmlspecialchars($result->name . " " . $result->surname));
        $mail->addBCC("rezervace@fyzioland.cz");

        $mail->Subject = "Připomenutí návštěvy - Fyzioland";
        $mail->Body = "Vážená klientko, vážený kliente,<br>";
        $mail->Body .= "na základě Vaší žádosti si Vám dovolujeme připomenout Vaši blížící se návštěvu u nás:<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $result->date)->format("j. n. Y") . " " . $result->hour . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($result->terapist) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($result->service) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->note) . "</td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br>";
        $mail->Body .= "V případě, že je třeba rezervaci z vážných důvodů zrušit, můžete tak učinit maximálně 48 hodin předem. Ke zrušení rezervace dojde kliknutím na následující odkaz <a href='https://fyzioland.cz/cancelReservation.php?email=" . urlencode($result->email) . "&hsh=" . $result->deleteHash . "'>zrušení rezervace</a>. ";
        $mail->Body .= "Potvrzení o zrušení rezervace Vám bude zasláno obratem na Váš email.";
        $mail->Body .= "<br><br>";
        $mail->Body .= "<b>Storno podmínky:</b> V případě, že se klient nedostaví na terapii nebo dojde ke zrušení rezervace ze strany klienta z libovolného důvodu později než 48 hodin před zahájením terapie, je klient povinen v souladu se všeobecnými obchodními podmínkami uhradit společnosti Fyzioland 100% z ceny terapie jako storno poplatek. Rezervací termínu na terapii vyjádřil klient souhlas s těmito storno podmínkami.";
        $mail->Body .= "<br><br>";
        $mail->Body .= "<b>Důležité upozornění pro nezletilé klienty:</b> V případě, že se jedná o rezervaci pro nezletilého klienta, je nezbytné, aby jeho zákonný zástupce byl přítomen po celou dobu terapie v prostorách Fyzioland.";
        $mail->Body .= "<br><br>";
        //$mail->Body .= "Rádi bychom Vás také informovali, že dne <b>3.10.2022 vstoupil v platnost nový ceník našich služeb</b>, jehož aktuální znění naleznete v horním menu na našich webových stránkách ";
        $mail->Body .= "<a href='https://www.fyzioland.cz'>fyzioland.cz</a>";
        $mail->Body .= "<br><br>";        
        $mail->Body .= "Těšíme se na Vás!<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse;'><tr>";
        $mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
        $mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
        $mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
        $mail->Body .= "Kašovická 1608/4, 104 00 Praha 10 - Uhříněves<br>";
        $mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
        $mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
        $mail->Body .= "</td>";
        $mail->Body .= "</tr></table>";

        $mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");

        $mail->Send();
    }

    echo "Odesláno celkem " . count($results) . " připomínek rezervací.";
    
} else {
        echo "Žádné e-maily k odeslání.";
}

//************************************************************
// připomenutí senzorických dotazníků - vstupní vyšetření
//************************************************************

$query = "  SELECT
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                r.date,
                r.hour,
                r.minute,
                s.name AS service,
                a.displayName AS terapist,
                r.note,
                r.deleteHash
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE                
                (
                 r.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY) OR
                 r.date = DATE_ADD(CURDATE(), INTERVAL 10 DAY) OR
                 r.date = DATE_ADD(CURDATE(), INTERVAL 14 DAY)
                ) AND
                r.active = 1 AND
                r.email IS NOT NULL AND
                r.service = 10 AND
                r.qFinished IS NULL";
$stmt = $dbh->prepare($query);
$stmt->execute();
$resultsVstup = $stmt->fetchAll(PDO::FETCH_OBJ);

if (count($resultsVstup) > 0) {
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);
    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");

    foreach ($resultsVstup as $result) {
        $mail->clearAllRecipients();
        $mail->addAddress($result->email, htmlspecialchars($result->name . " " . $result->surname));
        $mail->addBCC("rezervace@fyzioland.cz");

        $$mail->Subject = "Fyzioland - připomenutí vyplnění senzorického dotazníku";

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
    }

    echo "Odesláno celkem " . count($resultsVstup) . " připomínek senzorických dotazníků ke vstupnímu vyšetření.";   
    
} else {
    echo "Žádné e-maily k odeslání.";
}

//************************************************************
// připomenutí senzorických dotazníků - kontrolní vyšetření
//************************************************************

$targetDate = (new DateTime())->add(new DateInterval("P3D"))->format("Y-m-d");

$query = "  SELECT
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                r.date,
                r.hour,
                r.minute,
                s.name AS service,
                a.displayName AS terapist,
                r.note,
                r.deleteHash
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE                
                (
                 r.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY) OR
                 r.date = DATE_ADD(CURDATE(), INTERVAL 10 DAY) OR
                 r.date = DATE_ADD(CURDATE(), INTERVAL 14 DAY)
                ) AND
                r.active = 1 AND
                r.email IS NOT NULL AND
                r.service = 12 AND
                r.qFinished IS NULL";
$stmt = $dbh->prepare($query);
$stmt->execute();
$resultsKontrol = $stmt->fetchAll(PDO::FETCH_OBJ);

if (count($resultsKontrol) != 0) {
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);
    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");

    foreach ($resultsKontrol as $result) {
        $mail->clearAllRecipients();
        $mail->addAddress($result->email, htmlspecialchars($result->name . " " . $result->surname));
        $mail->addBCC("rezervace@fyzioland.cz");

        $mail->Subject = "Fyzioland - připomenutí vyplnění senzorického dotazníku";

        $mail->Body = "<html><head></head><body style='padding: 10px;'>";
        $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
        $mail->Body .= "rádi bychom Vám připomněli vyplnění senzorického dotazníku, který nám slouží jako jeden z důležitých podkladů k vyhodnocení procesu rehabilitace v rámci kontrolního vyšetření. Na jeho vyplnění si, prosím, vyhraďte 30-45 minut. Dotazník je elektronický a zobrazí se Vám po kliknutí na následující odkaz: ";
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
    }

    echo "Odesláno celkem " . count($resultsKontrol) . " připomínek senzorických dotazníků ke kontrolnímu vyšetření.";    
   
} else {
    echo "Žádné e-maily k odeslání.";
}
    



