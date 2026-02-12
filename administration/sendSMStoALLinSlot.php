<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

//info o volném slotu
$query = "SELECT
            pat.date, 
            DATE_FORMAT(pat.date,'%d.%m.%Y') as dateFormatted,
            pat.time, 
            DATE_FORMAT(pat.time,'%H:%i') as timeFormatted,
            pat.person as therapistId, 
            a.displayName as therapist
        FROM personAvailabilityTimetable pat 
        LEFT JOIN adminLogin a ON a.id = pat.person         
        WHERE pat.id = :idSlot ";

$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":idSlot", $_POST["idSlot"], PDO::PARAM_INT);
$stmt->execute();
$volnySlot = $stmt->fetch(PDO::FETCH_OBJ);

$date = (new DateTime($volnySlot->date));
$time = $volnySlot->time;

$dayOfWeek = intval($date->format("N"));
$differenceFromMonday = $dayOfWeek - 1;
$lastMonday = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P" . $differenceFromMonday . "D"));
$lastMondayString = $lastMonday->format('Y-m-d H:i:s');

$firstDayOfMonth = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P14D"));
$firstDayOfMonthString = $firstDayOfMonth->format('Y-m-d H:i:s');

$lastDayOfMonth = (new DateTime($date->format("Y-m-d")))->add(new DateInterval("P14D"));
$lastDayOfMonthString = $lastDayOfMonth->format('Y-m-d H:i:s');     

