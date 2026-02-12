<?php

require_once "php/class.settings.php";
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

$response = "0";
if (!empty($_POST["email"])) {
    $query = "SELECT id, CASE WHEN IFNULL(password, '') = '' THEN 0 ELSE 1 END AS passwordSet FROM clients WHERE AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') = :email LIMIT 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":email", trim(urldecode($_POST["email"])), \PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_OBJ);
    
    if ($result === FALSE) {
        $response = "1";
    } else if ($result->passwordSet === "0") {
        $response = "2";
    }
}

echo json_encode(array("response" => $response));