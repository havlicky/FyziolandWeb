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


if (isset($_POST["submit"])) {
    // kontrola Google reCAPTCHA
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
    
    // povinný souhlas se zpracováním osobních údajů
    if ($_POST["personalDetailsAgreement"] !== "1") {
        $messageBox->addText("Pro založení rezervace je třeba vyjádřit souhlas se zpracováním osobních údajů.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    // pole musí být povinně vyplněna
    if ( empty($_POST["name"]) || empty($_POST["surname"]) || empty($_POST["email"])  || empty($_POST["phone"])) {
        $messageBox->addText("Pole <b>jméno</b>, <b>příjmení</b> a <b>e-mail</b> musí být povinně vyplněny. Vytvořte prosím rezervaci znovu, tentokrát s vyplněním všech povinných polí.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    // datum rezervace nesmí být svátek nebo sobota nebo neděle
    $date = (new DateTime())->createFromFormat("Y-m-d", $_POST["date"]);
    $hodina = intval($_POST["hour"]);
    
    $query = "  SELECT
                    COUNT(*) AS count
                FROM holidays
                WHERE
                    (
                        month = :month1 AND
                        day = :day1 AND
                        year IS NULL
                    )
                    OR
                    (
                        month = :month2 AND
                        day = :day2 AND
                        year = :year
                    )";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":month1", $date->format("n"), PDO::PARAM_INT);
    $stmt->bindValue(":day1", $date->format("j"), PDO::PARAM_INT);
    $stmt->bindValue(":month2", $date->format("n"), PDO::PARAM_INT);
    $stmt->bindValue(":day2", $date->format("j"), PDO::PARAM_INT);
    $stmt->bindValue(":year", $date->format("Y"), PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (intval($result["count"]) === 1 || intval($date->format("N")) === 6 || intval($date->format("N")) === 7) {
        $messageBox->addText("Rezervace není možné vytvářet na nepracovní dny. Vytvořte prosím novou rezervaci na pracovní den.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    // kontrola, že na zvolený čas ještě rezervace žádná neexistuje
    $query = "  SELECT
                    COUNT(*) AS count
                FROM reservations
                WHERE
                    date = :date AND
                    hour = :hour AND
                    minute = :minute AND
                    active = 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_INT);
    $stmt->bindParam(":minute", $_POST["minute"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (intval($result["count"]) === 1) {
        $messageBox->addText("Na tento čas již existuje jiná rezervace. Vyberte prosím jiný termín.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    // rezervace lze provádět pouze na budoucí datum, nejdéle však do konce následujícího týdne
    $today = new DateTime();
    $differenceToFridayThisWeek = 5 - intval($today->format("N"));
    if ($differenceToFridayThisWeek >= 0) {
        $fridayThisWeek = (new DateTime($today->format("Y-m-d")))->add(new DateInterval("P" . $differenceToFridayThisWeek . "D"));
    } else {
        $fridayThisWeek = (new DateTime($today->format("Y-m-d")))->sub(new DateInterval("P" . abs($differenceToFridayThisWeek) . "D"));
    }
    $fridayNextWeek = (new DateTime($fridayThisWeek->format("Y-m-d")))->add(new DateInterval("P28D"));
    
    $aktualniHodina = intval($today->format("H"));
    $hodinaOdKtereLzeRezervovat = $aktualniHodina + 3;

    if (
            $date->format("Y-m-d") < $today->format("Y-m-d") ||
            $date->format("Y-m-d") > $fridayNextWeek->format("Y-m-d") ||
            ($date->format("Y-m-d") === $today->format("Y-m-d") && $hodina < $hodinaOdKtereLzeRezervovat)
    ) {
        
        $messageBox->addText("Rezervace je možné provádět pouze od zítřejšího dne a na následující 4 pracovní týdny. Vyberte prosím jiný den.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    // pokud kontroly projdou, může se rezervace uložit
    $query = "  INSERT INTO reservations (
                    name,
                    surname,
                    email,
                    phone,
                    date,
                    hour,
                    minute,
                    note,
                    alert,
                    personalDetailsAgreement,
                    deleteHash,
                    creationTimestamp,
                    IPaddress
                ) VALUES (
                    AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                    AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                    AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                    AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                    :date,
                    :hour,
                    :minute,
                    :note,
                    :alert,
                    :personalDetailsAgreement,
                    :deleteHash,
                    :creationTimestamp,
                    AES_ENCRYPT(:IPaddress, '" . Settings::$mySqlAESpassword . "')
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
    $stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
    $stmt->bindValue(":phone", empty($_POST["phone"]) ? NULL : $_POST["phone"], PDO::PARAM_STR);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_INT);
    $stmt->bindParam(":minute", $_POST["minute"], PDO::PARAM_INT);
    $stmt->bindValue(":note", empty($_POST["note"]) ? NULL : $_POST["note"], PDO::PARAM_STR);
    $stmt->bindParam(":alert", $_POST["alert-type"], PDO::PARAM_STR);
    $stmt->bindValue(":personalDetailsAgreement", 1, PDO::PARAM_INT);

    $deleteHash = bin2hex(openssl_random_pseudo_bytes(20));
    $stmt->bindParam(":deleteHash", $deleteHash, PDO::PARAM_STR);

    $stmt->bindValue(":creationTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $stmt->bindParam(":IPaddress", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);

    if ($stmt->execute()) {
        $messageBox->addText("Vaše rezervace byla v pořádku vytvořena. Současně byl odeslán potvrzovací e-mail na Vámi uvedenou e-mailovou adresu.");
        
        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);
        
        $mail->SetFrom("jitka.havlicka@fyzioland.cz", "Jitka Havlická, Fyzioland s.r.o.");
        $mail->AddAddress($_POST["email"], $_POST["name"] . " " . $_POST["surname"]);
    
        $mail->Subject = "Potvrzení rezervace ze serveru fyzioland.cz";
        $mail->Body = "Vážená klientko, vážený kliente,<br>";
        $mail->Body .= "potvrzujeme vytvoření rezervace s následujícími údaji uvedenými z Vaší strany ve webovém formuláři:<br><br>";
        $mail->Body .= "<table>";
        $mail->Body .= "<tr><td>Jméno a příjmení: </td><td>" . $_POST["name"] . " " . $_POST["surname"] . "</td></tr>";
        $mail->Body .= "<tr><td>Telefonní kontakt: </td><td>" . $_POST["phone"] . "</td></tr>";
        $mail->Body .= "<tr><td>Datum a čas rezervace: </td><td><b>" . (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["hour"] . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
        $mail->Body .= "<tr><td>Vaše poznámka k rezervaci: </td><td>" . $_POST["note"] . "</td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br>";
        $mail->Body .= "V případě, že je třeba rezervaci z vážných důvodů zrušit, můžete tak učinit maximálně 24 hodin předem, a to kliknutím na následující <a href='https://fyzioland.cz/rezervace/cancelReservation.php?email=" . urlencode($_POST["email"]) . "&hsh=" . $deleteHash . "'>odkaz</a>.";
        $mail->Body .= "<br><br>";
        $mail->Body .= "Děkujeme Vám za Vaši rezervaci a těšíme se na setkání.<br><br>";
        $mail->Body .= "Jitka Havlická<br>";
        $mail->Body .= "Fyzioland s.r.o.<br>";
        $mail->Body .= "Olivova 2585/45<br>251 01 Říčany<br>Tel.: +420 775 910 749";
        
        $mail->Send();
        
    } else {
        $messageBox->addText("Rezervaci se nepodařilo vytvořit. Zkuste to prosím znovu");
        $messageBox->setClass("alert-danger");
    }
}

$_SESSION["messageBox"] = $messageBox;
header("Location: rezervace");