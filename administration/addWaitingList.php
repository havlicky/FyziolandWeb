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

if (isset($_POST["submit"])) {    
    $query = "  INSERT INTO waitinglist (
                    client,
                    name,
                    surname,
                    email,
                    phone,
                    date,                    
                    personnel,
                    service,
                    note,
                    validto,
                    hash,
                    creationTimestamp
                ) VALUES (
                    :client,
                    AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                    AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                    AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                    AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                    NOW(),                    
                    :personnel,
                    :service,
                    :note,
                    :validto,
                    :hash,
                    :creationTimestamp                                     
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":client", empty($_POST["client"]) ? NULL : $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
    $stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
    $stmt->bindValue(":email", empty($_POST["email"]) ? NULL : $_POST["email"], PDO::PARAM_STR);
    $stmt->bindValue(":phone", empty($_POST["phone"]) ? NULL : $_POST["phone"], PDO::PARAM_STR);    
    $stmt->bindParam(":personnel", $_POST["terapist"], PDO::PARAM_INT);
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
    $stmt->bindValue(":note", empty($_POST["note"]) ? NULL : $_POST["note"], PDO::PARAM_STR);    
    $stmt->bindParam(":validto", $_POST["validto-formatted"], PDO::PARAM_STR);    
    $hash = bin2hex(openssl_random_pseudo_bytes(20));
    $stmt->bindParam(":hash", $hash, PDO::PARAM_STR);
    $stmt->bindValue(":creationTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);    
    
    if ($stmt->execute()) {                                        
        //dotažení názvu služby a jméno terapeuta pro email 
        $query = "  SELECT
                        (SELECT displayName FROM adminLogin WHERE id = :terapist) AS terapist,                        
                        (SELECT name FROM services WHERE id = :service) AS service
                 ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":terapist", $_POST["terapist"], PDO::PARAM_INT);
        $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
        $stmt->execute();
        $resultDetail = $stmt->fetch(PDO::FETCH_OBJ);
        
        //email o vytvořeném zápisu na wating list na rezervace@fyzioland.cz (včetně telefonu a emailové adresy
        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
        $mail->AddAddress("rezervace@fyzioland.cz", "Fyzioland - rezervace");
        $mail->Subject = "Fyzioland - zařazení na čekací listinu";

        $mail->Body = "<html><head></head><body style='padding: 10px;'>";
        $mail->Body .= "Vážený kolego,<br><br>";
        $mail->Body .= "rádi bychom Vás informovali, že jsme zažadili následující požadavek na čekací listinu klientů:<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["name"] . " " . $_POST["surname"] . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Telefonní kontakt</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["phone"] . "</td></tr>";

        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->terapist) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->service) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($_POST["note"]) . "</td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br>";
        
        $mail->Body .= "<br><br>";
        $mail->Body .= "Automatický email ze systému Fyzioland.<br><br>";
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
        $mail->Send();
                      
        $messageBox->addText("Požadavek byl úspěšně zapsán na čekací listinu");        
    } else {
        $messageBox->addText("Požadavek se nepodařilo vytvořit. Zkuste to prosím znovu.");
        $messageBox->setClass("alert-danger");
    }
        
    if (empty($_POST["client"])) {
        $query = "  INSERT INTO clients (
                        id,
                        name,
                        surname,
                        email,
                        phone,
                        lastEditDate
                    ) VALUES (
                        UUID(),
                        AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                        NOW()
                    )";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
        $stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
        $stmt->bindValue(":email", empty($_POST["email"]) ? NULL : $_POST["email"], PDO::PARAM_STR);
        $stmt->bindValue(":phone", empty($_POST["phone"]) ? NULL : $_POST["phone"], PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $query = "  UPDATE clients
                    SET
                        name = AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                        surname = AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                        email = AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                        phone = AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                        lastEditDate = NOW()
                    WHERE id = :id";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
        $stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
        $stmt->bindValue(":email", empty($_POST["email"]) ? NULL : $_POST["email"], PDO::PARAM_STR);
        $stmt->bindValue(":phone", empty($_POST["phone"]) ? NULL : $_POST["phone"], PDO::PARAM_STR);
        $stmt->bindParam(":id", $_POST["client"], PDO::PARAM_STR);
        $stmt->execute();
                        
    }   
}
$_SESSION["messageBox"] = $messageBox;
if (isset($_POST["resid"])) {
    // znamená to, že je požadavek na waiting list ze zrušené rezervace

} else {       
    header("Location: viewWaitingList.php");
}