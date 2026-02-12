<?php

require_once "php/PHPMailer/PHPMailer.php";
require_once "php/class.messagebox.php";

session_start();
$messageBox = new MessageBox();

if (isset($_POST["odeslat"])) {
    // kontrola Google reCAPTCHA
    if (empty($_POST["g-recaptcha-response"])) {
        $messageBox->addText("Prosíme vyplňte pole s Google Captchou, bez ní nelze pokračovat.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: index");
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
            header("Location: index");
            die();
        }
    }
    
    // pole musí být povinně vyplněna
    if ( empty($_POST["jmeno"]) || empty($_POST["prijmeni"]) || empty($_POST["email"]) || empty($_POST["telefon"]) || empty($_POST["zamereni"]) || empty($_POST["poznamka"])) {
        $messageBox->addText("Pole <b>jméno</b>, <b>příjmení</b>, <b>e-mail</b>, <b>telefon</b>, <b>zaměření</b> a <b>poznámka</b> musí být povinně vyplněny. Vyplňte prosím formulář znovu, tentokrát s uvedením všech povinných polí.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: index");
        die();
    }
    
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("postmaster@fyzioland.cz", "WEB fyzioland.cz");
    //$mail->AddAddress("michal.oplt@hosolutions.cz", "Michal Oplt");
    $mail->AddAddress("jiri.havlicky@fyzioland.cz", "Jiří Havlický");

    $mail->Subject = "Zpráva z webového formuláře - Požadavek na spolupráci se sportovním klubem";
    $mail->Body = "Na webu fyzioland.cz byl vyplněn formulář na požadavek spolupráce se sportovním klubem s následujícími detaily:<br><br>";
    $mail->Body .= "<table>";
    $mail->Body .= "<tr><td style='font-weight: bold; padding-right: 10px'>Jméno a příjmení: </td><td>" . $_POST["jmeno"] . " " . $_POST["prijmeni"] . "</td></tr>";
    $mail->Body .= "<tr><td style='font-weight: bold; padding-right: 10px'>E-mail: </td><td><a href='mailto:" . $_POST["email"] . "'>" . $_POST["email"] . "</a></td></tr>";
    $mail->Body .= "<tr><td style='font-weight: bold; padding-right: 10px'>Telefonní kontakt: </td><td>" . $_POST["telefon"] . "</td></tr>";
    $mail->Body .= "<tr><td style='font-weight: bold; padding-right: 10px'>Zaměření sportovního klubu: </td><td>" . $_POST["zamereni"] . "</td></tr>";
    $mail->Body .= "<tr><td style='font-weight: bold; padding-right: 10px'>Poznámka: </td><td>" . $_POST["poznamka"] . "</td></tr>";
    $mail->Body .= "</table>";
    $mail->Body .= "<br>";
    $mail->Body .= "<small>Tento e-mail byl vygenerován automaticky.</small>";
    
    if ($mail->Send()) {
        $messageBox->addText("Děkujeme za Vaši zprávu, budeme Vás v&nbsp;krátké době kontaktovat.");
    } else {
        $messageBox->addText("Omlouváme se, ale nastala chyba při odesílání Vašeho dotazu. Zkuste to prosím za chvíli znovu.");
        $messageBox->setClass("alert-danger");
    }
}

$_SESSION["messageBox"] = $messageBox;
header("Location: index");

