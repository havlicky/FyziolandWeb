<?php

require_once "../php/class.settings.php";
require_once "../php/class.messagebox.php";
require_once "checkLogin.php";

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

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

$query = "  SELECT
                COUNT(c.id) AS count
            FROM clients AS c
            WHERE
                AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') = (SELECT AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') FROM reservations WHERE id = :reservationId)";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":reservationId", $_GET["reservationId"], PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
if (intval($result->count) >= 1) {
    $messageBox->addText("Tento klient se již v databázi klientů nachází, nic nebylo zkopírováno.");
    $messageBox->setClass("alert-danger");

    $_SESSION["messageBox"] = $messageBox;
    header("Location: viewReservations?date=" . $_GET["backTo"]);
    die();
}


$query = "  INSERT INTO clients (
                id,
                name,
                surname,
                email,
                phone,
                lastEditDate
            )
            SELECT
                UUID(),
                r.name,
                r.surname,
                r.email,
                r.phone,
                NOW()
            FROM reservations AS r
            WHERE r.id = :reservationId";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":reservationId", $_GET["reservationId"], PDO::PARAM_INT);
if ($stmt->execute()) {
    $messageBox->addText("Klient byl úspěšně přenesen do databáze klientů.");
    $messageBox->setClass("alert-success");
} else {
    $messageBox->addText("Při kopírování klienta do databáze klientů nastala chyba.");
    $messageBox->setClass("alert-danger");
}
$_SESSION["messageBox"] = $messageBox;
header("Location: viewReservations?date=" . $_GET["backTo"]);