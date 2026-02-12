<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = 
    "SELECT
        p.service,
        p.activeWL,
        p.urgent,
        p.freqW,
        p.freqM,
        p.freqM2,
        p.slottype,
        p.note,
        NULL therapists,
        CONCAT('<small>', DATE_FORMAT(p.lastEditDate, '%d.%m.%Y'),'<br>', a.shortcut, '</small>') as lastEditDate
    FROM clientWLparam p
    LEFT JOIN adminLogin a ON a.id = p.lastEditUser
    WHERE client = :client";

$stmt = $dbh->prepare($query);
$stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);

if(count($results)>0) {	
    foreach ($results as $result) {                
        $queryTherapists = "SELECT GROUP_CONCAT(al.shortcut SEPARATOR ', ') FROM clienttherapists2 t LEFT JOIN adminLogin al ON al.id = t.therapist WHERE t.service = :service and t.client = :client";
        $stmtTherapists = $dbh->prepare($queryTherapists);
        $stmtTherapists->bindValue(":service", $result->service, PDO::PARAM_INT);
        $stmtTherapists->bindValue(":client", empty($_POST["client"]) ? NULL : $_POST["client"], PDO::PARAM_STR);
        if($stmtTherapists->execute()) {
            $result->therapists = $stmtTherapists->fetchAll(PDO::FETCH_NUM);
        }
    }
}

// dále potřebuji doplnit terapeuty ze služeb, u kterých nejsou zadány parametry čekací listiny (tzn. nemají zápis v tabulkce clintWLparam)
$pocet = count($results);

$queryTherapists2 = 
    "SELECT 
        service
    FROM clienttherapists2 t
    WHERE 
        t.client = :client AND
        t.service NOT IN (SELECT service FROM clientWLparam WHERE client = :client)";
$stmtTherapists2 = $dbh->prepare($queryTherapists2);
$stmtTherapists2->bindValue(":client", empty($_POST["client"]) ? NULL : $_POST["client"], PDO::PARAM_STR);
$stmtTherapists2->execute();
$results2 = $stmtTherapists2->fetchALL(PDO::FETCH_OBJ);

if(count($results2)>0) {	
    foreach ($results2 as $result2) {		
        $results[$pocet] = new stdClass();
        $results[$pocet]->service = $result2->service;
        $results[$pocet]->activeWL = 0;
        $results[$pocet]->urgent = 0;
        $results[$pocet]->freqW = null;
        $results[$pocet]->freqM = null;
        $results[$pocet]->freqM2 = null;

        $queryTherapists3 = "SELECT GROUP_CONCAT(al.shortcut SEPARATOR ', ') FROM clienttherapists2 t LEFT JOIN adminLogin al ON al.id = t.therapist WHERE t.service = :service and client = :client";
        $stmtTherapists3 = $dbh->prepare($queryTherapists3);
        $stmtTherapists3->bindValue(":service", $result2->service, PDO::PARAM_INT);
        $stmtTherapists3->bindValue(":client", empty($_POST["client"]) ? NULL : $_POST["client"], PDO::PARAM_STR);
        if($stmtTherapists3->execute()) {
                $results[$pocet]->therapists = $stmtTherapists3->fetchAll(PDO::FETCH_NUM);			
        }
        $pocet = $pocet + 1;
    }
}
echo json_encode($results);
