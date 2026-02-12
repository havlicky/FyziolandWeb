<?php

require_once "../php/class.settings.php";
require_once "../php/class.gosms.php";
require_once "../php/class.messagebox.php";
require_once "checkLogin.php";

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

session_start();

if ($_POST["table"] === "reservations") {
    $query = "SELECT smsIdentification FROM reservations WHERE id = :id";
} else if ($_POST["table"] === "groupExcercisesParticipants") {
    $query = "SELECT smsIdentification FROM groupExcercisesParticipants WHERE id = :id";
}
$stmt = $dbh->prepare($query);
$stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$smsIdentification = $result->smsIdentification;

$sms = new Gosms();
$smsDetail = $sms->getMessageDetail($smsIdentification);


$status = $smsDetail->sendingInfo->status;
$isDelivered = $smsDetail->delivery->isDelivered;
$sentFinish = $smsDetail->sendingInfo->sentFinish;
$smsText = $smsDetail->message->fulltext;

switch ($status) {
    case "SENT":
        $status = "Odesláno";
        break;
    case "FAILED":
        $status = "Došlo k chybě při odesílání";
        break;
    case "IN_PROGRESS":
        $status = "Probíhá odesílání";
        break;
    default:
        $status = "Neznámý stav";
}

$isDelivered = ($isDelivered ? "Doručeno" : "Nedoručeno");

$sentFinish = (new DateTime($sentFinish))->format("j. n. Y H:i:s");

?>
<p>
    <b>Stav odeslání:</b><br>
    <?= $status ?>
</p>
<p>
    <b>Datum odeslání:</b><br>
    <?= $sentFinish ?>
</p>
<p>
    <b>Stav doručení:</b><br>
    <?= $isDelivered ?>
</p>
<p>
    <b>Text zprávy:</b><br>
    <?= htmlspecialchars($smsText) ?>
</p>