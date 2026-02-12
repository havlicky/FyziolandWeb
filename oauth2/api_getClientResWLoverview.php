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


$date = (new DateTime()); 
$dayOfWeek = intval($date->format("N"));
$differenceFromMonday = $dayOfWeek - 1;
$_POST["dateFrom"] = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P" . $differenceFromMonday . "D"));
$_POST["dateFrom"]  = $_POST["dateFrom"]->format("Y-m-d"); 
$_POST["dateTo"] = (new DateTime($date->format("Y-m-d")))->add(new DateInterval("P" . (-$differenceFromMonday + 6) . "D"));
$_POST["dateTo"] =$_POST["dateTo"]->format("Y-m-d"); 


// zjištění posledního data rezervace
$query = "  SELECT MAX(r.date) as date FROM reservations r            
            WHERE
                r.date>=:date AND
                r.active = 1 AND

                (AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone OR
                  AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "')  = :email OR
                  r.client = :client)
            ORDER BY r.date ASC
        ";

$stmt = $dbh->prepare($query);
$stmt->bindValue(":date", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindValue(":email", $_POST["email"], PDO::PARAM_STR);
$stmt->bindValue(":phone", $_POST["phone"], PDO::PARAM_STR);
$stmt->bindValue(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();
$poslRes = $stmt->fetch(PDO::FETCH_OBJ);


// zjištění posledního data čekací listiny
$query = "  SELECT MAX(wl.date) as date FROM clientAvailabilityWL wl            
            WHERE
                wl.date>=:date AND
                wl.client = :client           
        ";

$stmt = $dbh->prepare($query);
$stmt->bindValue(":date", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindValue(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();
$poslWL = $stmt->fetch(PDO::FETCH_OBJ);

$i = 0;

do {    
    
    //zjištění týdenní poznámky WL u daného klienta
    $query = "  
                SELECT 
                    note,
                    COUNT(id) pocet
                FROM weeknotes 
                WHERE client=:client AND firstDayofWeek=:dateFrom                                
        ";

    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);    
    $stmt->bindValue(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
    $WeekNotes = $stmt->fetch(PDO::FETCH_OBJ);
    
    // dotaz na rezervace klienta
    $query = "  SELECT               
                
                GROUP_CONCAT(CONCAT('<b>',
                    CASE 
                        WHEN :date = '' THEN IF(DATE(r.creationTimestamp) = CURDATE(), '<FONT COLOR = tomato>','')
                        ELSE IF(DATE(r.creationTimestamp) = :date, '<FONT COLOR = tomato>','')
                    END,
                    
                    DAY(r.date), 
                    '.', 
                    MONTH(r.date), 
                    '.', 
                    YEAR(r.date),'</b>',
                    CASE 
                        WHEN :date = '' THEN IF(DATE(r.creationTimestamp) = CURDATE(), '</FONT>','')
                        ELSE IF(DATE(r.creationTimestamp) = :date, '</FONT>','')
                    END                    
                    )
                    ORDER BY r.date, r.hour, r.id SEPARATOR '<br>') as date,
                GROUP_CONCAT(SUBSTRING(DAYNAME(r.date),1,3) ORDER BY r.date, r.hour, r.id SEPARATOR '<br>') as denTydne,
                GROUP_CONCAT(DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') ORDER BY r.date, r.hour, r.id  SEPARATOR '<br>') as time,
                GROUP_CONCAT(CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'), ', ',  AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')) ORDER BY r.date, r.hour, r.id SEPARATOR '<br>') as client, 
                GROUP_CONCAT(
                    CASE
                        WHEN s.shortcut = 'ERGO-kont' THEN CONCAT('<b><FONT COLOR=IndianRed>', s.shortcut, '</FONT></b>')
                        WHEN s.isFyzio = 1 THEN CONCAT('<b><FONT COLOR=blue>', s.shortcut, '</FONT></b>')
                        WHEN s.isErgo = 1 THEN CONCAT('<b><FONT COLOR=green>', s.shortcut, '</FONT></b>')
                        ELSE s.shortcut
                    END
                    ORDER BY r.date, r.hour, r.id SEPARATOR '<br>'
                            ) as service,
                GROUP_CONCAT(
                    CASE
                        WHEN s.shortcut = 'ERGO-kont' THEN CONCAT('<b><FONT COLOR=IndianRed>', a.shortcut, '</FONT></b>')
                        WHEN s.isFyzio = 1 THEN CONCAT('<b><FONT COLOR=blue>', a.shortcut, '</FONT></b>')
                        WHEN s.isErgo = 1 THEN CONCAT('<b><FONT COLOR=green>', a.shortcut, '</FONT></b>')
                        ELSE a.shortcut
                    END
                ORDER BY r.date, r.hour, r.id SEPARATOR '<br>') as therapist,                    
                r.date as dateFormatted               
            FROM reservations r
            
            LEFT JOIN adminLogin a ON a.id = r.personnel
            LEFT JOIN services s ON s.id = r.service

            WHERE                
                r.active = 1 AND
                IF(:dateFrom<CURDATE(),
                    r.date BETWEEN CURDATE() AND :dateTo,
                    r.date BETWEEN :dateFrom AND :dateTo 
                ) AND
                (AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone OR
                 AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "')  = :email OR
                  r.client = :client)
            GROUP BY :dateFrom
           
        ";

    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindValue(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindValue(":email", $_POST["email"], PDO::PARAM_STR);
    $stmt->bindValue(":phone", $_POST["phone"], PDO::PARAM_STR);
    $stmt->bindValue(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindValue(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->execute();
    $resultRes = $stmt->fetch(PDO::FETCH_OBJ);
    
    $results[$i] = new StdClass();    
    if($WeekNotes->pocet>0) {
        $results[$i]->weekstart = (new DateTime($_POST["dateFrom"]))->format("j.n") . '<br>(!)';
    } else {
        $results[$i]->weekstart = (new DateTime($_POST["dateFrom"]))->format("j.n");
    }
    
    if($resultRes != false) {                
        $results[$i]->date = $resultRes->date;
        $results[$i]->denTydne = $resultRes->denTydne; 
        $results[$i]->time = $resultRes->time;   
        $results[$i]->client = $resultRes->client;   
        $results[$i]->service = $resultRes->service;   
        $results[$i]->therapist = $resultRes->therapist;
        $results[$i]->dateFormatted = $resultRes->dateFormatted;   
    }
                
    if($resultRes == false) {                
        $results[$i]->date = '-';
        $results[$i]->denTydne = '-';
        $results[$i]->time = '-';
        $results[$i]->client = '-';
        $results[$i]->service = '-';
        $results[$i]->therapist = '-';
        $results[$i]->dateFormatted = (new DateTime($_POST["dateFrom"]))->format("Y-m-d");
    }
    
    $results[$i]->weeknote = $WeekNotes->note;
    
    $query = "  SELECT 	                
                
                CONCAT(
                    DAY(wl.date), 
                    '.', 
                    MONTH(wl.date), 
                    '.', 
                    YEAR(wl.date)
                    ) as date,
                
                CONCAT(AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'), ', ',  AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')) as client,   
                CONCAT(
                    'WL ', 
                    '<FONT color = silver>(',
                    (SELECT COUNT(id) FROM clientAvailabilityWL WHERE date BETWEEN :dateFrom AND :dateTo AND client = :client),
                    ')</FONT>'                       
                ) as service,
                wl.date as dateFormatted               
            FROM clientAvailabilityWL wl
            LEFT JOIN clients c ON c.id = wl.client

            WHERE                                
                wl.date BETWEEN :dateFrom AND :dateTo AND               
                wl.client = :client
            GROUP BY :dateFrom
            ORDER BY wl.date ASC
        ";
    
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindValue(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);    
    $stmt->bindValue(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
    $resultsWL = $stmt->fetchALL(PDO::FETCH_OBJ);
    
    foreach($resultsWL as $resultWL) {
        //$results[$i]->date = $results[$i]->date . '<br>' . $resultWL->date;
        $results[$i]->date = $results[$i]->date . '<br>' . '-';
        $results[$i]->denTydne = $results[$i]->denTydne . '<br> -';
        $results[$i]->time = $results[$i]->time . '<br> -';
        $results[$i]->client = $results[$i]->client . '<br>' . $resultWL->client;
        $results[$i]->service = $results[$i]->service. '<br>' .$resultWL->service;
        $results[$i]->therapist = $results[$i]->therapist . '<br> -';
    }
    
    // posun datumů o týden
    $_POST["dateFrom"] = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P7D"));
    $_POST["dateFrom"] =  $_POST["dateFrom"]->format("Y-m-d");    
    $_POST["dateTo"] = (new DateTime($_POST["dateTo"]))->add(new DateInterval("P7D"));        
    $_POST["dateTo"] = $_POST["dateTo"]->format("Y-m-d");

    $i=$i+1;    
    
} while ($poslWL->date >= $_POST["dateFrom"] || $poslRes->date >= $_POST["dateFrom"]); 

$query = " SELECT IFNULL(noteWL,'Žádná.') as noteWL FROM clients WHERE id = :client";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();
$clientNote = $stmt->fetch(PDO::FETCH_OBJ);

$results[0]->clientNote = $clientNote->noteWL;

echo json_encode($results);
