<?php

require_once "../php/class.settings.php";
require_once "../php/class.gosms.php";
require_once "../php/PHPMailer/PHPMailer.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$date = (new DateTime())->add(new DateInterval("P1D"));
$date = new DateTime("2017-10-27");

$query = "  SELECT
                id,
                AES_DECRYPT(name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                date,
                hour,
                minute,
                alert,
                note,
                creationTimestamp
            FROM reservations
            WHERE
                active = 1 AND
                date = :date
            ORDER BY date, hour, minute";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":date", $date->format("Y-m-d"), PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

/*
  echo "<pre>";
  print_r($results);
  echo "</pre>";
 */

if (count($results) === 0) {
    die();
}

$mailBodyTemplate = file_get_contents("reminderTemplate.html");
$smsBodyTemplate = "Dobry den, radi bychom Vam pripomneli Vasi rezervaci na zitra ##datum## v ##cas##. Tesime se na Vas! Fyzioland, Kasovicka 1608/4, Praha-Uhrineves. Na tuto zpravu prosim neodpovidejte.";

foreach ($results as $result) {
    $hodina = $result->hour;
    $minuta = str_pad($result->minute, 2, "0", STR_PAD_LEFT);
    //$minuta = "00";
    
    // odesílání e-mailu
    if (in_array($result->alert, array("email", "both")) && !empty($result->email)) {
        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");

        $mail->Subject = "Fyzioland - připomenutí rezervace";

        $mail->Body = "<html><head></head><body style='padding: 10px;'>";
        $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
        $mail->Body .= "jsme rádi, že využíváte našich služeb a dovolujeme si Vám připomenout Vaši zítřejší rezervaci:<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["name"] . " " . $_POST["surname"] . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Telefonní kontakt</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["phone"] . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["hour"] . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT) . " - " . (intval($_POST["hour"]) + 1) . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($_POST["note"]) . "</td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br><br>";
        $mail->Body .= "Těšíme se na Vaši návštěvu.<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse;'><tr>";
        $mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
        $mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
        $mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
        $mail->Body .= "Kašovická 1608/4, 104 00 Praha 22 - Uhříněves<br>";
        $mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
        $mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
        $mail->Body .= "</td>";
        $mail->Body .= "</tr></table>";
        $mail->Body .= "</body></html>";

        $mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");

        $mail->AddAddress("rezervace@fyzioland.cz", "Fyzioland - rezervace");
        $mail->Send();
        
        
        
        
        
        $customizedMailBodyTemplate = str_replace(
            array("##datum##", "##cas##"), 
            array($date->format("j.n.Y"), $hodina . ":" . $minuta), 
            $mailBodyTemplate
        );

        echo $customizedMailBodyTemplate;
        echo "<br><br>";
        echo "<hr>";
    }
    
    /*
    // odesílání SMS
    if (in_array($result->alert, array("sms", "both")) && !empty($result->phone)) {
        $customizedSmsBodyTemplate = str_replace(
            array("##datum##", "##cas##"), 
            array($date->format("j.n.Y"), $hodina . ":" . $minuta), 
            $smsBodyTemplate
        );
        
        $goSms = new Gosms();
        $response = $goSms->send($customizedSmsBodyTemplate, $result->phone, TRUE);
        
        echo "<pre>";
        print_r(json_decode($response));
        echo "</pre>";
    }
    */
}