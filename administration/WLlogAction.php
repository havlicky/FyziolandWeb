<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";
include "header.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
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
if ($_POST["type"] === "phoneCall") {
    $query = "INSERT INTO logWL (client, WLdate, WLtime, therapist, type, actionTimestamp) VALUES (:client, :WLdate, :WLtime, :therapist, 'Tel', NOW())";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":WLdate", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":WLtime", $_POST["time"], PDO::PARAM_STR);    
    $stmt->bindParam(":therapist", $_POST["therapistId"], PDO::PARAM_STR);    
    $stmt->execute();
} else if ($_POST["type"] === "phoneCallNotSuccess") {
    $query = "INSERT INTO logWL (client, WLdate, WLtime, therapist, type,  actionTimestamp, note) VALUES (:client, :WLdate, :WLtime, :therapist, 'Tel', NOW(), 'Nedovolal jsem se')";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":WLdate", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":WLtime", $_POST["time"], PDO::PARAM_STR); 
    $stmt->bindParam(":therapist", $_POST["therapistId"], PDO::PARAM_STR);    
    $stmt->execute();
} else if ($_POST["type"] === "sms") {        
    $query = "INSERT INTO logWL (client, WLdate, WLtime, therapist, type, actionTimestamp, hash, service, message, creationTimestamp, user) VALUES (:client, :WLdate, :WLtime, :therapist, 'SMS', IF(:QR = 'Y', NOW(), NULL), :hash, :service, :message, NOW(), :user)";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":WLdate", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":WLtime", $_POST["time"], PDO::PARAM_STR);  
    $stmt->bindParam(":therapist", $_POST["therapistId"], PDO::PARAM_STR);
    $stmt->bindParam(":hash", $_POST["hash"], PDO::PARAM_STR);    
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);    
    $stmt->bindParam(":message", $_POST["message"], PDO::PARAM_STR);    
    $stmt->bindParam(":QR", $_POST["QR"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);    
    $stmt->execute();
} else if ($_POST["type"] === "Email_1") {
    //Email_1 znamená email na základě zařazení klienta na čekací listinu        
    
    $query = "INSERT INTO logWL (client, WLdate, WLtime, therapist, type, actionTimestamp, hash) VALUES (:client, :WLdate, :WLtime, :therapist, 'Email-WL', NOW(), :hash)";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":WLdate", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":WLtime", $_POST["time"], PDO::PARAM_STR);    
    $stmt->bindParam(":therapist", $_POST["therapistId"], PDO::PARAM_STR);
    $hash = bin2hex(openssl_random_pseudo_bytes(20));
    $stmt->bindParam(":hash", $hash, PDO::PARAM_STR);    
    $stmt->execute();    
    
    //zjištění emailu a jména klienta
    $query = "  SELECT
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email
            FROM clients 
            WHERE
                client=:client ";
    $stmt = $dbh->prepare($query);                                            
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);        
    $stmt->execute();
    $client = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    //email o nabídce rezervace klientovi a na email rezervace@fyzioland.cz
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");

    $mail->Subject = "Fyzioland - nabídka uvolněného termínu ergoterapie";

    $mail->Body = "<html><head></head><body style='padding: 10px;'>";
    $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
    $mail->Body .= "rádi bychom Vám nabídli možnost využít uvolněný termín na ergoterapii na základě Vaší žádosti o zařazení na čekací listinu.<br><br>";
    
    $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";        
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["time"] . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($_POST["therapist"]) . "</b></td></tr>";        
    $mail->Body .= "</table>";
    $mail->Body .= "<br>"; 
    
    $mail->Body .= "<b>Máte-li zájem využít výše uvedený termín, klikněte zde: <a href='https://fyzioland.cz/rezervaceActionWL?hsh=" . $hash . "'>Mám zájem o termín</a></b>.<br><br>";
    $mail->Body .= "Po kliknutí na odkaz náš systém ověří, zda je termín stále volný. V případě, že ano, pošleme Vám obratem potvrzení Vaší rezervace emailem. ";
    $mail->Body .= "Případně nás také můžete kontaktovat obratem na našem tel. čísle 775 910 749 nebo odpovědět na tento email.</b><br><br>";            
    
    $mail->Body .= "Nepřejete-li si dále dostávat tyto informační emaily, kontaktujte nás prosím.<br><br>";
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
    //$mail->AddAddress($client->email, $client->name . " " . $client->surname);

    $mail->Send();
    
    
    
} else if ($_POST["type"] === "Email_2") {
    //Email_2 znamená email na základě obvyklých časů (nikoliv WL)
    //zjištění emailu a jména klienta
    $query = "  SELECT
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email
            FROM clients 
            WHERE
                client=:client ";
    $stmt = $dbh->prepare($query);                                            
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);        
    $stmt->execute();
    $client = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    //email o nabídce rezervace klientovi a na email rezervace@fyzioland.cz
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");

    $mail->Subject = "Fyzioland - nabídka uvolněného termínu ergoterapie";

    $mail->Body = "<html><head></head><body style='padding: 10px;'>";
    $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
    $mail->Body .= "rádi bychom Vám nabídli možnost využít uvolněný termín na ergoterapii.<br><br>";
    $mail->Body .= "<b>Máte-li zájem využít níže uvedený termín, kontaktujte nás prosím bezdokladně na tel. čísle 775 910 749 nebo odpovězte na tento email.</b><br><br>";
    $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";        
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["time"] . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($_POST["therapist"]) . "</b></td></tr>";        
    $mail->Body .= "</table>";
    $mail->Body .= "<br>";        
    $mail->Body .= "Nepřejete-li si dále dostávat tyto informační emaily, kontaktujte nás prosím.<br><br>";
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
    //$mail->AddAddress($client->email, $client->name . " " . $client->surname);

    $mail->Send();
    
    $query = "INSERT INTO logWL (client, WLdate, WLtime, therapist, type, actionTimestamp, hash) VALUES (:client, :WLdate, :WLtime, :therapist, 'Email-WL', NOW(), :hash)";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":WLdate", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":WLtime", $_POST["time"], PDO::PARAM_STR);    
    $stmt->bindParam(":therapist", $_POST["therapistId"], PDO::PARAM_STR);
    $hash = bin2hex(openssl_random_pseudo_bytes(20));
    $stmt->bindParam(":hash", $hash, PDO::PARAM_STR);        
    $stmt->execute();           
   
}