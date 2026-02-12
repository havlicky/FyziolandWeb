<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
$messageBox = new MessageBox();

session_start();

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$query = "DELETE FROM emails WHERE id = :id";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":id", $_GET["id"], \PDO::PARAM_INT);
$stmt->execute();

$query = "DELETE FROM emailsRecipients WHERE emailId = :emailId";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":emailId", $_GET["id"], \PDO::PARAM_STR);
$stmt->execute();

$messageBox->addText("E-mail byl úspěšně odstraněn.");

$_SESSION["messageBox"] = $messageBox;
header("Location: emailsList.php");
