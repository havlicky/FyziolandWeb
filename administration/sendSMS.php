<?php

require_once "../php/class.settings.php";
require_once "../php/class.gosms.php";
require_once "../php/class.messagebox.php";
require_once "checkLogin.php";

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

session_start();
$messageBox = new MessageBox();

function stripDiacritics($string) {
    $search = array("ě", "š", "č", "ř", "ž", "ý", "á", "í", "é", "ú", "ů", "ď", "ť", "ň", "Ě", "Š", "Č", "Ř", "Ž", "Ý", "Á", "Í", "É", "Ú", "Ů", "Ď", "Ť", "Ň");
    $replace = array("e", "s", "c", "r", "z", "y", "a", "i", "e", "u", "u", "d", "t", "n", "E", "S", "C", "R", "Z", "Y", "A", "I", "E", "U", "U", "D", "T", "N");
    
    return str_replace($search, $replace, $string);
}

$smsCount = 0;

if (empty($_POST["predefinedMessage"])) {
    $smsText = $_POST["customText"];
} else {
    $query = "SELECT text FROM smsText WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_POST["predefinedMessage"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    $smsText = $result->text;
}

if (empty($smsText)) {
    $messageBox->addText("Nebyl vyplněn žádný text SMS zprávy. Nic nebylo odesláno.");
    $messageBox->setClass("alert-danger");
    $_SESSION["messageBox"] = $messageBox;
    die();
}

if (count($_POST["recipients"]) === 0) {
    $messageBox->addText("Nebyli vybráni žádní příjemci. Nic nebylo odesláno.");
    $messageBox->setClass("alert-danger");
    $_SESSION["messageBox"] = $messageBox;
    die();
}

foreach ($_POST["recipients"] as $recipient) {
    if ($_POST["table"] === "reservations") {
        $query = "  SELECT
                        r.id,
                        AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                        r.date,
                        s.name AS title
                    FROM reservations AS r
                    LEFT JOIN services AS s ON s.id = r.service
                    WHERE
                        r.id = :id AND
                        r.active = 1 AND
                        r.smsIdentification IS NULL";
    } else if ($_POST["table"] === "groupExcercisesParticipants") {
        $query = "  SELECT
                        gep.id,
                        AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                        ge.date,
                        ge.title AS title
                    FROM groupExcercisesParticipants AS gep
                    LEFT JOIN clients AS c ON c.id = gep.client
                    LEFT JOIN groupExcercises AS ge ON ge.id = gep.groupExcercise
                    WHERE
                        gep.id = :id AND
                        gep.smsIdentification IS NULL";
    }
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $recipient, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($result === FALSE) {
        continue;
    }
    
    $replacements = array(
        "#datum#" => (new DateTime())->createFromFormat("Y-m-d", $result->date)->format("j.n."),
        "#nazev#" => stripDiacritics($result->title)
    );
    
    foreach ($replacements as $search => $replace) {
        $smsText = str_replace($search, $replace, $smsText);
    }

    $sms = new Gosms();
    $smsResponse = $sms->send($smsText, $result->phone);
    
    if ($_POST["table"] === "reservations") {
        $query = "UPDATE reservations SET smsIdentification = :smsIdentification WHERE id = :id";
    } else if ($_POST["table"] === "groupExcercisesParticipants") {
        $query = "UPDATE groupExcercisesParticipants SET smsIdentification = :smsIdentification WHERE id = :id";
    }
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":smsIdentification", $smsResponse->link, PDO::PARAM_STR);
    $stmt->bindParam(":id", $result->id, PDO::PARAM_INT);
    $stmt->execute();
    
    $smsCount++;
}

$messageBox->addText("Bylo úspěšně odesláno <b>{$smsCount}</b> SMS zpráv. Pro detail najeďte na ikonu telefonu u jednotlivých rezervací.");
$messageBox->setClass("alert-success");
$_SESSION["messageBox"] = $messageBox;