<?php

require_once "../php/class.settings.php";
require_once "../php/class.gosms.php";
require_once "../php/class.messagebox.php";
require_once "checkLogin.php";
include('phpqrcode/qrlib.php');

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

$query = "  
    SELECT                
        AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') as phone,
        CONCAT(AES_DECRYPT(name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(surname, '" . Settings::$mySqlAESpassword . "')) AS client,
        (SELECT textSMSwl FROM services WHERE id = :service) as service
    FROM clients             
    WHERE
        id = :client
    ";                
            
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

$smsText = 'Fyzioland: Nabídka uvolněného termínu na ' . $result->service . ' '.  
            (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " v " . $_POST["time"] .
            ' pro ' . $result->client .
            ' (terapeutka '. $_POST["therapist"] . '). Máte-li o termín zájem, klikněte na následující odkaz www.fyzioland.cz/rezervaceActionWLq?hsh=' . $_POST["hash"] .
            ' pro potvrzení zájmu o rezervaci (pokud bude termín ještě volný, potvrzení o rezervaci Vám přijde emailem). ' .
            $resultAdminUser->displayName;

if (empty($result->phone)) {
    $messageBox->addText("Klient nemá vyplněno telefonní číslo. Nelze zobrazit QR kód.");
    $messageBox->setClass("alert-danger");
    $_SESSION["messageBox"] = $messageBox;
    die();
}

$data = 'SMSTO:'. $result->phone.':'. $smsText;

ob_start();
    QRCode::png($data, null);
    $imageString = base64_encode( ob_get_contents() );
ob_end_clean();

echo json_encode(array(
    "img" => $imageString,
    "phone" => $result->phone,
    "client" => $result->client,
    "smsText" => $smsText	
));