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

if (isset($_POST["active"])) {
    $active = $_POST["active"];    
} else {
    $active = 1;
}


if($_POST["person"] == 'allErgoTherapists') {
    // věšichni terapeuti do jednoho řetězce pro jednotlivé datumy a časy
    
    $query = "  SELECT
                    r.date,                    
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS time,                   
                    GROUP_CONCAT(
                        if(
                            r.client = :client OR 
                            AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR
                            AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone
                            ,
                            CONCAT(
                                '<FONT COLOR=red>','<b>', al.shortcut, ': ', 
                                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ',
                                SUBSTRING(AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),1,1),'.</b><FONT COLOR=black>'
                            )
                            ,
                            IF(r.service = 10,
                                CONCAT(
                                    '<FONT COLOR=blue>',
                                    al.shortcut, ': <b>', 
                                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ',
                                    SUBSTRING(AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),1,1),'.</b> <FONT COLOR=black>'
                                ),
                                IF(r.service = 12,
                                    CONCAT(
                                        '<FONT COLOR=Green>',
                                        al.shortcut, ': <b>', 
                                        AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ',
                                        SUBSTRING(AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),1,1),'.</b> <FONT COLOR=black>'
                                    ),
                                    CONCAT(
                                        al.shortcut, ': <b>', 
                                        AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ',
                                        SUBSTRING(AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),1,1),'.</b>'
                                    )
                                )
                                    
                            )
                        ) 
                        ORDER BY al.orderRank 
                        SEPARATOR '<br>'
                    ) as client
                FROM reservations r

                LEFT JOIN services s ON s.id = r.service
                LEFT JOIN adminLogin al ON al.id = r.personnel

                WHERE                    
                    r.date BETWEEN :dateFrom AND :dateTo AND
                    r.active = 1 AND
                    al.active= 1
                GROUP by r.date, time
                ORDER BY al.shortcut
                ";
    $stmt = $dbh->prepare($query);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);    
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
} else if($_POST["person"] =='clientOnly'){
    // rezervace pouze daného klienta 
    $query = "  SELECT
                    r.id,
                    r.date,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS time,                   
                    GROUP_CONCAT(                        
                        CONCAT(
                            al.shortcut, ': ','<FONT COLOR=red>', 
                            AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ', 
                            AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),
                            '<FONT COLOR=black>'
                        )
                        SEPARATOR '<br>'
                       ) as client,                    
                    s.shortcut
                FROM reservations r

                LEFT JOIN services s ON s.id = r.service
                LEFT JOIN adminLogin al ON al.id = r.personnel

                WHERE
                    (r.client = :client OR AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone) AND
                    r.date BETWEEN :dateFrom AND :dateTo AND
                    r.active = 1
                GROUP BY r.date
                ORDER BY r.date, time";
    $stmt = $dbh->prepare($query);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
    $stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
} else if($_POST["person"] =='freeSlotsOnly'){
    // rezervace pouze daného klienta 
    $query ="  SELECT
                    CONCAT(
                            '<b>', 
                            GROUP_CONCAT(al.shortcut SEPARATOR '</b>, <b>'),
                            '</b>'                            
                          ) as freeSlotsTherapists,
                        
                    DATE_FORMAT(pat.time, '%H:%i') as time,
                    pat.date,
                    1 as green
                FROM personAvailabilityTimetable pat
                LEFT JOIN adminLogin al ON al.id = pat.person                
                WHERE
                    pat.date BETWEEN :dateFrom AND :dateTo AND
                    
                    ! EXISTS 
                        (SELECT id FROM reservations r WHERE 
                                r.active = 1 AND
                                r.personnel = pat.person AND
                                r.date = pat.date AND
                                CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
                        )       
                                                
                GROUP BY pat.date, pat.time
                "; 
            
    $stmt = $dbh->prepare($query);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);    
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);            
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);         
    
    $query ="  SELECT
                    r.date,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as time,
                    GROUP_CONCAT(
                            CONCAT(
                                '<FONT COLOR=red>',
                                al.shortcut, ': ', 
                                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ', 
                                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),
                                '<FONT COLOR=black>',
                                '<br>'

                              ) 
                            SEPARATOR '<br>'
                          ) as rezervace
                    
                FROM reservations r
                LEFT JOIN adminLogin al ON al.id = r.personnel
                
                WHERE
                    r.active = 1 AND
                    r.date BETWEEN :dateFrom AND :dateTo AND
                    (r.client = :client OR 
                    AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR
                    AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone)
                GROUP BY r.date, time
                            
                "; 
            
    $stmt = $dbh->prepare($query);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
    $stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);            
    $stmt->execute();
    $results2 = $stmt->fetchAll(PDO::FETCH_OBJ);
       
    //najde, kde jsou volní terapeuti a rezervace shodují dle datumu a času
    foreach($results as $result1) {                        
        foreach ($results2 as $result2) {
            if($result1->date == $result2->date && $result1->time == $result2->time) {
                $result1->freeSlotsTherapists = $result2->rezervace . $result1->freeSlotsTherapists;
                
            }                 
        }        
    }        
    // pro každé okénko s rezervací, které se neshoduje s volným termínem udělá zápis
    $pocet = count($results);
    foreach($results2 as $result2) { 
        $found= false;
        foreach ($results as $result1) {            
            if ($result1->date == $result2->date && $result1->time == $result2->time) {
                $found = true;                
            }
        }
        if ($found == false) {
                $results[$pocet] =  new stdClass();
                $results[$pocet]->date = $result2->date;
                $results[$pocet]->time = $result2->time;
                $results[$pocet]->freeSlotsTherapists = $result2->rezervace; 
                $pocet = $pocet + 1;
        }                                          
    }        
    
    } else if($_POST["person"] =='freeSlotsEntryOnly'){
    // rezervace pouze daného klienta 
    $query = "  SELECT
                    CONCAT('<b>', GROUP_CONCAT(
                        CONCAT
                            (al.shortcut, '</b> <FONT COLOR=gray> (',
                             (SELECT
                                COUNT(res.id)
                             FROM reservations res
                             WHERE
                                res.personnel = al.id AND
                                res.service = 10 AND
                                res.active = 1 AND
                                res.date BETWEEN :dateFrom AND :dateTo), 
                            ')<b> <FONT COLOR=black> '
                            ) 
                        SEPARATOR '</b>, <b>'),'</b>') 
                    as freeSlotsTherapists,
                    
                DATE_FORMAT(pat.time, '%H:%i') as time,
                    pat.date
                FROM personAvailabilityTimetable pat
                LEFT JOIN adminLogin al ON al.id = pat.person                
                WHERE
                    pat.date BETWEEN :dateFrom AND :dateTo AND
                    ! EXISTS 
                        (SELECT id FROM reservations r WHERE 
                                r.active = 1 AND
                                r.personnel = pat.person AND
                                r.date = pat.date AND
                                CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
                        ) AND
                    EXISTS
                        (SELECT person FROM relationPersonService WHERE service = 10 AND person = pat.person)
                                                
                GROUP BY pat.date, pat.time
                ";
    $stmt = $dbh->prepare($query);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);            
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    } else {
    // zde pokud je vybrán konkrétní terapeut    
    $query = "  SELECT
                    r.id,
                    r.date,
                    CONCAT(AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "')) as clientName,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS time,
                    CASE 
                        WHEN (r.active = 1 AND (r.client = :client OR AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone)) THEN 
                                CONCAT(
                                   '<FONT COLOR=red>', 
                                        AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),
                                        ', ', 
                                        AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),
                                        '<FONT COLOR=black>'
                                )
                        WHEN :active = 0 THEN 
                                GROUP_CONCAT(    
                                        CONCAT(
                                                '<b><FONT COLOR=grey>', 
                                                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),
                                                ', ',
                                                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'), '</b><br>'
                                                '<small>',
                                                IFNULL(DATE_FORMAT(r.deleteTimestamp,'%d.%m.%Y %H:%i'),''), ' ', 
                                                IFNULL(al.shortcut,''), ' ',                                    
                                                '(', s.shortcut, ')', ' ',
                                                IFNULL(r.deleteReason,''),
                                                '</small>' 
                                        )
                                ORDER BY r.deleteTimestamp SEPARATOR '<br>'   
                                )
                        
                        WHEN r.service = 10 THEN 
                            CONCAT(
                                '<b><FONT COLOR=blue>',  
                                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),
                                ', ',
                                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),
                                '</b><FONT COLOR=black>'  
                               
                            )
                        WHEN r.service = 12 THEN 
                            CONCAT(
                                '<b><FONT COLOR=green>',  
                                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),
                                ', ',
                                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'),
                                '</b><FONT COLOR=black>'  
                               
                            )
                        ELSE                                                         
                            CONCAT(
                                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),
                                    ', ',
                                    AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')                                
                            )
                    END as client,                                        
                    IFNULL(
                            (
                                SELECT 
                                    IF(cl.date IS NULL, '', ROUND(DATEDIFF(now(), cl.date)/365,1)) 
                                FROM clients cl 
                                WHERE
                                   
                                    (
                                        r.client = cl.id OR
                                        (
                                            r.client IS NULL AND
                                            (r.phone = cl.phone OR cl.email = r.email) AND 
                                            r.name = cl.name AND
                                            r.surname = cl.surname 

                                            
                                        )
                                    )
                                LIMIT 1
                            ), 
                            ''
                    ) as age,                   
                    r.personnel,
                    s.shortcut,
                    s.id as service,
                    r.active

                FROM reservations r

                LEFT JOIN services s ON s.id = r.service
                LEFT JOIN adminLogin al ON al.id = r.deleteUser                

                WHERE
                    r.personnel = :person AND
                    r.date BETWEEN :dateFrom AND :dateTo AND
                    r.active = :active AND
                    (:active2 = 1 OR (:active3 = 0 AND :deleteReason = 'ALL') OR (:active4 = 0 AND :deleteReason2 = r.deleteReason))                    
                GROUP BY r.date, r.hour, r.minute, r.personnel
                ORDER BY r.date, r.hour, r.minute               
                ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":person", $_POST["person"] , PDO::PARAM_INT);    
    $stmt->bindParam(":active", $active, PDO::PARAM_INT);
    $stmt->bindParam(":active2", $active, PDO::PARAM_INT);
    $stmt->bindParam(":active3", $active, PDO::PARAM_INT);
    $stmt->bindParam(":active4", $active, PDO::PARAM_INT);
    $stmt->bindParam(":deleteReason", $_POST["reason"], PDO::PARAM_STR);    
    $stmt->bindParam(":deleteReason2", $_POST["reason"], PDO::PARAM_STR);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
    $stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
}
echo json_encode($results);