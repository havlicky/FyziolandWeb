<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

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
                s.id,
                s.name
            FROM services AS s                                                                    
            WHERE s.id IN (SELECT service from relationPersonService WHERE relationPersonService.person = :person)
            ORDER BY s.order";
$stmt = $dbh->prepare($query);                                            
$stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);