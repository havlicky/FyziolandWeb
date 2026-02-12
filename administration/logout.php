<?php

require_once "../php/class.messagebox.php";
/*
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$messageBox = new MessageBox();

unset($_SESSION["loggedUser"]);
 
 */
$messageBox = new MessageBox();
$messageBox->addText("Úspěšné odhlášení.");
$_SESSION["messageBox"] = $messageBox;
header("Location: login.php");
die();