<?php

require_once "../php/class.settings.php";
require_once "../php/class.gosms.php";
require_once "../php/class.messagebox.php";
require_once "checkLogin.php";
include('phpqrcode/qrlib.php');

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

session_start();
$messageBox = new MessageBox();

function stripDiacritics($string) {
    $search = array("ó", "ě", "š", "č", "ř", "ž", "ý", "á", "í", "é", "ú", "ů", "ď", "ť", "ň", "Ě", "Š", "Č", "Ř", "Ž", "Ý", "Á", "Í", "É", "Ú", "Ů", "Ď", "Ť", "Ň");
    $replace = array("o", "e", "s", "c", "r", "z", "y", "a", "i", "e", "u", "u", "d", "t", "n", "E", "S", "C", "R", "Z", "Y", "A", "I", "E", "U", "U", "D", "T", "N");
    
    return str_replace($search, $replace, $string);
}

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
    $messageBox->addText("Nebyl vyplněn žádný text SMS zprávy. Nelze zobrazit QR kód.");
    $messageBox->setClass("alert-danger");
    $_SESSION["messageBox"] = $messageBox;
    die();
}

$query = "   SELECT
                CONCAT (AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'), ', ', AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')) AS client,
                AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "') as phone,                
                ge.date,
                ge.title,
                CONCAT (HOUR(ge.timeFrom), ':', LPAD(MINUTE(ge.timeFrom), 2, '0')) as timeFrom                        
            FROM groupExcercisesParticipants gep
            LEFT JOIN clients c ON c.id = gep.client
            LEFT JOIN groupExcercises ge ON ge.id = gep.groupExcercise
            WHERE
                gep.id=:id";                
            
$stmt = $dbh->prepare($query);
$stmt->bindParam(":id", $_POST["id"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

if (empty($result->phone)) {
    $messageBox->addText("Klient nemá vyplněno telefonní číslo. Nelze zobrazit QR kód.");
    $messageBox->setClass("alert-danger");
    $_SESSION["messageBox"] = $messageBox;
    die();
}
    
$replacements = array(
    "#datum#" => (new DateTime())->createFromFormat("Y-m-d", $result->date)->format("j.n."),
    "#nazev#" => stripDiacritics($result->title),
    "#cas#" => $result->timeFrom
);

foreach ($replacements as $search => $replace) {
    $smsText = str_replace($search, $replace, $smsText);
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


$_SESSION["messageBox"] = $messageBox;