//dotaz na klienty na čekací listině
$query = "                       
        SELECT
            c.id as clientId,
            IF(c.date IS NULL, NULL, ROUND(DATEDIFF(now(), c.date)/365,1)) as age,
            t.service as serviceId,
            s.textSMSwl as service,
            CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) as client, 
            IF(
                (SELECT id
                 FROM reservations res 
                 WHERE 
                     (res.client = t.client OR
                      res.phone = c.phone OR
                      res.email = c.email)
                 ORDER BY res.date DESC, res.hour DESC
                 LIMIT 1
                ) =                                    
                    (SELECT 
                        id 
                     FROM reservations res 
                     WHERE 
                        (res.client = t.client OR
                         res.phone = c.phone OR
                         res.email = c.email) AND 
                        res.active = 0 AND 
                        (res.deleteReason = 'Nemoc terapeuta' OR res.deleteReason = 'Organizační důvody na straně FL')                                            
                    ORDER BY date DESC, res.hour DESC LIMIT 1
                    ), 
                'ANO', 'NE'
            ) as cancelByFL
            
        FROM clienttherapists2 t
        LEFT JOIN clients c ON c.id = t.client
        LEFT JOIN services s ON s.id = t.service
        LEFT JOIN adminLogin a ON a.id = t.therapist
        INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

        WHERE
            t.therapist = :therapist AND
            (
                IFNULL(
                    (SELECT noWL FROM weeknotes WHERE client = t.client AND firstDayOfWeek = :lastMonday),
                    0
                ) = 0
            ) AND
            -- má zájem o daný slot            
            (
                IF (p.slottype = 'indiv', t.client IN (SELECT client FROM clientAvailabilityWL WHERE time = :time AND date = :date), FALSE) OR 
                CASE
                    WHEN p.slottype = 'forenoon' THEN :time < CAST('13:00:00' AS time)
                    WHEN p.slottype = 'afternoon' THEN :time > CAST('12:00:00' AS time)
                    WHEN p.slottype = 'all' THEN TRUE
                    WHEN p.slottype IS NULL THEN FALSE
                END
            ) AND
            p.activeWL = 1 AND 
            !EXISTS(
                SELECT r.id FROM reservations r
                WHERE 
                    :date BETWEEN DATE(r.deleteTimestamp) AND DATE_ADD(DATE(r.deleteTimestamp), INTERVAL 5 DAY) AND 
                    r.deleteReason = 'Nemoc klienta' AND                             
                    r.active = 0 AND 
                    (
                        r.client = c.id OR 
                        (
                            r.client IS NULL AND
                            (r.phone = c.phone OR c.email = r.email) AND TRIM(AES_DECRYPT(c.name , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.name , '" . Settings::$mySqlAESpassword . "')) AND TRIM(AES_DECRYPT(c.surname , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.surname , '" . Settings::$mySqlAESpassword . "'))
                        )
                    ) 
                ) AND
            (SELECT COUNT(r.id)FROM reservations r
                WHERE 
                     (
                        r.client = c.id OR 
                        (
                            r.client IS NULL AND
                            (r.phone = c.phone OR c.email = r.email) AND TRIM(AES_DECRYPT(c.name , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.name , '" . Settings::$mySqlAESpassword . "')) AND TRIM(AES_DECRYPT(c.surname , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.surname , '" . Settings::$mySqlAESpassword . "'))
                        )
                    ) AND
                    r.active = 0 AND
                    r.personnel = t.therapist AND
                    r.date = :date AND
                    CAST(CONCAT(r.hour, ':', r.minute) as time) = :time
            ) = 0 AND
            IF (t.service = 10, (SELECT COUNT(id) FROM reservations res WHERE res.personnel = t.therapist AND res.active = 1 AND res.service = 10 AND res.date=:date)<2, TRUE) AND
            IF (t.service = 10, (SELECT COUNT(id) FROM reservations res WHERE res.personnel = t.therapist AND res.active = 1 AND res.service = 10 AND res.date BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY))<a.maxEntryErgoPerWeek, TRUE) AND
            (
                (SELECT COUNT(id)FROM reservations r
                    WHERE
                        (
                            r.client = c.id OR 
                            (
                                r.client IS NULL AND
                                (r.phone = c.phone OR c.email = r.email) AND TRIM(AES_DECRYPT(c.name , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.name , '" . Settings::$mySqlAESpassword . "')) AND TRIM(AES_DECRYPT(c.surname , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.surname , '" . Settings::$mySqlAESpassword . "'))
                            )
                        )  AND
                        (
                            IF(t.service = 2, (r.service = 2 OR r.service = 12), r.service = t.service)
                        ) AND
                        r.active = 1 AND
                        r.sooner IS NULL AND
                        r.date BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY)                                    
                ) < p.freqW OR
                (
                    (SELECT COUNT(id) FROM reservations r
                        WHERE
                            (
                                r.client = c.id OR 
                                (
                                    r.client IS NULL AND
                                    (r.phone = c.phone OR c.email = r.email) AND TRIM(AES_DECRYPT(c.name , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.name , '" . Settings::$mySqlAESpassword . "')) AND TRIM(AES_DECRYPT(c.surname , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.surname , '" . Settings::$mySqlAESpassword . "'))
                                )
                            )  AND
                            (
                                IF(t.service = 2, (r.service = 2 OR r.service = 12), r.service = t.service)
                            ) AND
                            r.active = 1 AND
                            r.sooner IS NULL AND
                            r.date BETWEEN :firstDayOfMonth AND :lastDayOfMonth                                   
                    ) < p.freqM AND                                    
                    !EXISTS(SELECT r.id FROM reservations r
                        WHERE 
                            (
                                r.client = c.id OR 
                                (
                                    r.client IS NULL AND
                                    (r.phone = c.phone OR c.email = r.email) AND TRIM(AES_DECRYPT(c.name , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.name , '" . Settings::$mySqlAESpassword . "')) AND TRIM(AES_DECRYPT(c.surname , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.surname , '" . Settings::$mySqlAESpassword . "'))
                                )
                            )  AND
                            r.active = 1 AND
                            r.sooner IS NULL AND
                            (
                                IF(t.service = 2, (r.service = 2 OR r.service = 12), r.service = t.service)
                            ) AND
                            r.date BETWEEN DATE_ADD(:date, INTERVAL -5 DAY) AND DATE_ADD(:date, INTERVAL 5 DAY)                                    
                    )   
                ) OR
                IF(p.freqM2 IS NULL, FALSE,
                    (SELECT COUNT(id) FROM reservations r
                       WHERE
                           (
                                r.client = c.id OR 
                                (
                                    r.client IS NULL AND
                                    (r.phone = c.phone OR c.email = r.email) AND TRIM(AES_DECRYPT(c.name , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.name , '" . Settings::$mySqlAESpassword . "')) AND TRIM(AES_DECRYPT(c.surname , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.surname , '" . Settings::$mySqlAESpassword . "'))
                                )
                            )  AND
                            (
                                IF(t.service = 2, (r.service = 2 OR r.service = 12), r.service = t.service)
                            ) AND
                           r.active = 1 AND
                           r.sooner IS NULL AND
                           r.date BETWEEN DATE_ADD(:date, INTERVAL - ((SELECT p.freqM2 FROM clientWLparam p WHERE p.client = t.client AND p.service = t.service)-1)*30 DAY) AND DATE_ADD(:date, INTERVAL + 60 DAY)                                   
                   ) < 1
                )
            )                          
        ORDER BY  
            (SELECT actionTimestamp FROM logWL WHERE client = t.client AND WLdate = :date AND WLtime = :time AND therapist = :therapist ORDER BY actionTimestamp DESC LIMIT 1),
            p.urgent DESC,
            cancelByFL ASC,
            s.middlecut ASC,                            
            CASE WHEN t.service = 2 AND (:time > CAST('07:00:00' AS time) AND :time2 < CAST('12:00:00' AS time)) THEN age END ASC,
            CASE WHEN t.service = 2 AND (:time > CAST('12:00:00' AS time) OR :time2 = CAST('07:00:00' AS time)) THEN age END DESC,                            
            CASE WHEN t.service = 1 THEN age END ASC,
            CASE WHEN t.service = 11 THEN age END ASC
        ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":time", $volnySlot->time, PDO::PARAM_STR);
