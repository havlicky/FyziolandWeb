<?php
$pageTitle = "FL - Vstup/Kontrol";

require_once "checkLogin.php";
require_once "../header.php";

$today = new DateTime();

$days = array(1 => "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle");

$alsoPassedAll = isset($_GET["alsoPassedAll"]) ? true : false;
$alsoPassed1M = isset($_GET["alsoPassed1M"]) ? true : false;
if ($alsoPassedAll) {
    $dateFrom = '1970-01-01';
} else {
    if ($alsoPassed1M) {
        $date = date("Y-m-d"); 
        $dateFrom =  date("Y-m-d", strtotime("$date - 31day"));
    } else {
    $dateFrom = (new DateTime())->format("Y-m-d");
    }
}

// dotaz na vstupní ergo vyšetření
$query = "  SELECT
                r.id,
                CONCAT(AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "')) AS name,                 
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                r.date,
                SUBSTRING(DAYNAME(r.date),1,3) as denTydne,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS timeFrom,                
                r.minute,
                a.shortcut,
                r.note COLLATE utf8_general_ci as note,
                r.internalNote COLLATE utf8_general_ci as internalNote,
                a.shortcut,
                CASE WHEN r.attName = '' THEN '' ELSE 'ANO' END AS attName, 
                CASE WHEN r.qFinished IS NULL THEN '' ELSE 'ANO' END AS qFinished, 
                IF (r.recomCheck = 1, 'checked', 'NULL') as recomCheck,
                IF (r.recomSaved = 1, 'checked', 'NULL') as recomSaved
              
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE 
                r.active = 1 AND
                r.date >= :dateFrom AND
                (r.service = 10 OR r.service = 19)
            ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $dateFrom, PDO::PARAM_STR);
$stmt->execute();
$resultsVSTUP = $stmt->fetchAll(PDO::FETCH_OBJ);

// dotaz na kontrolní ergo vyšetření
$query = "  SELECT
                r.id,
                CONCAT(AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "')) AS name,                 
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                r.date,
                SUBSTRING(DAYNAME(r.date),1,3) as denTydne,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS timeFrom,
                r.minute,                
                r.note COLLATE utf8_general_ci as note,
                r.internalNote COLLATE utf8_general_ci as internalNote,
                a.shortcut,                
                CASE WHEN r.qFinished IS NULL THEN '' ELSE 'ANO' END AS qFinished
              
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE 
                r.active = 1 AND
                r.date >= :dateFrom AND
                r.service = 12
            ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $dateFrom, PDO::PARAM_STR);
$stmt->execute();
$resultsKONTROL = $stmt->fetchAll(PDO::FETCH_OBJ);

// dotaz na konzultace ergo
$query = "  SELECT
                r.id,
                CONCAT(AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "')) AS name,                 
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                r.date,
                SUBSTRING(DAYNAME(r.date),1,3) as denTydne,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS timeFrom,
                r.minute,                
                r.note COLLATE utf8_general_ci as note,
                r.internalNote COLLATE utf8_general_ci as internalNote,
                a.shortcut,                
                CASE WHEN r.qFinished IS NULL THEN '' ELSE 'ANO' END AS qFinished
              
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE 
                r.active = 1 AND
                r.date >= :dateFrom AND
                r.service = 13
            ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $dateFrom, PDO::PARAM_STR);
$stmt->execute();
$resultsKONZULTACE = $stmt->fetchAll(PDO::FETCH_OBJ);

