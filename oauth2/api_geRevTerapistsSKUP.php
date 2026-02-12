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

$query = "             
            SELECT                                        
                al.displayName as personSKUP,
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
                     LEFT JOIN attendance a ON a.ge = ge.id AND groupExcercisesParticipants.client = a.client 
                     WHERE groupExcercisesParticipants.groupExcercise = ge.id AND groupExcercises.canceled = '0' AND (a.nechodi IS NULL OR a.nechodi = 0))
                    * 
                    IF(ge.semestralCourse IS NULL, 0, (SELECT internalPrice FROM semestralCourses WHERE semestralCourses.id = ge.semestralCourse)),0)
                    ) as revenues,
                SUM(ge.persCost) as persCost,
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
                     LEFT JOIN attendance a ON a.ge = ge.id AND groupExcercisesParticipants.client = a.client 
                     WHERE groupExcercisesParticipants.groupExcercise = ge.id AND groupExcercises.canceled = '0' AND (a.nechodi IS NULL OR a.nechodi = 0))
                    * 
                    IF(ge.semestralCourse IS NULL, 0, (SELECT internalPrice FROM semestralCourses WHERE semestralCourses.id = ge.semestralCourse)),0)
                    ) - SUM(ge.persCost) as PL,
                    
                COUNT(ge.id) as pocetLekci
               
            FROM groupExcercises ge
            LEFT JOIN adminLogin al ON al.id = ge.instructor
            WHERE                
                ge.date BETWEEN :dateFrom AND :dateTo AND
                ge.canceled = 0
            GROUP BY personSKUP
            
            UNION
            
            SELECT                                        
                'CELKEM' as personSKUP,
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
                     LEFT JOIN attendance a ON a.ge = ge.id AND groupExcercisesParticipants.client = a.client 
                     WHERE groupExcercisesParticipants.groupExcercise = ge.id AND groupExcercises.canceled = '0' AND (a.nechodi IS NULL OR a.nechodi = 0))
                    * 
                    IF(ge.semestralCourse IS NULL, 0, (SELECT internalPrice FROM semestralCourses WHERE semestralCourses.id = ge.semestralCourse)),0)
                    ) as revenues,
                    SUM(ge.persCost) as persCost,
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
                     LEFT JOIN attendance a ON a.ge = ge.id AND groupExcercisesParticipants.client = a.client 
                     WHERE groupExcercisesParticipants.groupExcercise = ge.id AND groupExcercises.canceled = '0' AND (a.nechodi IS NULL OR a.nechodi = 0))
                    * 
                    IF(ge.semestralCourse IS NULL, 0, (SELECT internalPrice FROM semestralCourses WHERE semestralCourses.id = ge.semestralCourse)),0)
                    ) - SUM(ge.persCost) as PL,
                    COUNT(ge.id) as pocetLekci
                    
                    
            FROM groupExcercises ge            
            WHERE                
                ge.date BETWEEN :dateFrom AND :dateTo AND
                ge.canceled = 0
            ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($results);