$stmt->bindParam(":time2", $volnySlot->time, PDO::PARAM_STR);
$stmt->bindParam(":date", $volnySlot->date, PDO::PARAM_STR);
$stmt->bindParam(":therapist", $volnySlot->therapistId, PDO::PARAM_INT);
$stmt->bindParam(":lastMonday", $lastMondayString, PDO::PARAM_STR);
$stmt->bindParam(":firstDayOfMonth", $firstDayOfMonthString, PDO::PARAM_STR);
$stmt->bindParam(":lastDayOfMonth", $lastDayOfMonthString, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);    

foreach ($results as $result) {
    $hash = bin2hex(openssl_random_pseudo_bytes(5));
    $smsText = 'Fyzioland: Nabídka uvolněného termínu na ' . $result->service . ' '.  
            $volnySlot->dateFormatted . " v " . $volnySlot->timeFormatted .
            ' pro ' . $result->client .
            ' (terapeut '. $volnySlot->therapist . '). Máte-li o termín zájem, klikněte na následující odkaz www.fyzioland.cz/rezervaceActionWLq?hsh=' . $hash .
            ' pro potvrzení zájmu o rezervaci (pokud bude termín ještě volný, potvrzení o rezervaci Vám přijde emailem). ' .
            $resultAdminUser->displayName;
    
$query = "
    INSERT 
        INTO logWL(client, WLdate, WLtime, service, therapist, message, type, actionTimestamp, hash, creationTimestamp, user)
        VALUES(:clientId, :WLdate, :WLtime, :serviceId, :therapistId, :message, 'SMS', NULL, :hash, NOW(), :user)
        ";

$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":clientId", $result->clientId, PDO::PARAM_STR);
$stmt->bindValue(":WLdate", $volnySlot->date, PDO::PARAM_STR);
$stmt->bindValue(":WLtime", $volnySlot->time, PDO::PARAM_STR);
$stmt->bindValue(":serviceId", $result->serviceId, PDO::PARAM_INT);
$stmt->bindValue(":therapistId", $volnySlot->therapistId, PDO::PARAM_INT);
$stmt->bindValue(":user", $resultAdminUser->id, PDO::PARAM_INT);
$stmt->bindValue(":message", $smsText, PDO::PARAM_STR);
$stmt->bindValue(":hash", $hash, PDO::PARAM_STR);
$stmt->execute();    
}

echo json_encode('Hotovo');
