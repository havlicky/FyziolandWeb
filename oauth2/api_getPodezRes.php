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
                    personAvailabilityTimetable.date,
                    personAvailabilityTimetable.time,
                    (SELECT GROUP_CONCAT(al.shortcut ORDER BY 1 ASC SEPARATOR ', ')				 
                        FROM adminLogin al
                        LEFT JOIN personAvailabilityTimetable patt ON al.id = patt.person
                        WHERE personAvailabilityTimetable.date = patt.date AND personAvailabilityTimetable.time = patt.time AND patt.person IN (SELECT id FROM adminLogin WHERE isErgo = 1)) AS sloty,

                    (SELECT GROUP_CONCAT(al.shortcut ORDER BY 1 ASC SEPARATOR ', ')				 
                        FROM adminLogin al
                        LEFT JOIN reservations r ON al.id = r.personnel
                        WHERE personAvailabilityTimetable.date = r.date AND personAvailabilityTimetable.time = CAST(CONCAT(r.hour, ':', r.minute) AS TIME) AND r.active = 1 AND r.personnel IN (SELECT id FROM adminLogin WHERE isErgo = 1) ) AS rezervace                    
                FROM personAvailabilityTimetable
                WHERE
                    (
                    (SELECT COUNT(id) FROM personAvailabilityTimetable pat WHERE pat.date=personAvailabilityTimetable.date AND pat.time = personAvailabilityTimetable.time AND pat.person IN (SELECT id FROM adminLogin WHERE isErgo = 1))>3 OR 
                    ( (SELECT COUNT(id) FROM personAvailabilityTimetable pat WHERE pat.date=personAvailabilityTimetable.date AND pat.time = personAvailabilityTimetable.time AND pat.person IN (SELECT id FROM adminLogin WHERE isErgo = 1))+
                      ((SELECT COUNT(id) FROM reservations res WHERE res.date=personAvailabilityTimetable.date AND CAST(CONCAT(res.hour, ':', res.minute) AS TIME)= personAvailabilityTimetable.time AND res.personnel IN (SELECT id FROM adminLogin WHERE isErgo = 1) AND res.active = 1 AND

                        ! EXISTS(
                            SELECT
                                id
                            FROM
                                personAvailabilityTimetable patt2
                            WHERE
                                patt2.date = res.date AND patt2.time = CAST(CONCAT(res.hour, ':', res.minute) AS TIME) AND patt2.person = res.personnel

                            LIMIT 1
                       ))))>3 

                    ) AND personAvailabilityTimetable.date>=NOW()

                UNION

                SELECT
                    r.date,
                    CAST(CONCAT(r.hour, ':', r.minute) AS TIME) as time,
                    (SELECT GROUP_CONCAT(al.shortcut ORDER BY 1 ASC SEPARATOR ', ')				 
                        FROM adminLogin al
                        LEFT JOIN personAvailabilityTimetable patt ON al.id = patt.person
                        WHERE r.date = patt.date AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = patt.time AND patt.person IN (SELECT id FROM adminLogin WHERE isErgo = 1)) AS sloty,

                    (SELECT GROUP_CONCAT(al.shortcut ORDER BY 1 ASC SEPARATOR ', ')				 
                        FROM adminLogin al
                        LEFT JOIN reservations ON al.id = reservations.personnel
                        WHERE r.date = reservations.date AND reservations.hour = r.hour AND reservations.active = 1 AND reservations.personnel IN (SELECT id FROM adminLogin WHERE isErgo = 1)
                        ) AS rezervace                    
                FROM reservations r
                WHERE
                 (SELECT COUNT(id) FROM reservations res WHERE res.date=r.date AND res.hour= r.hour AND res.personnel IN (SELECT id FROM adminLogin WHERE isErgo = 1) AND res.active = 1)>3 AND
                 r.date>=NOW()

                GROUP BY 1, 2

                ";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
