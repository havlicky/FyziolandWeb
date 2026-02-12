<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

/*
$date = (new DateTime()); 
$dayOfWeek = intval($date->format("N"));
$differenceFromMonday = $dayOfWeek - 1;
$lastMonday = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P" . $differenceFromMonday . "D"));
*/

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
$stmt->bindValue(":date", $_POST["dateFrom"] , PDO::PARAM_STR);
$stmt->bindValue(":email", $_POST["email"] , PDO::PARAM_STR);
$stmt->bindValue(":phone", $_POST["phone"] , PDO::PARAM_STR);
$stmt->bindValue(":client", $_POST["client"] , PDO::PARAM_STR);
$stmt->execute();
$poslRes = $stmt->fetch(PDO::FETCH_OBJ);

// zjištění posledního data čekací listiny
$query = "  SELECT MAX(wl.date) as date FROM clientAvailabilityWL wl            
            WHERE
                wl.date>=:date AND
                wl.client = :client           
        ";

$stmt = $dbh->prepare($query);
$stmt->bindValue(":date", $_POST["dateFrom"] , PDO::PARAM_STR);
$stmt->bindValue(":client", $_POST["client"] , PDO::PARAM_STR);
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
                
                GROUP_CONCAT(CONCAT(
                    DAY(r.date), 
                    '.', 
                    MONTH(r.date), 
                    '.', 
                    YEAR(r.date)                    
                    )
                    ORDER BY r.date, r.hour, r.id SEPARATOR '<br>') as date,
                GROUP_CONCAT(SUBSTRING(DAYNAME(r.date),1,3) ORDER BY r.date, r.hour, r.id SEPARATOR '<br>') as denTydne,
                GROUP_CONCAT(DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') ORDER BY r.date, r.hour, r.id  SEPARATOR '<br>') as time,
                GROUP_CONCAT(CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'), ', ',  AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')) ORDER BY r.date, r.hour, r.id SEPARATOR '<br>') as client, 
                GROUP_CONCAT(
                    IF(s.shortcut = 'KONT',                    
                       CONCAT('<b><FONT COLOR=blue>', s.shortcut, '<FONT COLOR=black></b>'),
                       s.shortcut                       
                       ) 
                    ORDER BY r.date, r.hour, r.id SEPARATOR '<br>'
                            ) as service,
                GROUP_CONCAT(a.shortcut ORDER BY r.date, r.hour, r.id SEPARATOR '<br>') as therapist,                    
                r.date as dateFormatted               
            FROM reservations r
            
            LEFT JOIN adminLogin a ON a.id = r.personnel
            LEFT JOIN services s ON s.id = r.service

            WHERE                
                r.active = 1 AND
                r.date BETWEEN :dateFrom AND :dateTo AND
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
    $stmt->execute();
    $resultRes = $stmt->fetch(PDO::FETCH_OBJ);
    
    
    $results[$i] = new StdClass();    
    if($WeekNotes->pocet>0) {
        $results[$i]->weekstart = (new DateTime($_POST["dateFrom"]))->format("j.n") . '<br>(!)';
    } else {
        $results[$i]->weekstart = (new DateTime($_POST["dateFrom"]))->format("j.n");
    }
    $results[$i]->date = $resultRes->date;
    $results[$i]->denTydne = $resultRes->denTydne; 
    $results[$i]->time = $resultRes->time;   
    $results[$i]->client = $resultRes->client;   
    $results[$i]->service = $resultRes->service;   
    $results[$i]->therapist = $resultRes->therapist;
    $results[$i]->dateFormatted = $resultRes->dateFormatted;   
    $results[$i]->weeknote = $WeekNotes->note;
    
    if($resultRes->date == null) {
        $results[$i]->date = '-';
        $results[$i]->denTydne = '-';
        $results[$i]->time = '-';
        $results[$i]->client = '-';
        $results[$i]->service = '-';
        $results[$i]->therapist = '-';
        $results[$i]->dateFormatted = (new DateTime($_POST["dateFrom"]))->format("Y-m-d");
    }
    
    $query = "  SELECT 	                
                
                CONCAT(
                    DAY(wl.date), 
                    '.', 
                    MONTH(wl.date), 
                    '.', 
                    YEAR(wl.date)
                    ) as date,
                
                CONCAT(AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'), ', ',  AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')) as client,   
                'WL' as service,
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

echo json_encode($results);
