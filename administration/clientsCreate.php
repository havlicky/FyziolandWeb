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

$query = "  INSERT INTO clients (
                id,
                name,
                surname,
                email,
                phone,
                mailing,
                lastEditDate
            ) VALUES (
                UUID(),
                AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                :mailing,
                NOW()
            )";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
$stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
$stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
$stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);
$stmt->bindParam(":mailing", $_POST["send_email"], PDO::PARAM_STR);
$stmt->execute();

$messageBox->addText("Klient byl úspěšně vytvořen.");
$_SESSION["messageBox"] = $messageBox;

header("Location: viewClients");