<?php

require_once "php/class.messagebox.php";
require_once "php/class.settings.php";
require_once "php/PHPMailer/PHPMailer.php";
$messageBox = new MessageBox();

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

// dotaz na rezervace dle emailové adresy

if ($_POST["emailforoverview"] != NULL) {
    
    if (empty($_POST["g-recaptcha-response"])) {
        $messageBox->addText("Prosíme vyplňte pole s Google Captchou, bez ní nelze pokračovat.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            "secret" => "6LcR5TQUAAAAAEcQa2c32DAkyG56D3vtMeX2gCc2",
            "response" => $_POST["g-recaptcha-response"]
        )));
        $responseRaw = curl_exec($ch);
        curl_close($ch);
        
        $response = json_decode($responseRaw);
        if ($response->success !== TRUE) {
            $messageBox->addText("Selhalo ověření Google Captcha.");
            $messageBox->setClass("alert-danger");

            $_SESSION["messageBox"] = $messageBox;
            header("Location: rezervace");
            die();
        }
    }
    
    $query = "  SELECT 	                
                    CONCAT(
                        DAY(r.date), 
                        '.', 
                        MONTH(r.date), 
                        '.', 
                        YEAR(r.date)) as date,               
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as timeFrom,
                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') as surname,
                    AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') as name,                
                    s.name as service,
                    a.displayName AS personnel,
                    AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') as phone,
                    AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') as email,
                    deleteHash
                FROM reservations r

                LEFT JOIN adminLogin a ON a.id = r.personnel
                LEFT JOIN services s ON s.id = r.service

                WHERE
                    r.date>= curdate() AND
                    r.active = 1 AND                
                    CONVERT(AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') USING 'utf8')  LIKE :email
                ORDER BY r.date, r.hour ASC                        
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":email", $_POST["emailforoverview"], PDO::PARAM_STR);    
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);


    //zaslání e-mailu klientovi s přehledem rezervací    
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
    $mail->AddAddress($_POST["emailforoverview"], $results[0]->name . " " . $results[0]->surname);
    $mail->AddBCC("rezervace@fyzioland.cz");                    

    $mail->Subject = "Přehled rezervací";
    $mail->Body = "Vážená klientko, vážený kliente,<br><br>";

    if (count($results) == 0 ) {
        $mail->Body .= "v současné chvíli neevidujeme žádné budoucí rezervace provedené na Vaši emailovou adresu.";
        $mail->Body .= "<br><br>";
    } else {
        $mail->Body .= "zasíláme vyžádaný přehled Vašich budoucích rezervací:<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 1000px;'>";
        foreach ($results as $result) {
            $odkazNaZruseniRezervace = "<a href='https://fyzioland.cz/cancelReservation.php?email=" . urlencode($result->email) . "&hsh=" . $result->deleteHash . "'>Odkaz na zrušení rezervace</a> ";
            $mail->Body .= "
                <tr>
                    <td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->date . "</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->timeFrom . "</td> 
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->name . "</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->surname . "</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->service . "</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->personnel . "</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->phone . "</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->email . "</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $odkazNaZruseniRezervace . "</td>
                    
                </tr>";
        }
        $mail->Body .= "</table>";
        $mail->Body .= "<br><br>";    
    }
    $mail->Body .= "Automatický email systému Fyzioland";
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

    $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");
    
    if ($mail->Send()) {
        $messageBox->addText("Přehled Vašich rezervací byl zaslán na Vámi uvedenou e-mailovou adresu.");
        $messageBox->setDelay(7000);        
    } else {
        $messageBox->addText("Přehled Vašich rezervací se nám nepodařilo odeslat.");
        $messageBox->setClass("alert-danger");
        $messageBox->setDelay(7000);           
    }      
            
} else {
    $messageBox->addText("Nebyla vyplněna e-mailová adresa. Přehled nemohl být zaslán.");
    $messageBox->setClass("alert-danger");
    $messageBox->setDelay(7000);        
}

$_SESSION["messageBox"] = $messageBox;
header("Location: rezervace");