?>
<div class="container-fluid" id="administrace-rezervaci">
    
    <?php include "menu.php" ?>

    <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            Rezervace na <b>vstupní</b> vyšetření
        </h2>                

        <div class="row" style="margin-bottom: 10px;">
            <div class="col-lg-1 col-lg-offset-10">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassedAll" <?= $alsoPassedAll ? "checked" : "" ?>> Zpětně VŠE
                    </label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassed1M" <?= $alsoPassed1M ? "checked" : "" ?>> Zpětně -1M
                    </label>
                </div>
            </div>
        </div>                        
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-hover table-striped" id="prehled-allReservationsVSTUP" style="horizontal-align: middle">
                    <thead>
                        <tr>                                                        
                            <th class="text-center" style="vertical-align: middle;">Datum</th>
                            <th class="text-center" style="vertical-align: middle;">Den</th>                            
                            <th class="text-center" style="vertical-align: middle;">Čas</th>                            
                            <th class="text-center" style="vertical-align: middle;">Jméno</th>                                                        
                            <th class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th class="text-center" style="vertical-align: middle;">Email</th>
                            <th class="text-center" style="vertical-align: middle;">Terapeut</th>
                            <th class="text-center" style="vertical-align: middle;">Vyplněn dotazník</th>
                            <th class="text-center" style="vertical-align: middle;">Přiložená zpráva</th>
                            <th class="text-center" style="vertical-align: middle;">Zpráva OK</th>
                            <th class="text-center" style="vertical-align: middle;">Zpráva uložena</th>
                            <th class="text-center" style="vertical-align: middle;">Poznámka od klienta</th>
                            <th class="text-center" style="vertical-align: middle;">Interní poznámka</th>                            
                            <th class="text-center" style="vertical-align: middle;">Akce</th>
                            <th class="text-center" style="vertical-align: middle;">ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($resultsVSTUP) > 0): ?>
                            <?php foreach ($resultsVSTUP as $result): ?>
                                <tr data-id="<?= $result->id ?>">                                    
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j.n.Y") ?></td>                                    
                                    <td class="text-center"><?= $result->denTydne ?></td>
                                    <td class="text-center"><?= $result->timeFrom ?></td>
                                    <td class="text-left"><?= $result->name ?></td>                                    
                                    <td class="text-left"><?= $result->phone ?></td>
                                    <td class="text-left"><?= $result->email ?></td>
                                    <td class="text-center"><?= $result->shortcut ?></td>
                                    <?php 
                                        $entryDate = date_create($result->date);                                                                                
                                        $dateDiff = $entryDate ->diff($today)->format("%a");
                                        if ($result->qFinished == '' && $dateDiff <3): 
                                    ?>                                        
                                        <td class="text-center" style="background-color: red; color: white;">NEVYPLNĚN<br>Čas urgovat klienta</td>
                                    <?php else: ?>
                                        <td class="text-center"><?= $result->qFinished ?></td>
                                    <?php endif; ?>
                                    <td class="text-center"><?= $result->attName ?></td>
                                    <td data-field="recomCheck" class="text-center">
                                        <span data-role="content"><input type="checkbox" name="recomCheck" title="Doporučení/poukaz splňuje požadavky Fyziolandu" <?=$result->recomCheck?>> </span>
                                    </td>
                                    <td data-field="recomSaved" class="text-center">
                                        <span data-role="content"><input type="checkbox" name="recomSaved" title="Zpráva byla uložena do systému ordinace" <?=$result->recomSaved?>> </span>
                                    </td>                                      
                                    <td class="text-left"><?= $result->note ?></td>
                                    <td data-field="internalNote">
                                        <span data-role="content"><?= nl2br(htmlentities($result->internalNote)) ?></span>
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>                                    
                                    <td class="text-center">
                                        <a href="sendReminderQuestVstup.php?id=<?= $result->id ?>" name="sendReminder" title="Zaslat připomenutí vyplnění senzorického dotazníku (automatické připomenutí chodí 3 kalendářní dny před vstupním vyšetřením)">
                                            <span class="glyphicon glyphicon-envelope"></span>
                                        </a>
                                    </td>
                                    <td class="text-left"><?= $result->id ?></td>                                    
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="12" class="text-center">Nenalezeny žádné rezervace.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            Rezervace na <b>kontrolní</b> vyšetření
        </h2>                
       
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-hover table-striped" id="prehled-allReservationsKONTROL" style="horizontal-align: middle">
                    <thead>
                        <tr>                                                        
                            <th class="text-center" style="vertical-align: middle;">Datum</th>
                            <th class="text-center" style="vertical-align: middle;">Den</th>                            
                            <th class="text-center" style="vertical-align: middle;">Čas</th>                            
                            <th class="text-center" style="vertical-align: middle;">Jméno</th>                                                        
                            <th class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th class="text-center" style="vertical-align: middle;">Email</th>
                            <th class="text-center" style="vertical-align: middle;">Terapeut</th>
                            <th class="text-center" style="vertical-align: middle;">Vyplněn dotazník</th>
                            <th class="text-center" style="vertical-align: middle;">Interní poznámka</th>
                            <th class="text-center" style="vertical-align: middle;">Poznámka od klienta</th>
                            <th class="text-center" style="vertical-align: middle;">Akce</th>
                            <th class="text-center" style="vertical-align: middle;">ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($resultsKONTROL) > 0): ?>
                            <?php foreach ($resultsKONTROL as $result): ?>
                                <tr data-id="<?= $result->id ?>">                                    
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j.n.Y") ?></td>                                    
                                    <td class="text-center"><?= $result->denTydne ?></td>
                                    <td class="text-left"><?= $result->timeFrom ?></td>
                                    <td class="text-left"><?= $result->name ?></td>                                    
                                    <td class="text-left"><?= $result->phone ?></td>
                                    <td class="text-left"><?= $result->email ?></td>
                                    <td class="text-center"><?= $result->shortcut ?></td>
                                    <?php 
                                        $entryDate = date_create($result->date);                                                                                
                                        $dateDiff = $entryDate ->diff($today)->format("%a");
                                        if ($result->qFinished == '' && $dateDiff <2): 
                                    ?>                                        
                                        <td class="text-center" style="background-color: red; color: white;">NEVYPLNĚN<br>Čas urgovat klienta</td>
                                    <?php else: ?>
                                        <td class="text-center"><?= $result->qFinished ?></td>
                                    <?php endif; ?>
                                    <td data-field="internalNote">
                                        <span data-role="content"><?= nl2br(htmlentities($result->internalNote)) ?></span>
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>
                                    <td class="text-left"><?= $result->note ?></td>
                                    <td class="text-center">
                                        <a href="sendReminderQuestKont.php?id=<?= $result->id ?>" name="sendReminder" title="Zaslat připomenutí vyplnění senzorického dotazníku (automatické připomenutí chodí 3 kalendářní dny před kontrolním vyšetřením)">
                                            <span class="glyphicon glyphicon-envelope"></span>
                                        </a>
                                    </td>
                                    <td class="text-left"><?= $result->id ?></td>                                    
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="12" class="text-center">Nenalezeny žádné rezervace.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> 

     <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            Rezervace na <b>konzultace</b>
        </h2>                
       
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-hover table-striped" id="prehled-allReservationsKONZULTACE" style="horizontal-align: middle">
                    <thead>
                        <tr>                                                        
                            <th class="text-center" style="vertical-align: middle;">Datum</th>
                            <th class="text-center" style="vertical-align: middle;">Den</th>                            
                            <th class="text-center" style="vertical-align: middle;">Čas</th>                            
                            <th class="text-center" style="vertical-align: middle;">Jméno</th>                                                        
                            <th class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th class="text-center" style="vertical-align: middle;">Email</th>
                            <th class="text-center" style="vertical-align: middle;">Terapeut</th>                            
                            <th class="text-center" style="vertical-align: middle;">Interní poznámka</th>
                            <th class="text-center" style="vertical-align: middle;">Poznámka od klienta</th>
                            <th class="text-center" style="vertical-align: middle;">ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($resultsKONZULTACE) > 0): ?>
                            <?php foreach ($resultsKONZULTACE as $result): ?>
                                <tr data-id="<?= $result->id ?>">                                    
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j.n.Y") ?></td>                                    
                                    <td class="text-center"><?= $result->denTydne ?></td>
                                    <td class="text-left"><?= $result->timeFrom ?></td>
                                    <td class="text-left"><?= $result->name ?></td>                                    
                                    <td class="text-left"><?= $result->phone ?></td>
                                    <td class="text-left"><?= $result->email ?></td>
                                    <td class="text-center"><?= $result->shortcut ?></td>                                    
                                    <td data-field="internalNote">
                                        <span data-role="content"><?= nl2br(htmlentities($result->internalNote)) ?></span>
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>
                                    <td class="text-left"><?= $result->note ?></td>                                     
                                    <td class="text-left"><?= $result->id ?></td>                                    
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="12" class="text-center">Nenalezeny žádné rezervace.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            <b>Přehled počtu rezervací po vstupním vyšetření</b>
        </h2>
        <h4 class="text-center">
            Vstupní vyšetření max. před 60 dny
        </h4> 
        <?php

        // ******************************************************************************
        // PŘEHLED POČTU REZERVACÍ U KLIENTŮ, KTEŘÍ MĚLI NEDÁVNO VSTUPNÍ VYŠETŘENÍ
        // ******************************************************************************        

        $query = "  SELECT                        
            CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'), ', ', AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'))  AS client,                
            AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone, 
            r.date,
            al.shortcut as terapist,
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date>= CURRENT_TIMESTAMP()) AS total,
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 7 DAY) AS reservations1W,
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 DAY) AS reservations1W2W,        
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 DAY) AS reservations2W3W,
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY) AS reservations3W4W,
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 DAY) AS reservations4W5W,
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 DAY) AS reservations5W6W,
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 DAY) AS reservations6W7W,
            (SELECT COUNT(id) FROM reservations WHERE active = 1 AND service != 10 AND (email = r.email OR phone = r.phone OR client = r.client) AND date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY) AS reservations7W8W,

            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1) = cawl.client AND cawl.date>= CURRENT_TIMESTAMP()) as WLtotal,
            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1) = cawl.client AND cawl.date BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 7 DAY) as WL1W,
            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1)  = cawl.client AND cawl.date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 DAY) as WL1W2W,
            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1)  = cawl.client AND cawl.date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 DAY) as WL2W3W,
            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1)  = cawl.client AND cawl.date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY) as WL3W4W,
            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1)  = cawl.client AND cawl.date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 DAY) as WL4W5W,
            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1) = cawl.client AND cawl.date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 DAY) as WL5W6W,
            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1)  = cawl.client AND cawl.date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 DAY) as WL6W7W,
            (SELECT COUNT(cawl.id) FROM clientAvailabilityWL cawl WHERE (SELECT cl.id FROM clients cl WHERE cl.email = r.email OR cl.phone = r.phone OR cl.id = r.client LIMIT 1)  = cawl.client AND cawl.date BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY) as WL7W8W
        FROM
            reservations r            

        LEFT JOIN adminLogin al ON al.id = r.personnel
        LEFT JOIN clients c ON c.id = r.client

        WHERE             
            r.active = 1 AND r.service = 10 AND r.date BETWEEN (CURRENT_TIMESTAMP() - INTERVAL 60 DAY) AND CURRENT_TIMESTAMP()
        ";

        $stmt = $dbh->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        ?>

        <div class="row">
            <div class="col-lg-6 col-lg-offset-3">
                <table class="table table-bordered table-hover table-striped" id="tableRezervacePoVstupu" style="horizontal-align: middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="vertical-align: middle;">Klient</th>
                            <th class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th class="text-center" style="vertical-align: middle;">Terapeut</th>                            
                            <th class="text-center" style="vertical-align: middle;">Vstupní vyšetření</th>
                            <th class="text-center" style="vertical-align: middle;">Celkem</th>
                            <th class="text-center" style="vertical-align: middle;">1W</th>
                            <th class="text-center" style="vertical-align: middle;">2W</th>
                            <th class="text-center" style="vertical-align: middle;">3W</th>
                            <th class="text-center" style="vertical-align: middle;">4W</th>                            
                            <th class="text-center" style="vertical-align: middle;">5W</th>
                            <th class="text-center" style="vertical-align: middle;">6W</th>
                            <th class="text-center" style="vertical-align: middle;">7W</th>
                            <th class="text-center" style="vertical-align: middle;">8W</th>                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td class="text-left"><?= $result->client ?></td>
                                    <td class="text-center"><?= $result->phone ?></td>
                                    <td class="text-center"><?= $result->terapist ?></td>
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j. n. Y") ?></td>
                                    <?php if ($result->total == 0 && $result->WLtotal == 0): ?>
                                        <td class="text-center" style="background-color: red; color: white;"><b><?= $result->total ?>/<?= number_format($result->WLtotal, 0, ",", " ") ?></b></td>
                                    <?php else: ?>
                                        <td class="text-center"><?= $result->total ?>/<?= number_format($result->WLtotal, 0, ",", " ") ?></td>
                                    <?php endif; ?>
                                    <td class="text-center" data-order="<?= $result->reservations1W ?>"><?= number_format($result->reservations1W, 0, ",", " ")?>/<?= number_format($result->WL1W, 0, ",", " ") ?></td>
                                    <td class="text-center" data-order="<?= $result->reservations1W2W ?>"><?= number_format($result->reservations1W2W, 0, ",", " ") ?>/<?= number_format($result->WL1W2W, 0, ",", " ") ?></td>
                                    <td class="text-center" data-order="<?= $result->reservations2W3W ?>"><?= number_format($result->reservations2W3W, 0, ",", " ") ?>/<?= number_format($result->WL2W3W, 0, ",", " ") ?></td>
                                    <td class="text-center" data-order="<?= $result->reservations3W4W ?>"><?= number_format($result->reservations3W4W, 0, ",", " ") ?>/<?= number_format($result->WL3W4W, 0, ",", " ") ?></td>                                    
                                    <td class="text-center" data-order="<?= $result->reservations4W5W ?>"><?= number_format($result->reservations4W5W, 0, ",", " ") ?>/<?= number_format($result->WL4W5W, 0, ",", " ") ?></td>
                                    <td class="text-center" data-order="<?= $result->reservations5W6W ?>"><?= number_format($result->reservations5W6W, 0, ",", " ") ?>/<?= number_format($result->WL5W6W, 0, ",", " ") ?></td>
                                    <td class="text-center" data-order="<?= $result->reservations6W7W ?>"><?= number_format($result->reservations6W7W, 0, ",", " ") ?>/<?= number_format($result->WL6W7W, 0, ",", " ") ?></td>
                                    <td class="text-center" data-order="<?= $result->reservations7W8W ?>"><?= number_format($result->reservations7W8W, 0, ",", " ") ?>/<?= number_format($result->WL7W8W, 0, ",", " ") ?></td>                                                                        

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="11" class="text-center">Dotaz selhal.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $.fn.modal.Constructor.prototype.enforceFocus = function () {};

            $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();
            
            $("#prehled-allReservationsVSTUP").on("click", "a[name='sendReminder']", function(event) {
                if ( !confirm('Skutečně si přejete odeslat klientovi email s připomenutím na vyplnění senzorického dotazníku?') ) {
                    event.preventDefault();
                }
            });
            
            $("#prehled-allReservationsKONTROL").on("click", "a[name='sendReminder']", function(event) {
                if ( !confirm('Skutečně si přejete odeslat klientovi email s připomenutím na vyplnění senzorického dotazníku?') ) {
                    event.preventDefault();
                }
            });

            var vstup;
            if ($("#prehled-allReservationsVSTUP td").length > 1) {
                vstup = $("#prehled-allReservationsVSTUP").DataTable({
                    "order": [[0, "asc"]],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                    },
                    "responsive": true,                    
                    "fixedHeader": true,
                    "pageLength": 25
                });
            };
            
            var kontrol;
            if ($("#prehled-allReservationsKONTROL td").length > 1) {
                kontrol = $("#prehled-allReservationsKONTROL").DataTable({
                    "order": [[0, "asc"]],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                    },
                    "responsive": true,                    
                    "fixedHeader": true,
                    "pageLength": 10
                });
            };  
            var konzultace;
            if ($("#prehled-allReservationsKONZULTACE td").length > 1) {
                konzultace = $("#prehled-allReservationsKONZULTACE").DataTable({
                    "order": [[0, "asc"]],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                    },
                    "responsive": true,                    
                    "fixedHeader": true,
                    "pageLength": 10
                });
            };  
            
            $("#prehled-allReservationsVSTUP").on("click", "td a[data-role='editField']", function(event) {
                event.preventDefault();

                var td = $(this).closest("td");
                var prevSpan = td.find("span[data-role='content']").first();

                prevSpan.parent("a").removeAttr("href");                        

                if (td.attr("data-field") !== "mailing") {                            
                    var input = $("<input>");
                    input.attr("value", prevSpan.text());
                    input.attr("class", "form-control");
                    input.attr("style", "width: 80%;");
                    input.attr("type", "text");
                }

                prevSpan.replaceWith(input);                        
                input.focus().select();
            });
            
            $("#prehled-allReservationsVSTUP").on("blur", "td input[type='text']", function() {
                var text = $(this).val();
                var span = $("<span data-role='content'></span>");

                span.text(text);
                $(this).replaceWith(span);
            });
            
            $("#prehled-allReservationsVSTUP").on("change", "td input", function() {
                var inputType = $(this).attr("type");
                var td = $(this).closest("td");
                var field = td.attr("data-field");
                var id = $(this).closest("tr").attr("data-id");
                var value;

                if (inputType === "text") {
                    value = $(this).val();
                } else if (inputType === "checkbox") {
                    value = ($(this).prop( "checked" ) ? 1 : 0);                    
                }

                $.ajax({
                    "url": "viewVstupKontrolALLEdit.php",
                    "method": "post",
                    "data": { "id": id, "field": field, "value": value },
                    "success": function(response) {
                        //console.log(response);
                        td.addClass("success");
                        setTimeout(function() { td.removeClass("success"); }, 1000);
                    }
                });
            });
            
            $("#prehled-allReservationsKONTROL").on("click", "td a[data-role='editField']", function(event) {
                event.preventDefault();

                var td = $(this).closest("td");
                var prevSpan = td.find("span[data-role='content']").first();

                prevSpan.parent("a").removeAttr("href");                        

                if (td.attr("data-field") !== "mailing") {                            
                    var input = $("<input>");
                    input.attr("value", prevSpan.text());
                    input.attr("class", "form-control");
                    input.attr("style", "width: 80%;");
                    input.attr("type", "text");
                }

                prevSpan.replaceWith(input);                        
                input.focus().select();
            });
            
            $("#prehled-allReservationsKONTROL").on("blur", "td input[type='text']", function() {
                var text = $(this).val();
                var span = $("<span data-role='content'></span>");

                span.text(text);
                $(this).replaceWith(span);
            });
            
            $("#prehled-allReservationsKONTROL").on("change", "td input", function() {
                var inputType = $(this).attr("type");
                var td = $(this).closest("td");
                var field = td.attr("data-field");
                var id = $(this).closest("tr").attr("data-id");
                var value;

                if (inputType === "text") {
                    value = $(this).val();
                } else if (inputType === "checkbox") {
                    value = ($(this).prop( "checked" ) ? 1 : 0);                    
                }

                $.ajax({
                    "url": "viewVstupKontrolALLEdit.php",
                    "method": "post",
                    "data": { "id": id, "field": field, "value": value },
                    "success": function(response) {
                        //console.log(response);
                        td.addClass("success");
                        setTimeout(function() { td.removeClass("success"); }, 1000);
                    }
                });
            });
            
            $("#prehled-allReservationsKONZULTACE").on("click", "td a[data-role='editField']", function(event) {
                event.preventDefault();

                var td = $(this).closest("td");
                var prevSpan = td.find("span[data-role='content']").first();

                prevSpan.parent("a").removeAttr("href");                        

                if (td.attr("data-field") !== "mailing") {                            
                    var input = $("<input>");
                    input.attr("value", prevSpan.text());
                    input.attr("class", "form-control");
                    input.attr("style", "width: 80%;");
                    input.attr("type", "text");
                }

                prevSpan.replaceWith(input);                        
                input.focus().select();
            });
            
            $("#prehled-allReservationsKONZULTACE").on("blur", "td input[type='text']", function() {
                var text = $(this).val();
                var span = $("<span data-role='content'></span>");

                span.text(text);
                $(this).replaceWith(span);
            });
            
            $("#prehled-allReservationsKONZULTACE").on("change", "td input", function() {
                var inputType = $(this).attr("type");
                var td = $(this).closest("td");
                var field = td.attr("data-field");
                var id = $(this).closest("tr").attr("data-id");
                var value;

                if (inputType === "text") {
                    value = $(this).val();
                } else if (inputType === "checkbox") {
                    value = ($(this).prop( "checked" ) ? 1 : 0);                    
                }

                $.ajax({
                    "url": "viewVstupKontrolALLEdit.php",
                    "method": "post",
                    "data": { "id": id, "field": field, "value": value },
                    "success": function(response) {
                        //console.log(response);
                        td.addClass("success");
                        setTimeout(function() { td.removeClass("success"); }, 1000);
                    }
                });
            });
        
            $("#alsoPassedAll").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewVstupKontrolALL?alsoPassedAll";
                } else {
                    document.location = "viewVstupKontrolALL";
                }
            });
            
            $("#alsoPassed1M").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewVstupKontrolALL?alsoPassed1M";
                } else {
                    document.location = "viewVstupKontrolALL";
                }
            });
                        
            $.widget.bridge("tlp", $.ui.tooltip); // ošetření konfliktu s widgetem tooltip() v Bootstrap, používáme ten z jQuery UI
        });

    </script>
</div>
</body>
</html> 