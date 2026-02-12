<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "SELECT noteWL FROM clients WHERE id = :client";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_OBJ);

echo json_encode($result);
