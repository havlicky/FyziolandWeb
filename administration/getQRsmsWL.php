<?php

require_once "../php/class.settings.php";
require_once "../php/class.gosms.php";
require_once "../php/class.messagebox.php";
require_once "checkLogin.php";
include('phpqrcode/qrlib.php');

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}
if ($_POST["isergo"] == 1) {
    $smsText = 'Vážená klientko, vážený kliente, dovolujeme si Vám nabídnout následující uvolněný termín na ergoterapii ' .  (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["time"] .' u kolegyně: '. $_POST["therapist"] . '. Máte-li o termín zájem, klikněte na následující odkaz www.fyzioland.cz/rezervaceActionWLq?hsh=' . $_POST["hash"] .' pro automatické vytvoření rezervace (potvrzení o rezervaci Vám následně přijde emailem) nebo nás v krátkém čase kontaktujte. Děkujeme. ' .  $resultAdminUser->displayName . ', Fyzioland';//$smsText = 'Vazena klientko, vazeny kliente, dovolujeme si Vám nabidnout nasledujici uvolneny termin na ergoterapii ' .  (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["time"] .' u kolegyne: '. $_POST["therapist"] . '. Mate-li o termin zajem, kliknete na nasledujici odkaz www.fyzioland.cz/rezervaceActionWL?hsh=' . $_POST["hash"] .' pro automaticke vytvoreni rezervace (potvrzeni o rezervaci Vam nasledne prijde emailem) nebo nas v kratke dobe kontaktujte. Dekujeme. Jiri Havlicky, Fyzioland';
}

if ($_POST["isfyzio"] == 1) {
    $smsText = 'Vážená klientko, vážený kliente, dovolujeme si Vám nabídnout následující uvolněný termín na fyzioterapii ' .  (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["time"] .' u kolegyně: '. $_POST["therapist"] . '. Máte-li o termín zájem, klikněte na následující odkaz www.fyzioland.cz/rezervaceActionWLq?hsh=' . $_POST["hash"] .' pro automatické vytvoření rezervace (potvrzení o rezervaci Vám následně přijde emailem) nebo nás v krátkém čase kontaktujte. Děkujeme. ' .  $resultAdminUser->displayName . ', Fyzioland';//$smsText = 'Vazena klientko, vazeny kliente, dovolujeme si Vám nabidnout nasledujici uvolneny termin na ergoterapii ' .  (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["time"] .' u kolegyne: '. $_POST["therapist"] . '. Mate-li o termin zajem, kliknete na nasledujici odkaz www.fyzioland.cz/rezervaceActionWL?hsh=' . $_POST["hash"] .' pro automaticke vytvoreni rezervace (potvrzeni o rezervaci Vam nasledne prijde emailem) nebo nas v kratke dobe kontaktujte. Dekujeme. Jiri Havlicky, Fyzioland';
}



$query = "  SELECT                
                AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') as phone,
                CONCAT (AES_DECRYPT(surname, '" . Settings::$mySqlAESpassword . "'), ', ', AES_DECRYPT(name, '" . Settings::$mySqlAESpassword . "')) AS client
            FROM clients             
            WHERE
                id=:client
                ";                
            
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

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