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
    if (empty($_POST["email"])) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Všechna pole jsou povinná a musí být vyplněna.";
        
        header("Location: requestResetHesla");
        die();
    }
    
    $query = "SELECT id, recoveryHash FROM clients WHERE AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') = :email";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":email", trim(urldecode($_POST["email"])), \PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_OBJ);
    if ($result === FALSE) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Obnova hesla nemohla být provedena, protože Vámi zadaná e&#8209;mailová adresa není v systému evidována. Zaregistrovat se můžete <a href='registrace'>zde</a>";
        
        header("Location: requestResetHesla");
        die();
    }
    
    $recoveryHash = empty($result->recoveryHash) ? bin2hex(random_bytes(20)) : $result->recoveryHash;
    $query = "UPDATE clients SET recoveryHash = :recoveryHash WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":recoveryHash", $recoveryHash, PDO::PARAM_STR);
    $stmt->bindValue(":id", $result->id, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
        $mail->Subject = "Požadavek na obnovení hesla";
        
        $mail->Body = "<html><head></head><body style='padding: 10px;'>";
        $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
        $mail->Body .= "na webu fyzioland.cz byl zaregistrován požadavek na obnovení přihlašovacího hesla do Vašeho účtu.<br><br>";
        $mail->Body .= "Proces obnovení hesla dokončete prosím kliknutím na následující <a href='https://fyzioland.cz/resetHesla.php?email=" . urlencode($_POST["email"]) . "&hsh=" . $recoveryHash . "'>odkaz</a>.";
        $mail->Body .= "<br><br>";
        $mail->Body .= "V případě, že jste tento požadavek na webu nezadali Vy, tuto zprávu prosím ignorujte a Vaše přihlašovací údaje zůstanou nezměněny.";
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
$_SESSION["registerSuccess"] .= "Na Vámi zadanou e&#8209;mailovou adresu byl odeslán e&#8209;mail s instrukcemi pro obnovení hesla. Postupujte prosím podle pokynů v&nbsp;zaslané zprávě.";

header("Location: requestResetHesla");
die();



