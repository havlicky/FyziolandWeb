<?php

// include our OAuth2 Server object
require_once __DIR__.'/server.php';

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $server->getResponse()->send();
    die;
}

require_once "../php/class.settings.php";

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
                IFNULL(entryNote, '') as entryNote,
                IF(id = :idres, 'act', 'prev') as poradi
            FROM reservations
            WHERE id = :idres OR id = :idprevres                 
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":idres", $_POST["idres"], PDO::PARAM_INT);
$stmt->bindParam(":idprevres", $_POST["idprevres"], PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);
    
echo json_encode($results);
