<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";
require_once "../php/class.settings.php";

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
                c.fyzduration,
                c.fyzBezLehatka,
                c.slotTypes,
                c.updateType,
                c.lastSlotsUpdate,                
                IFNULL(
                    (SELECT
                        GROUP_CONCAT(al.shortcut SEPARATOR ', ')
                    FROM clienttherapists ct
                    LEFT JOIN adminLogin al ON al.id = ct.therapist AND ct.type='main'
                    WHERE ct.client = c.id)
                    ,
                    '-'
                    ) as mainTherapists,
                    
                IFNULL(
                    (SELECT
                        GROUP_CONCAT(al.shortcut SEPARATOR ', ')
                    FROM clienttherapists ct
                    LEFT JOIN adminLogin al ON al.id = ct.therapist AND ct.type='other'
                    WHERE ct.client = c.id)
                    ,
                    '-'
                    ) as otherTherapists,
                 
                IFNULL(
                    (SELECT
                        GROUP_CONCAT(DISTINCT
                            CONCAT(al.shortcut, '-',
                                (SELECT COUNT(res.id)
                                 FROM reservations res
                                 WHERE 
                                    res.active = 1 AND
                                    res.date < NOW() AND
                                    res.personnel = al.id AND
                                    (res.client = :client OR
                                    res.email = c.email OR
                                    res.phone = c.phone)
                                                                        
                                )                                                                
                            )
                            SEPARATOR ', '                                
                        )                 
                    FROM reservations r
                    LEFT JOIN adminLogin al ON al.id = r.personnel
                    WHERE r.client = c.id)
                    ,
                    '-'
                    ) as allTherapists
            FROM clients c
            WHERE
            id = :client";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

echo json_encode($result);