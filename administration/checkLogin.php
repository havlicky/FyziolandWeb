<?php

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

if (empty($_COOKIE["login"]) || empty($_COOKIE["loginName"])) {
    header("Location: login");
    die();
} else {
    $query = "  SELECT
                    id,
                    displayName,
                    shortcut,
                    isSuperAdmin,
                    seeContactDetails,
                    makeReservations,
                    viewWL,
                    CASE WHEN EXISTS (SELECT id FROM relationPersonService WHERE person = adminLogin.id) THEN 1 ELSE 0 END AS isTerapist,
                    isErgo,
                    isFyzio,
                    limitW_ErgoClients,
                    slotChange
                FROM adminLogin 
                WHERE
                    login = :login AND
                    loginCookieValue = :cookie AND
                    loginCookieExpiry > NOW() AND
                    active = 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":login", $_COOKIE["loginName"], PDO::PARAM_STR);
    $stmt->bindParam(":cookie", $_COOKIE["login"], PDO::PARAM_STR);
    $stmt->execute();
    $resultAdminUser = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($resultAdminUser === FALSE) {
        header("Location: login");
        die();
    }
}



