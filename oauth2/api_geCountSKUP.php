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


$query = " SELECT                    
                ge. title,
                CONCAT(
                    DAY(ge.date), 
                    '.', 
                    MONTH(ge.date), 
                    '.', 
                    YEAR(ge.date) 
                    ) as date,
                SUBSTRING(DAYNAME(ge.date),1,3) as denTydne,
                SUM(
                    IFNULL(ge.cash, 0)
                    +
                    IFNULL((SELECT Count(prepaid) FROM visits WHERE visits.ge = ge.id), 0)
                    + 
                    IFNULL(ge.QR, 0)
                    +
                    IFNULL(ge.benefit, 0)
                    +
                    IFNULL(ge.free, 0)
                    +
                    IFNULL((SELECT COUNT(groupExcercisesParticipants.id) 
                        FROM groupExcercisesParticipants 
                        LEFT JOIN groupExcercises ON groupExcercises.id = groupExcercisesParticipants.groupExcercise
                        WHERE groupExcercisesParticipants.groupExcercise = ge.id AND groupExcercises.canceled = '0' AND groupExcercises.semestralCourse IS NOT NULL), 0)                    
                    ) as pocetUcastniku,
                    
                    IF((SELECT groupExcercises.semestralCourse FROM groupExcercises WHERE groupExcercises.id = ge.id) IS NULL,
                        (SELECT GROUP_CONCAT(AES_DECRYPT(clients.surname, '" . Settings::$mySqlAESpassword . "')  SEPARATOR ', ')				 
                        FROM groupExcercisesParticipants 				 
                        LEFT JOIN clients ON clients.id = groupExcercisesParticipants.client				 
                        WHERE ge.id = groupExcercisesParticipants.groupExcercise),
                        
                        CONCAT((SELECT COUNT(attendance.id)FROM attendance WHERE ge.id = attendance.ge AND attendance.ucast = 1),' - ',
                        (SELECT GROUP_CONCAT(AES_DECRYPT(clients.surname, '" . Settings::$mySqlAESpassword . "')  SEPARATOR ', ')				 
                        FROM attendance 				 
                        LEFT JOIN clients ON clients.id = attendance.client				 
                        WHERE 
                        ge.id = attendance.ge AND
                        attendance.ucast = 1)
                        )
                        )    
                        
                        AS jmenaUcastniku
        
            FROM groupExcercises ge

        WHERE                
            ge.date BETWEEN :dateFrom AND :dateTo AND
            ge.canceled = 0
        GROUP BY ge.id
        ORDER BY ge.title ";

$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchALL(PDO::FETCH_OBJ);

echo json_encode($result);
