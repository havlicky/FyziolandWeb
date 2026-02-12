<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$date = (new DateTime())->format("Y-m-d");

$query = "  SELECT 	
                adminLogin.id as terapist,
                adminLogin.displayName,            
                CONCAT(
                    DAY(personAvailabilityTimetable.date), 
                    '. ', 
                    MONTH(personAvailabilityTimetable.date), 
                    '. ', 
                    YEAR(personAvailabilityTimetable.date)
                    ) as date,
                personAvailabilityTimetable.date as dateformatted,
                SUBSTRING(DAYNAME(personAvailabilityTimetable.date),1,3) as DenTydne,
                CONCAT (HOUR(personAvailabilityTimetable.time ), ':', LPAD(MINUTE(personAvailabilityTimetable.time ), 2, '0')) as timeFrom,
                HOUR(personAvailabilityTimetable.time ) as hourFrom,
                CONCAT(                    
                    '<a href=\'#\',
                    data-freeslotid=\'',
                    personAvailabilityTimetable.id,
                    '\',
                    data-id=\'',
                    :id,
                    '\',
                    data-action=\'',
                    :action,
                    '\',
                    title=\'Přesunout rezervaci na tento volný termín\' data-type=\'modalakce\'>',
                    '<span class=\'glyphicon glyphicon-chevron-left\'></span>',
                    '</a>'                   
                ) as akce
            FROM `personAvailabilityTimetable`
            LEFT JOIN adminLogin ON personAvailabilityTimetable.person = adminLogin.id
            WHERE
                date >= :dateFrom AND
                date < :dateTo AND
                
                (
                    SELECT COUNT(id) 
                    from reservations AS r 
                    WHERE 
                        r.active = 1 AND 
                        r.date = personAvailabilityTimetable.date AND 
                        r.personnel = personAvailabilityTimetable.person AND 
                        CAST(CONCAT(r.hour, ':', r.minute) as time) = personAvailabilityTimetable.time
                ) = 0 AND
                EXISTS (SELECT 1 FROM relationPersonService rps WHERE rps.person = adminLogin.id AND ((rps.service = :service1) OR (:service2 IS NULL)))
            ORDER BY personAvailabilityTimetable.date ASC, personAvailabilityTimetable.time ASC, adminLogin.displayName ASC";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $date, PDO::PARAM_STR);
$stmt->bindValue(":id", $_GET["id"] , PDO::PARAM_INT);
$stmt->bindValue(":action", $_GET["action"] , PDO::PARAM_STR);
$stmt->bindValue(":dateTo", empty($_GET["dateTo"]) ? '2100-01-01' : $_GET["dateTo"], PDO::PARAM_STR);
$stmt->bindValue(":service1", empty($_GET["service"]) ? NULL : $_GET["service"], PDO::PARAM_INT);
$stmt->bindValue(":service2", empty($_GET["service"]) ? NULL : $_GET["service"], PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
