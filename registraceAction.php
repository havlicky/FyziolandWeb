<?php

require_once "php/class.settings.php";
require_once "php/PHPMailer/PHPMailer.php";
require_once "php/PHPMailer/Exception.php";
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
    $query = "SELECT id, CASE WHEN IFNULL(password, '') = '' THEN 0 ELSE 1 END AS passwordSet FROM clients WHERE AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') = :email";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":email", trim(urldecode($_POST["email"])), \PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_OBJ);
    if ($result !== FALSE && $result->passwordSet === "1") {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Tento e-mail je již používaný, zvolte prosím jiný.";
        
        header("Location: registrace");
        die();
    }
    $novyKlient = ($result === FALSE ? TRUE : FALSE);
    
    if (!isset($_POST["personalDetailsAgreement"])) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Je třeba vyjádřit souhlas se zpracováním osobních údajů.";

        header("Location: registrace");
        die();
    }
    
    if ($novyKlient) {
        if (empty($_POST["email"]) || empty($_POST["password"]) || empty($_POST["passwordAgain"]) || empty($_POST["name"]) || empty($_POST["surname"]) || empty($_POST["phoneNumber"])) {
            $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
            $_SESSION["registerError"] .= "Všechna pole jsou povinná a musí být vyplněna.";

            header("Location: registrace");
            die();
        }

        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
            $_SESSION["registerError"] .= "Zadaná e-mailová adresa není platná, zadejte prosím jinou.";

            header("Location: registrace");
            die();
        }
    } else {
        if (empty($_POST["email"]) || empty($_POST["password"]) || empty($_POST["passwordAgain"])) {
            $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
            $_SESSION["registerError"] .= "Všechna pole jsou povinná a musí být vyplněna.";

            header("Location: registrace");
            die();
        }
    }
    
    if ($_POST["password"] !== $_POST["passwordAgain"]) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Zadané heslo a potvrzení hesla se neshodují.";
        
        header("Location: registrace");
        die();
    }
    
    $activationHash = bin2hex(random_bytes(20));
    if ($novyKlient) {
        $query = "  INSERT INTO clients (
                        id,
                        name,
                        surname,
                        email,
                        password,
                        phone,
                        activationHash,
                        lastEditDate,
                        mailing
                    ) VALUES (
                        UUID(),
                        AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                        :password,
                        AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                        :activationHash,
                        NOW(),
                        :mailing
                    )";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":name", trim($_POST["name"]), PDO::PARAM_STR);
        $stmt->bindValue(":surname", trim($_POST["surname"]), PDO::PARAM_STR);
        $stmt->bindValue(":email", empty($_POST["email"]) ? NULL : trim($_POST["email"]), PDO::PARAM_STR);
        $stmt->bindValue("password", password_hash($_POST["password"], PASSWORD_DEFAULT), PDO::PARAM_STR);
        $stmt->bindValue(":phone", empty($_POST["phoneNumber"]) ? NULL : trim($_POST["phoneNumber"]), PDO::PARAM_STR);
        $stmt->bindValue(":activationHash", $activationHash, PDO::PARAM_STR);
        $stmt->bindValue(":mailing", isset($_POST["emailingAgreement"]) ? 1 : 0, PDO::PARAM_INT);
    } else {
        $query = "  UPDATE clients SET
                        password = :password,
                        activationHash = :activationHash,
                        lastEditDate = NOW(),
                        mailing = :mailing
                    WHERE id = :id";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue("password", password_hash($_POST["password"], PASSWORD_DEFAULT), PDO::PARAM_STR);
        $stmt->bindValue(":activationHash", $activationHash, PDO::PARAM_STR);
        $stmt->bindValue(":mailing", isset($_POST["emailingAgreement"]) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(":id", $result->id, PDO::PARAM_STR);
    }
    if ($stmt->execute()) {
        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
        $mail->Subject = "Potvrzení registrace";
        
        $mail->Body = "<html><head></head><body style='padding: 10px;'>";
        $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
        $mail->Body .= "děkujeme Vám za Vaši registraci ve společnosti Fyzioland.<br><br>";
        $mail->Body .= "Registraci dokončete prosím kliknutím na následující aktivační <a href='https://fyzioland.cz/registraceActivate.php?email=" . urlencode($_POST["email"]) . "&hsh=" . $activationHash . "'>odkaz</a>.";
        $mail->Body .= "<br><br>";
        $mail->Body .= "Děkujeme Vám a&nbsp;těšíme se na Vaši návštěvu.<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse; margin-top: 15px;'><tr>";
        $mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
        $mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
        $mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
        $mail->Body .= "Kašovická 1608/4, 104 00 Praha 10 - Uhříněves<br>";
        $mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
        $mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
        $mail->Body .= "</td>";
        $mail->Body .= "</tr></table>";
        $mail->Body .= "</body></html>";

        $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");

        $mail->AddAddress($_POST["email"]);
        $mail->Send();
    }
}

$_SESSION["registerSuccess"] = "<span class='glyphicon glyphicon-ok' aria-hidden='true' style='margin-right: 10px;'></span>";
$_SESSION["registerSuccess"] .= "Děkujeme, Vaše registrace byla úspěšně dokončena, nyní je třeba pouze Váš nový profil aktivovat.<br>Na Vámi zadanou e&#8209;mailovou adresu byl odeslán aktivační e&#8209;mail. Postupujte prosím podle pokynů v&nbsp;zaslané zprávě.";

header("Location: registraceActivate");
die();



