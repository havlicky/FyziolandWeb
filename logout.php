<?php

require_once "php/class.settings.php";
session_start();

unset($_SESSION["loggedUserId"], $_SESSION["loggedUserName"], $_SESSION["loggedUserSurname"], $_SESSION["loggedUserEmail"], $_SESSION["loggedUserPhone"]);
$_SESSION["logout"] = true;

// úspěšné odhlášení
if (isset($_SESSION["redirectAfterLogin"])) {
    header("Location: " . $_SESSION["redirectAfterLogin"]);
    unset($_SESSION["redirectAfterLogin"]);
} else {
    header("Location: /");
}
die();