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

if ($_GET["action"] === "hotovost") {
    $query = "  SELECT
                ge.id,
                ge.title,
                ge.date,                
                ge.price*ge.Cash as amount,               
                al.displayName,
                al.zakPohoda
            FROM groupExcercises ge
            LEFT JOIN adminLogin al ON al.id = ge.instructor
            
            WHERE                
                (ge.date BETWEEN :dateFrom AND :dateTo) AND
                ge.Cash>0
            ORDER BY ge.date ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);

    echo json_encode($results);
}

if ($_GET["action"] === "benefit") {
    $query = "  SELECT
                ge.id,
                ge.title,
                ge.date,
                ge.Benefit as pocet,
                IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = (SELECT semestralCourse from groupExcercises WHERE id = ge.id))) AS price,
                al.displayName,
                al.zakPohoda
            FROM groupExcercises ge
            LEFT JOIN adminLogin al ON al.id = ge.instructor
            
            WHERE                
                (ge.date BETWEEN :dateFrom AND :dateTo) AND
                ge.Benefit>0
            ORDER BY ge.date ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);

    echo json_encode($results);
}

if ($_GET["action"] === "faktura") {
    $query = "  SELECT
                ge.id,
                ge.title,
                ge.date,
                ge.QR as pocet,
                IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = (SELECT semestralCourse from groupExcercises WHERE id = ge.id))) AS price,
                al.displayName,
                al.zakPohoda
            FROM groupExcercises ge
            LEFT JOIN adminLogin al ON al.id = ge.instructor
            
            WHERE                
                (ge.date BETWEEN :dateFrom AND :dateTo) AND
                ge.QR>0 AND
                ge.fixedInvoice = 0
            ORDER BY ge.date ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);

    echo json_encode($results);
}

if ($_GET["action"] === "predplatne") {
    $query = "  SELECT
                ge.id,
                ge.title,
                ge.date,
                (SELECT COUNT(id) FROM visits v WHERE v.ge = ge.id) as pocet,
                IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = (SELECT semestralCourse from groupExcercises WHERE id = ge.id))) AS price,
                al.displayName,
                al.zakPohoda
            FROM groupExcercises ge
            LEFT JOIN adminLogin al ON al.id = ge.instructor
            
            WHERE                
                (ge.date BETWEEN :dateFrom AND :dateTo) AND
                (SELECT COUNT(id) FROM visits v WHERE v.ge = ge.id)>0
            ORDER BY ge.date ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);

    echo json_encode($results);
}