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


if ($_GET["action"] === "cash") {       
    $query = " SELECT
                    'HPSKUP' as codePohoda,
                    '2000' as initialBalance,
                    (
                        SELECT
                            SUM(amount)
                        FROM deposits
                        WHERE
                            date BETWEEN :dateFrom AND :dateTo AND
                            paymentType = 'hotově'
                    ) as deposits,
                    SUM(
                        ge.cash
                        *
                        IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = ge.id))
                        ) as cash,
                        
                    0 +
                        (SELECT
                            IFNULL(SUM(amount),0)
                        FROM deposits
                        WHERE
                            date BETWEEN :dateFrom AND :dateTo AND
                            paymentType = 'hotově'
                        ) +
                        SUM(
                            ge.cash
                            *
                            IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = ge.semestralCourse))
                        ) +
                        SUM(
                            ge.free
                            *
                            IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = ge.semestralCourse))
                        )
                            as turnover,
                    
                    2000 + 
                        (SELECT
                            IFNULL(SUM(amount),0)
                        FROM deposits
                        WHERE
                            date BETWEEN :dateFrom AND :dateTo AND
                            paymentType = 'hotově'
                        ) +
                        SUM(
                            ge.cash
                            *
                            IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = ge.semestralCourse))
                        ) +
                        SUM(
                            ge.free
                            *
                            IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = ge.semestralCourse))
                        )
                            as totalBalance,
                       
                        SUM(
                        ge.free
                        *
                        IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = ge.semestralCourse))
                        ) as free
                        
            FROM groupExcercises ge

            WHERE                
                ge.date BETWEEN :dateFrom AND :dateTo
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);

    echo json_encode($results);
}

if ($_GET["action"] === "all") {
    $query = " SELECT                    
                    SUM(
                        IFNULL(ge.cash * IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE semestralCourses.id = ge.id)),0)
                        +
                        IFNULL((SELECT SUM(prepaid) FROM visits WHERE visits.ge = ge.id),0)
                        + 
                        IFNULL(ge.QR * IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE semestralCourses.id = ge.id)),0)
                        +
                        IFNULL(ge.benefit * IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE semestralCourses.id = ge.id)),0)
                        +
                        IFNULL(ge.free * IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE semestralCourses.id = ge.id)),0)
                        +
                        IFNULL((SELECT COUNT(groupExcercisesParticipants.id) 
                         FROM groupExcercisesParticipants 
                         LEFT JOIN groupExcercises ON groupExcercises.id = groupExcercisesParticipants.groupExcercise
                         WHERE groupExcercisesParticipants.groupExcercise = ge.id AND groupExcercises.canceled = '0')
                        * 
                        IF(ge.semestralCourse IS NULL, 0, (SELECT internalPrice FROM semestralCourses WHERE semestralCourses.id = ge.semestralCourse)),0)
                        ) as turnover,
                    (SELECT SUM(code)*100 FROM reservations WHERE date BETWEEN :dateFrom AND :dateTo) as unrealized
            FROM groupExcercises ge

            WHERE                
                ge.date BETWEEN :dateFrom AND :dateTo
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    echo json_encode($result);
}