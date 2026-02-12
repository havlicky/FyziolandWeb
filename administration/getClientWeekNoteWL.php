<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "SELECT note, noWL FROM weeknotes WHERE client = :client AND firstDayOfWeek=:lastmonday";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->bindParam(":lastmonday", $_POST["lastmonday"], PDO::PARAM_STR);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_OBJ);

echo json_encode($result);
