<?php
$pageTitle = "FL - REPORTY-Sloty";

require_once "checkLogin.php";
require_once "../header.php";
require_once "../php/class.settings.php";

$days = array(1 => "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle");  
$today = new DateTime();
$today = $today->format("Y-m-d");        

if (isset($_GET["selectedSlot"])) {
    $selectedSlot = $_GET["selectedSlot"];    
} else {
    $selectedSlot = 'ALL';
}

$vstupniVysetreniShow = isset($_GET["vstupniVysetreniShow"]) ? true : false;

if (isset($_GET["dateFrom"])) {
    if (!$dateFrom = (new DateTime)->createFromFormat("Y-m-d", $_GET["dateFrom"])) {
        
    }
} else {
    $dateFrom = (new DateTime()); 
}

?>

<div class="container-fluid" id="administrace-rezervaci">
    
    <?php include "menu.php" ?>                            
   
    <div class="col-lg-9 col-lg-offset-2">    
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#panel-WL" role="tab" data-toggle="tab">Čekací listina</a></li>
            <li role="presentation"><a href="#panel-vstupy" role="tab" data-toggle="tab">Vstupní vyšetření</a></li>
            <li role="presentation"><a href="#panel-statistiky" role="tab" data-toggle="tab">Statistiky</a></li>		        						
        </ul>
    </div>    
    
    <div class="tab-content">    
        <div role="tabpanel" class="tab-pane fade in" id="panel-WL">
            <div class="col-lg-9 col-lg-offset-2"> 
                <h2 class="text-center">
                    <b>Návrhy pro nové rezervace v následujících 45 kalendářních dnech</b>
                </h2>
            </div>
            <div class="row">               
                <div class="col-lg-6 col-lg-offset-6">
                    <label for="terapist">Volné sloty od </label>
                    <input class="form-control text-center" style="margin-top: 10px; display: inline-block; width: auto; font-size: 20px;" name="date" value="<?= $days[$dateFrom->format("N")] ?>, <?= $dateFrom->format("j. n. Y") ?>">
                    <input class="form-control" type="hidden" name="dateFormatted" id="dateFormatted" value="<?= $dateFrom->format("Y-m-d") ?>">
                </div>
            </div>

            <?php                    
            
            // z důvodu toho, aby mi to pro aktuální den ukazovalo pouze volné sloty pozdějí než aktuální čas a v ostatní dny všechny volné sloty nezávisle na čase
            $dateFromFormatted_temp = $dateFrom->format("Y-m-d");
            if ($dateFromFormatted_temp === $today) {
                $dateFromFormatted = $dateFrom->format("Y-m-d H:i");
            } else {
                $dateFromFormatted = $dateFrom->format("Y-m-d");
            }

            //$firstDayOfMonth = date('Y-m-01');
            //$lastDayOfMonth = date('Y-m-t');
            $reportedClients = array();

            // dotaz na neobsazené sloty
            $query = "  
                SELECT
                    pat.id as patId,
                    pat.date as date,            
                    pat.time as time,
                    al.displayName AS therapist,
                    al.id as therapistId,
                    al.isErgo,
                    al.isFyzio,
                    (SELECT 
                        res.id 
                        FROM reservations res 
                        WHERE 
                            res.personnel = pat.person AND 
                            res.date = pat.date AND 
                            DATE_FORMAT(CAST(CONCAT(res.hour, ':', res.minute, ':00') AS TIME), '%H:%i') = pat.time AND 
                            res.active = 0 AND 
                            res.deleteTimestamp >
                                (SELECT
                                    MAX(actionTimestamp)
                                FROM logWL
                                WHERE
                                    pat.date = logWL.WLdate AND
                                    pat.time = logWL.WLtime AND
                                    pat.person = logWL.therapist
                                )
                        LIMIT 1
                        ) as recentlyDeleted,
                    SUBSTRING(DAYNAME(pat.date),1,3) as denTydne
                FROM
                    personAvailabilityTimetable pat                                        

                LEFT JOIN adminLogin al ON al.id = pat.person

                WHERE                    
                    CAST(CONCAT(pat.date, ' ', pat.time) as datetime)>=:dateFrom AND
                    pat.date < CURRENT_TIMESTAMP() + INTERVAL 45 DAY AND 
                    
                    ! EXISTS(
                    SELECT
                        r.id
                    FROM
                        reservations r
                    WHERE
                        r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND CAST(
                            CONCAT(r.hour, ':', r.minute) AS TIME
                        ) = pat.time
                    LIMIT 1)
                ORDER BY date, time
            ";

            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":dateFrom", $dateFromFormatted, PDO::PARAM_STR);
            $stmt->execute();
            $VolneSloty = $stmt->fetchAll(PDO::FETCH_OBJ);               
            $pocetVolneSloty = count($VolneSloty);                                 
            ?>
            
            <div class="row">               
                <div class="col-lg-6 col-lg-offset-6">
                    <label for="terapist">Konkrétní slot </label>
                    <select name="slot" id= "slot" class="form-control" style="margin-top: 10px; display: inline-block; width: auto;">
                        <option value="ALL" <?= $selectedSlot == $volnySlot->patId ? "selected" : "" ?>>Všchny volné sloty</option>
                        <?php foreach ($VolneSloty as $volnySlot): ?>
                            <option value="<?= $volnySlot->patId ?>" <?= $selectedSlot == $volnySlot->patId ? "selected" : "" ?>><?= (new DateTime($volnySlot->date))->format("j. n. Y") . ' (' . $volnySlot->denTydne .') '.(new DateTime($volnySlot->time))->format("H:i").  $volnySlot->therapist  ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
                       
            <?php
            foreach ($VolneSloty as $volnySlot) {
                if ($volnySlot->patId == $selectedSlot || $selectedSlot == 'ALL') {
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
                    ?>

                    <!--Nadpis - volný slot - datum, čas, terapeutka--> 
                    <div class="row">
                        <div class="col-lg-9 col-lg-offset-2">
                            <h3 class="text-left">
                                <?php if ($volnySlot->recentlyDeleted > 0): ?>                                 
                                    <?= '<font color = "red">' . (new DateTime($volnySlot->date))->format("j. n. Y") . ' ('. $volnySlot->denTydne. ') '. (new DateTime($volnySlot->time))->format("H:i"). '  <b>'.  $volnySlot->therapist . '</b> </font> <font color = "black"> </font>'  ?>                                                                                                                      
                                <?php else: ?>
                                    <?= (new DateTime($volnySlot->date))->format("j. n. Y") . ' ('. $volnySlot->denTydne. ') '. (new DateTime($volnySlot->time))->format("H:i"). '  <b>'.  $volnySlot->therapist . '</b>'?>  
                                <?php endif; ?>     

                            </h3>
                        </div>
                    </div>
                    <?php         
                     // ******************************************************************
                     // DLE ČEKACÍ LISTINY A TERAPEUTA RHB PLÁNU
                     // ******************************************************************
                    $query = "               
                        SELECT                
                            c.id as clientId,
                            CONCAT(AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'),', ', AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')) AS client,
                            AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "') as phone,
                            AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') as email,
                            CASE
                                WHEN :isErgo THEN CONCAT(IF(freqMergo IS NULL, FreqWergo, FreqMergo), IF(freqMergo IS NULL, 'x za týden', 'x za měsíc')) 
                                WHEN :isFyzio THEN CONCAT(IF(freqMfyzio IS NULL, FreqWfyzio, FreqMfyzio), IF(freqMfyzio IS NULL, 'x za týden', 'x za měsíc')) 
                            END as frekvence,

                            '' as slotTypes,                        

                            (IF(:therapist IN (SELECT clienttherapists.therapist FROM clienttherapists WHERE clienttherapists.client = cawl.client AND clienttherapists.type = 'MAIN'), '<b>MAIN</b>', IF(:therapist IN (SELECT clienttherapists.therapist FROM clienttherapists WHERE clienttherapists.client = cawl.client AND clienttherapists.type = 'OTHER'),'OTHER', '-'))) as therapistType,

                            (SELECT
                                GROUP_CONCAT(al.shortcut SEPARATOR ', ')
                             FROM clienttherapists ct
                             LEFT JOIN adminLogin al ON al.id = ct.therapist
                             WHERE ct.client = cawl.client AND ct.type = 'main') as RHBtherapists,                                 

                            CONCAT(                            
                                IF((SELECT noteWL FROM clients WHERE id = c.id) IS NULL,'', CONCAT('<FONT COLOR = MediumVioletRed><b>Obecná: </b><FONT COLOR = black>',(SELECT noteWL FROM clients WHERE id = c.id),'<br>')),
                                IF(!EXISTS(SELECT note FROM weeknotes WHERE client = c.id AND firstDayOfWeek BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY)),'', CONCAT('<FONT COLOR = MediumVioletRed><b>Specifická: </b><FONT COLOR = black>',(SELECT note FROM weeknotes WHERE client = c.id AND firstDayOfWeek BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY)),'<br>')),
                                IFNULL(
                                    (SELECT
                                        GROUP_CONCAT(CONCAT('<b>', logWL.type, '.: ', '</b>', DATE_FORMAT(logWL.actionTimestamp,'%d.%m.%Y %H:%i'), ' ','<i><b>' , logWL.note, IF(logWL.rejected=1,'<FONT COLOR=red> (Odmítnuto)<FONT COLOR=black>',''),IF(logWL.utilized=1,'<FONT COLOR=green> (Využito)<FONT COLOR=black>',''),'</i></b>') SEPARATOR '<br>') 
                                        FROM logWL                 
                                        WHERE logWL.client = cawl.client AND logWL.WLdate = cawl.date AND logWL.WLtime = cawl.time AND logWL.therapist = :therapist),
                                    '') 
                             ) as notes,

                            (SELECT
                                GROUP_CONCAT(DISTINCT al.shortcut SEPARATOR ', ')
                             FROM clienttherapists ct
                             LEFT JOIN adminLogin al ON al.id = ct.therapist
                             WHERE ct.client = cawl.client AND ct.type = 'other'
                             ) as otherTherapists,

                            (SELECT                    
                               GROUP_CONCAT(CONCAT(
                                DAY(r.date), 
                                '.', 
                                MONTH(r.date), ' (', a.shortcut,')'
                                ) SEPARATOR ', ')                                                              
                                FROM reservations r                               
                                LEFT JOIN adminLogin a ON a.id = r.personnel
                                WHERE
                                    (r.client = cawl.client OR 
                                     r.email = c.email OR
                                     r.phone = c. phone    
                                    ) AND
                                    r.active = 1 AND
                                    r.date BETWEEN DATE_ADD(:date, INTERVAL -90 DAY) AND :date
                                ORDER BY
                                    r.date DESC
                                LIMIT 1) as posledniRezervace,
                            (SELECT                    
                                GROUP_CONCAT(CONCAT(DAY(r.date),'.',MONTH(r.date),  ' (', a.shortcut,')') SEPARATOR ', ') as date                                                              
                                FROM reservations r               
                                LEFT JOIN adminLogin a ON a.id = r.personnel
                                WHERE
                                    (r.client = cawl.client OR 
                                     r.email = c.email OR
                                     r.phone = c. phone    
                                    ) AND
                                    r.active = 1 AND
                                    r.date > :date                        

                                ORDER BY
                                    r.date) as budouciRezervace,
                            (SELECT IF(MAX(DATE_FORMAT(actionTimestamp,'%Y-%m-%d')) = curdate(), 1,0) FROM logWL WHERE client = cawl.client AND WLdate = :date AND WLtime = :time and therapist = :therapist) as lastLogWLToday                            

                        FROM clientAvailabilityWL cawl

                        LEFT JOIN clients c ON c.id = cawl.client            

                        WHERE
                            (CASE 
                                WHEN :isErgo = 1 THEN c.activeErgoClient = 'Y' OR c.activeErgoClient is NULL
                                WHEN :isFyzio = 1 THEN c.activeFyzioClient = 'Y' OR c.activeFyzioClient is NULL
                            END) AND

                            cawl.time = :time AND
                            cawl.date = :date AND

                            (SELECT
                                COUNT(r.id)
                                FROM reservations r
                                WHERE 
                                    (r.client = cawl.client OR 
                                     r.email = c.email OR
                                     r.phone = c. phone    
                                    ) AND
                                    r.active = 0 AND
                                    r.date = :date AND
                                    CAST(CONCAT(r.hour, ':', r.minute) as time) = :time
                            ) = 0 AND
                            (
                               (SELECT
                                    COUNT(r.id)
                                    FROM reservations r
                                    WHERE 
                                        (r.client = cawl.client OR 
                                         r.email = c.email OR
                                         r.phone = c. phone    
                                        ) AND
                                        r.active = 1 AND
                                        r.date BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY) AND
                                        (CASE 
                                            WHEN :isErgo = 1 THEN r.service = 2
                                            WHEN :isFyzio = 1 THEN (r.service = 1 OR r.service = 11)
                                        END) 
                                ) < 
                                    (CASE 
                                        WHEN :isErgo = 1 THEN c.freqWergo
                                        WHEN :isFyzio = 1 THEN c.freqWfyzio
                                    END) OR

                                (
                                    (SELECT                    
                                        COUNT(r.id)
                                        FROM reservations r
                                        WHERE 
                                            (r.client = cawl.client OR 
                                             r.email = c.email OR
                                             r.phone = c. phone    
                                            ) AND
                                            r.active = 1 AND
                                            r.date BETWEEN :firstDayOfMonth AND :lastDayOfMonth AND
                                            CASE 
                                                WHEN :isErgo = 1 THEN r.service = 2
                                                WHEN :isFyzio = 1 THEN (r.service = 1 OR r.service = 11)
                                            END 
                                    ) < 
                                        (CASE 
                                            WHEN :isErgo = 1 THEN c.freqMergo
                                            WHEN :isFyzio = 1 THEN c.freqMfyzio
                                        END) AND

                                    !EXISTS(SELECT
                                                r.id
                                            FROM reservations r
                                            WHERE 
                                                (r.client = cawl.client OR 
                                                 r.email = c.email OR
                                                 r.phone = c.phone    
                                                ) AND
                                                r.active = 1 AND
                                                r.date BETWEEN DATE_ADD(:date, INTERVAL -5 DAY) AND DATE_ADD(:date, INTERVAL 5 DAY) AND
                                                (CASE 
                                                    WHEN :isErgo = 1 THEN r.service = 2
                                                    WHEN :isFyzio = 1 THEN (r.service = 1 OR r.service = 11)
                                                END)
                                            )
                                ) OR        

                                CASE 
                                    WHEN :isErgo = 1 THEN c. freqMergo is NULL AND c.freqWergo is NULL
                                    WHEN :isFyzio = 1 THEN FALSE
                                END   

                            ) AND

                            (CASE 
                                WHEN :isErgo = 1 THEN :therapist IN (SELECT therapist FROM clienttherapists ct WHERE ct.client = cawl.client AND ct.type = 'main')
                                WHEN :isFyzio = 1 THEN TRUE 
                            END)

                        ORDER BY (SELECT actionTimestamp FROM logWL WHERE client = cawl.client AND WLdate = cawl.date AND WLtime = cawl.time AND therapist = :therapist ORDER BY actionTimestamp DESC LIMIT 1)
                        ";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":time", $volnySlot->time, PDO::PARAM_STR);
                $stmt->bindParam(":date", $volnySlot->date, PDO::PARAM_STR);
                $stmt->bindParam(":therapist", $volnySlot->therapistId, PDO::PARAM_INT);                
                $stmt->bindParam(":lastMonday", $lastMondayString, PDO::PARAM_STR);
                $stmt->bindParam(":firstDayOfMonth", $firstDayOfMonthString, PDO::PARAM_STR);
                $stmt->bindParam(":lastDayOfMonth", $lastDayOfMonthString, PDO::PARAM_STR);
                $stmt->bindParam(":isErgo", $volnySlot->isErgo, PDO::PARAM_INT);                
                $stmt->bindParam(":isFyzio", $volnySlot->isFyzio, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_OBJ);

                if (count($results) > 0) {
                    foreach ($results as $result) {
                        //echo($result->clientId);
                        $reportedClients[] = $result->clientId;
                    }            
                } 
                
                ?>               

                <div class="col-lg-9 col-lg-offset-2">
                    <h4 class="text-left">
                       DLE ČEKACÍ LISTINY A TERAPEUTA DLE RHB PLÁNU
                    </h4>
                </div>

                <div class="col-lg-9 col-lg-offset-2">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 6%;" class="text-center">Klient</th>
                                <th style="width: 8%;" class="text-center">Akce</th>  
                                <th style="width: 36%;" class="text-center">Poznámky</th>
                                <th style="width: 6%;" class="text-center">Frekvence</th>
                                <th style="width: 6%;" class="text-center">Ergoterapeut</th>
                                <th style="width: 6%;" class="text-center">Typ terapeuta</th>
                                <th style="width: 10%;" class="text-center">Předchozí rezervace 3M</th>
                                <th style="width: 10%;" class="text-center">Budoucí rezervace</th>                                                                        
                                <th style="width: 10%;" class="text-center">Kont. údaje</th>  
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($results) > 0): ?>
                                <?php foreach ($results as $result): ?>
                                    <tr data-date="<?= (new DateTime($volnySlot->date))->format("Y-m-d") ?>" data-time="<?= (new DateTime($volnySlot->time))->format("H:i") ?>" data-client="<?= $result->clientId?>" data-therapistId="<?= $volnySlot->therapistId?>" data-therapist="<?= $volnySlot->therapist?>" data-isergo="<?= $volnySlot->isErgo?>" data-isfyzio="<?= $volnySlot->isFyzio?>">                                       
                                        <?php if ( !empty($result->lastLogWLToday == 0) ): ?>    
                                            <td class="text-left"><?= $result->client ?></td>                            
                                        <?php else: ?>
                                            <td class="text-left"><FONT COLOR=blue><?= $result->client ?></FONT></td>                            
                                        <?php endif; ?>
                                        <td class="text-center">
                                            <a href="#" data-type="smsWLlog" title="Zapsat SMS s nabídkou termínu do logu">
                                                <span class="glyphicon glyphicon-phone"></span>
                                            </a>
                                            <a href="#" data-type="phoneCallWLlog" title="Zapsat telefonát s nabídkou termínu do logu">
                                                <span class="glyphicon glyphicon-earphone"></span>
                                            </a>
                                            <a href="#" data-type="phoneCallNotSuccessWLlog" title="Zapsat nedovolal jsem se do logu">
                                                <span class="glyphicon glyphicon-remove"></span>
                                            </a>                                
                                            <a href="#" data-type="emailWL_1_Log" title="Poslat nabídku termínu emailem a provést zápis do logu">
                                                <span class="glyphicon glyphicon-envelope"></span>
                                            </a>
                                            <a href="#" data-type="actionListWL" title="Doplnit komentář k zadané akci">
                                                <span class="glyphicon glyphicon-plus"></span>
                                            </a>
                                            <a href="#" data-type="actionListWLhistory" title="Zobrazit historii komunikace s klientem">
                                                <span class="glyphicon glyphicon-th-list"></span>
                                            </a>
                                        </td> 
                                        <td class="text-left"><?= $result->notes ?></td> 
                                        <td class="text-center"><?= '<b>'. $result->frekvence  . '</b><br> ('. $result->slotTypes . ')'?></td>                    
                                        <td class="text-center"><?= '<b>'. $result->RHBtherapists  . '</b><br> ('. $result->otherTherapists . ')'?></td>                    
                                        <td class="text-center"><?= $result->therapistType ?></td>
                                        <td class="text-center"><?= $result->posledniRezervace ?></td>
                                        <td class="text-center"><?= $result->budouciRezervace ?></td>                                                                            
                                        
                                        <td class="text-center"><?= $result->phone . '<br>' .$result->email ?></td>                                        
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                    <tr><td colspan="9" class="text-center">Nebyly nalezeny žádné možnosti rezervací.</td></tr>    
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php } ?>
            <?php } ?>
        </div>
        
        <div role="tabpanel" class="tab-pane fade" id="panel-vstupy">                                                

            <div class="col-lg-10 col-lg-offset-1">
                <h2 class="text-center">
                    <b>Přehled počtu rezervací po vstupním vyšetření</b>
                </h2>
                <h4 class="text-center">
                    Vstupní vyšetření max. před 60 dny
                </h4> 
                
                <div class="row">
                    <div class="col-lg-6 col-lg-offset-6">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="vstupniVysetreniShow" <?= $vstupniVysetreniShow ? "checked" : "" ?>> Zobraz report
                            </label>
                        </div>
                    </div>  
                </div>
                
                <?php
            
                if ($vstupniVysetreniShow == true) {
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
                    (c.activeErgoClient = 'Y' OR c.activeErgoClient is NULL) AND
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
            <?php } ?>
            </div>

        </div>

    <div role="tabpanel" class="tab-pane fade" id="panel-statistiky">
            <?php

            $query = " (
    SELECT
        `al`.`displayName` AS `displayName`,
		`al`.`orderRank` AS `orderRank`,
        (
        SELECT
            COUNT(`pat`.`id`)
        FROM
            `fyziolandc`.`personAvailabilityTimetable` `pat`
        WHERE
            `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND ! EXISTS(
            SELECT
                `r`.`id`
            FROM
                `fyziolandc`.`reservations` `r`
            WHERE
                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                ) = `pat`.`time`
            LIMIT 1
        )) AS `availableSlots1W`,(
            SELECT
                COUNT(`pat`.`id`)
            FROM
                `fyziolandc`.`personAvailabilityTimetable` `pat`
            WHERE
                `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND ! EXISTS(
                SELECT
                    `r`.`id`
                FROM
                    `fyziolandc`.`reservations` `r`
                WHERE
                    `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                        CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                    ) = `pat`.`time`
                LIMIT 1
            )) AS `availableSlots1W2W`,(
                SELECT
                    COUNT(`pat`.`id`)
                FROM
                    `fyziolandc`.`personAvailabilityTimetable` `pat`
                WHERE
                    `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND ! EXISTS(
                    SELECT
                        `r`.`id`
                    FROM
                        `fyziolandc`.`reservations` `r`
                    WHERE
                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                        ) = `pat`.`time`
                    LIMIT 1
                )) AS `availableSlots2W3W`,(
                    SELECT
                        COUNT(`pat`.`id`)
                    FROM
                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                    WHERE
                        `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND ! EXISTS(
                        SELECT
                            `r`.`id`
                        FROM
                            `fyziolandc`.`reservations` `r`
                        WHERE
                            `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                            ) = `pat`.`time`
                        LIMIT 1
                    )) AS `availableSlots3W4W`,(
                        SELECT
                            COUNT(`pat`.`id`)
                        FROM
                            `fyziolandc`.`personAvailabilityTimetable` `pat`
                        WHERE
                            `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND ! EXISTS(
                            SELECT
                                `r`.`id`
                            FROM
                                `fyziolandc`.`reservations` `r`
                            WHERE
                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                ) = `pat`.`time`
                            LIMIT 1
                        )) AS `availableSlots1M`,(
                            SELECT
                                COUNT(`pat`.`id`)
                            FROM
                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                            WHERE
                                `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND ! EXISTS(
                                SELECT
                                    `r`.`id`
                                FROM
                                    `fyziolandc`.`reservations` `r`
                                WHERE
                                    `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                        CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                    ) = `pat`.`time`
                                LIMIT 1
                            )) AS `availableSlots4W5W`,(
                                SELECT
                                    COUNT(`pat`.`id`)
                                FROM
                                    `fyziolandc`.`personAvailabilityTimetable` `pat`
                                WHERE
                                    `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND ! EXISTS(
                                    SELECT
                                        `r`.`id`
                                    FROM
                                        `fyziolandc`.`reservations` `r`
                                    WHERE
                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                        ) = `pat`.`time`
                                    LIMIT 1
                                )) AS `availableSlots5W6W`,(
                                    SELECT
                                        COUNT(`pat`.`id`)
                                    FROM
                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                    WHERE
                                        `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND ! EXISTS(
                                        SELECT
                                            `r`.`id`
                                        FROM
                                            `fyziolandc`.`reservations` `r`
                                        WHERE
                                            `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                            ) = `pat`.`time`
                                        LIMIT 1
                                    )) AS `availableSlots6W7W`,(
                                        SELECT
                                            COUNT(`pat`.`id`)
                                        FROM
                                            `fyziolandc`.`personAvailabilityTimetable` `pat`
                                        WHERE
                                            `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY AND ! EXISTS(
                                            SELECT
                                                `r`.`id`
                                            FROM
                                                `fyziolandc`.`reservations` `r`
                                            WHERE
                                                `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                    CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                ) = `pat`.`time`
                                            LIMIT 1
                                        )) AS `availableSlots7W8W`,(
                                            SELECT
                                                COUNT(`pat`.`id`)
                                            FROM
                                                `fyziolandc`.`personAvailabilityTimetable` `pat`
                                            WHERE
                                                `pat`.`person` = `al`.`id` AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY AND ! EXISTS(
                                                SELECT
                                                    `r`.`id`
                                                FROM
                                                    `fyziolandc`.`reservations` `r`
                                                WHERE
                                                    `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                        CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                    ) = `pat`.`time`
                                                LIMIT 1
                                            )) AS `availableSlots2M`
                                            FROM
                                                `fyziolandc`.`adminLogin` `al`
                                            WHERE
                                                `al`.`active` = 1 AND `al`.`indiv` = 1)
                                            UNION
                                                (
                                                SELECT
                                                    'ERGO ALL' AS `ERGO ALL`,
													'99' as orderRank,
                                                    (
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots1W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots1W2W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots2W3W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots3W4W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots1M`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots4W5W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots5W6W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots6W7W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots7W8W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots2M`
                                                FROM
                                                    `fyziolandc`.`adminLogin` `al`
                                                WHERE
                                                    `al`.`active` = 1 AND `al`.`indiv` = 1 AND `al`.`isErgo` = 1)
                                                UNION
                                                (
                                                SELECT
                                                    'FYZIO ALL' AS `FYZIO ALL`,
                                                    '5' as orderRank,
                                                    (
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots1W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots1W2W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots2W3W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots3W4W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots1M`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots4W5W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots5W6W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots6W7W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots7W8W`,(
                                                    SELECT
                                                        COUNT(`pat`.`id`)
                                                    FROM
                                                        `fyziolandc`.`personAvailabilityTimetable` `pat`
                                                    WHERE
                                                        `pat`.`person` IN(
                                                        SELECT
                                                            `fyziolandc`.`adminLogin`.`id`
                                                        FROM
                                                            `fyziolandc`.`adminLogin`
                                                        WHERE
                                                            `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                    ) AND `pat`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY AND ! EXISTS(
                                                    SELECT
                                                        `r`.`id`
                                                    FROM
                                                        `fyziolandc`.`reservations` `r`
                                                    WHERE
                                                        `r`.`active` = 1 AND `r`.`personnel` = `pat`.`person` AND `r`.`date` = `pat`.`date` AND CAST(
                                                            CONCAT(`r`.`hour`, ':', `r`.`minute`) AS TIME
                                                        ) = `pat`.`time`
                                                    LIMIT 1
                                                )) AS `availableSlots2M`
                                                FROM
                                                    `fyziolandc`.`adminLogin` `al`
                                                WHERE
                                                    `al`.`active` = 1 AND `al`.`indiv` = 1 AND `al`.`isFyzio` = 1)
                                                ORDER BY
                                                    orderRank ASC";

            $stmt = $dbh->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
            ?>                         
            
            <div class="col-lg-10 col-lg-offset-1">
                <h2 class="text-center">
                    Počet VOLNÝCH slotů
                </h2>                                       
                
                <div class="row">
                    <div class="col-lg-6 col-lg-offset-3">
                        <table class="table table-bordered table-hover table-striped" id="TableVolneVstupy" style="horizontal-align: middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="vertical-align: middle;">Pracovník</th>                                    
                                    <th class="text-center" style="vertical-align: middle;">1W</th>
                                    <th class="text-center" style="vertical-align: middle;">2W</th>
                                    <th class="text-center" style="vertical-align: middle;">3W</th>
                                    <th class="text-center" style="vertical-align: middle;">4W</th>
                                    <th style="font-weight: bold; font-size:120%;" class="text-center" style="vertical-align: middle;">1M</th>
                                    <th class="text-center" style="vertical-align: middle;">5W</th>
                                    <th class="text-center" style="vertical-align: middle;">6W</th>
                                    <th class="text-center" style="vertical-align: middle;">7W</th>
                                    <th class="text-center" style="vertical-align: middle;">8W</th>
                                    <th style="font-weight: bold; font-size:120%;" class="text-center" style="vertical-align: middle;">2M</th>   
                                    <th class="text-center" style="vertical-align: middle;">Pořadí</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($results) > 0): ?>
                                    <?php foreach ($results as $result): ?>
                                        <?php if ($result->displayName == 'ERGO ALL' || $result->displayName == 'FYZIO ALL'): ?>
                                            <tr data-id="<?= $result->id ?>" style="background-color: gray; color: white; font-weight: bold;">
                                        <?php else: ?>
                                             <tr data-id="<?= $result->id ?>">   
                                        <?php endif; ?>
                                            <td class="text-left"><?= $result->displayName ?></td>                                            
                                            <td class="text-center" data-order="<?= $result->availableSlots1W ?>"><?= number_format($result->availableSlots1W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->availableSlots1W2W ?>"><?= number_format($result->availableSlots1W2W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->availableSlots2W3W ?>"><?= number_format($result->availableSlots2W3W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->availableSlots3W4W ?>"><?= number_format($result->availableSlots3W4W, 0, ",", " ") ?></td>
                                            <td style="font-weight: bold; font-size:120%;" class="text-center" data-order="<?= $result->availableSlots1M ?>"><?= number_format($result->availableSlots1M, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->availableSlots4W5W ?>"><?= number_format($result->availableSlots4W5W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->availableSlots5W6W ?>"><?= number_format($result->availableSlots5W6W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->availableSlots6W7W ?>"><?= number_format($result->availableSlots6W7W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->availableSlots7W8W ?>"><?= number_format($result->availableSlots7W8W, 0, ",", " ") ?></td>                                    
                                            <td style="font-weight: bold; font-size:120%;" class="text-center" data-order="<?= $result->availableSlots2M ?>"><?= number_format($result->availableSlots2M, 0, ",", " ") ?></td>                                    
                                            <td style="color: gray; font-size:50%;" class="text-center"><?= $result->orderRank ?></td>
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
        
            <?php
        
            
            $query = "  (
            SELECT
                `al`.`displayName` AS `displayName`,
                `al`.`orderRank` AS `orderRank`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 7 DAY) AS `usedSlots1W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 DAY) AS `usedSlots1W2W`,        
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 DAY) AS `usedSlots2W3W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY) AS `usedSlots3W4W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY) AS `usedSlots1M`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 DAY) AS `usedSlots4W5W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 DAY) AS `usedSlots5W6W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 DAY) AS `usedSlots6W7W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY) AS `usedSlots7W8W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` = `al`.`id` AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY) AS `usedSlots2M`

            FROM
                `adminLogin` `al`
            WHERE
                `al`.`active` = 1 AND `al`.`indiv` = 1
            ORDER BY
                `al`.`displayName`)

            UNION

            (SELECT
                'ERGO ALL' AS `displayName`,
                '98' as orderRank,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 7 DAY) AS `usedSlots1W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 DAY) AS `usedSlots1W2W`,        
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 DAY) AS `usedSlots2W3W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            )AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY) AS `usedSlots3W4W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY) AS `usedSlots1M`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 DAY) AS `usedSlots4W5W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 DAY) AS `usedSlots5W6W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 DAY) AS `usedSlots6W7W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY) AS `usedSlots7W8W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isErgo` = 1
                                                            )AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY) AS `usedSlots2M`

            FROM
                `adminLogin` `al`
            WHERE
                `al`.`active` = 1 AND `al`.`indiv` = 1
            )

        UNION

            (SELECT
                'FYZIO ALL' AS `displayName`,
                '5' as orderRank,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 7 DAY) AS `usedSlots1W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 7 DAY AND CURRENT_TIMESTAMP() + INTERVAL 14 DAY) AS `usedSlots1W2W`,        
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 14 DAY AND CURRENT_TIMESTAMP() + INTERVAL 21 DAY) AS `usedSlots2W3W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            )AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 21 DAY AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY) AS `usedSlots3W4W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() AND CURRENT_TIMESTAMP() + INTERVAL 28 DAY) AS `usedSlots1M`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 35 DAY) AS `usedSlots4W5W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 35 DAY AND CURRENT_TIMESTAMP() + INTERVAL 42 DAY) AS `usedSlots5W6W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 42 DAY AND CURRENT_TIMESTAMP() + INTERVAL 49 DAY) AS `usedSlots6W7W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            ) AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 49 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY) AS `usedSlots7W8W`,
                (SELECT COUNT(`r`.`id`) FROM `reservations` `r` WHERE `r`.`active` = 1 AND `r`.`personnel` IN(
                                                                SELECT
                                                                    `fyziolandc`.`adminLogin`.`id`
                                                                FROM
                                                                    `fyziolandc`.`adminLogin`
                                                                WHERE
                                                                    `fyziolandc`.`adminLogin`.`isFyzio` = 1
                                                            )AND `r`.`date` BETWEEN CURRENT_TIMESTAMP() + INTERVAL 28 DAY AND CURRENT_TIMESTAMP() + INTERVAL 56 DAY) AS `usedSlots2M`

            FROM
                `adminLogin` `al`
            WHERE
                `al`.`active` = 1 AND `al`.`indiv` = 1
            ) ORDER BY orderRank

        ";

            $stmt = $dbh->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
            ?>
        
            <div class="col-lg-10 col-lg-offset-1">
                <h2 class="text-center">
                    Počet OBSAZENÝCH slotů
                </h2>                        

                <div class="row">
                    <div class="col-lg-6 col-lg-offset-3">
                        <table class="table table-bordered table-hover table-striped" id="tableObsazeneSloty" style="horizontal-align: middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="vertical-align: middle;">Pracovník</th>                                    
                                    <th class="text-center" style="vertical-align: middle;">1W</th>
                                    <th class="text-center" style="vertical-align: middle;">2W</th>
                                    <th class="text-center" style="vertical-align: middle;">3W</th>
                                    <th class="text-center" style="vertical-align: middle;">4W</th>
                                    <th style="font-weight: bold; font-size:120%;" class="text-center" style="vertical-align: middle;">1M</th>
                                    <th class="text-center" style="vertical-align: middle;">5W</th>
                                    <th class="text-center" style="vertical-align: middle;">6W</th>
                                    <th class="text-center" style="vertical-align: middle;">7W</th>
                                    <th class="text-center" style="vertical-align: middle;">8W</th>
                                    <th style="font-weight: bold; font-size:120%;" class="text-center" style="vertical-align: middle;">2M</th>   
                                    <th class="text-center" style="vertical-align: middle;">Pořadí</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($results) > 0): ?>
                                    <?php foreach ($results as $result): ?>
                                        <?php if ($result->displayName == 'ERGO ALL' || $result->displayName == 'FYZIO ALL'): ?>
                                            <tr data-id="<?= $result->id ?>" style="background-color: gray; color: white; font-weight: bold;">
                                        <?php else: ?>
                                             <tr data-id="<?= $result->id ?>">   
                                        <?php endif; ?>
                                            <td class="text-left"><?= $result->displayName ?></td>                                            
                                            <td class="text-center" data-order="<?= $result->usedSlots1W ?>"><?= number_format($result->usedSlots1W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots1W2W ?>"><?= number_format($result->usedSlots1W2W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots2W3W ?>"><?= number_format($result->usedSlots2W3W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots3W4W ?>"><?= number_format($result->usedSlots3W4W, 0, ",", " ") ?></td>
                                            <td style="font-weight: bold; font-size:120%;" class="text-center" data-order="<?= $result->usedSlots1M ?>"><?= number_format($result->usedSlots1M, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots4W5W ?>"><?= number_format($result->usedSlots4W5W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots5W6W ?>"><?= number_format($result->usedSlots5W6W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots6W7W ?>"><?= number_format($result->usedSlots6W7W, 0, ",", " ") ?></td>
                                            <td class="text-center" data-order="<?= $result->usedSlots7W8W ?>"><?= number_format($result->usedSlots7W8W, 0, ",", " ") ?></td>                                    
                                            <td style="font-weight: bold; font-size:120%;" class="text-center" data-order="<?= $result->usedSlots2M ?>"><?= number_format($result->usedSlots2M, 0, ",", " ") ?></td>                                    
                                            <td style="color: gray; font-size:50%;" class="text-center"><?= $result->orderRank ?></td>
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
        </div>        
    </div>    
    
    <!-- modální dialog pro zobrazení akcí na dané klientovi, datu a čase čekací listiny-->
    <div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-actionListWL-modal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Přehled provedených akcí s nabídkami volného termínu</h3>
                </div>
                <div class="modal-body text-center">                    
                    <table id="TableactionListWL" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 20%;" class="text-center">Okamžik</th>     
                                <th style="width: 10%;" class="text-center">Typ</th> 
                                <th style="width: 10%;" class="text-center">Terapeut</th>                                 
                                <th style="width: 40%;" class="text-center">Poznámka</th>     
                                <th style="width: 10%;" class="text-center">Využito</th>
                                <th style="width: 10%;" class="text-center">Odmítnuto</th>
                                <th style="width: 20%;" class="text-center">Akce</th>                                                                     
                            </tr>
                        </thead>
                        <tbody>
                            <tr>                                
                            </tr>
                        </tbody>
                    </table>
                </div> 
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>                    
                </div>

            </div> 
        </div>            
    </div>    
    
    <!-- modální dialog pro zobrazení historie všech akcí s klietem na čekací listině-->
    <div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-actionListWLhistory-modal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Přehled provedených akcí s nabídkami volného termínu</h3>
                </div>
                <div class="modal-body text-center">                    
                    <table id="TableactionListWLhistory" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 10%;" class="text-center">Okamžik pobídky</th>     
                                <th style="width: 10%;" class="text-center">Nabízený termín</th>     
                                <th style="width: 10%;" class="text-center">Typ</th>
                                <th style="width: 10%;" class="text-center">Terapeut</th>
                                <th style="width: 40%;" class="text-center">Poznámka</th>     
                                <th style="width: 10%;" class="text-center">Využito</th>
                                <th style="width: 10%;" class="text-center">Odmítnuto</th>                                
                            </tr>
                        </thead>
                        <tbody>
                            <tr>                                
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>                    
                </div>
            </div>
        </div>
    </div>
    
    <!-- modální dialog pro zobrazení QR kódu pro načtení individuální SMS-->
    <div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-qrsms-modal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <input type="hidden" id="date" name="date" value="">
                <input type="hidden" id="time" name="time" value="">
                <input type="hidden" id="client" name="client" value="">
                <input type="hidden" id="therapist" name="therapist" value="">
                <input type="hidden" id="hash" name="hash" value="">
                <input type="hidden" id="isergo" name="isergo" value="">
                <input type="hidden" id="isfyzio" name="isfyzio" value="">
                
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">QR kód pro načtení individuální SMS</h3>
                </div>
                <div class="modal-body text-center">
                    <img src="" alt="QR kód pro načtení individuální SMS" id="imgQRsms" width="200" />
                    <table class="table">
                        <thead>
                            <tr>                                        
                                <th style="width: 15%" class="text-center">Klient</th>
                                <th style="width: 15%" class="text-center">Tel. číslo</th>
                                <th style="width: 70%"class="text-center">Text SMS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="qrSMSClient" class="text-center">Klient</td>
                                <td id="qrSMSPhone" class="text-center">Telefonní číslo</td>
                                <td id="qrSMSFinalText" class="text-left">Text SMS</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="SMSzapisLog" name = "SMSzapisLog">Zapsat do logu</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>                    
                </div>
            </div>
        </div>
    </div>
    

    <script>
        $(document).ready(function () {
           var vstupniVysetreni = "<?= $vstupniVysetreniShow?>";
           if (vstupniVysetreni === true){
                $('.nav-tabs a[href="#panel-vstupy"]').click();
            } else {
                $('.nav-tabs a[href="#panel-WL"]').click();
            }
           
            $.fn.modal.Constructor.prototype.enforceFocus = function () {};

            $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();                       
            
            $("input[name='date']").datepicker({
                altField: "#dateFormatted",
                altFormat: "yy-mm-dd",
                dayNames: ["neděle", "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota"],
                dayNamesMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
                firstDay: 1,
                dateFormat: "DD, d. m. yy",
                monthNames: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"],
                minDate:new Date()
                
            });

            $("input[name='date']").change(function() {               
                    window.location = "reports_old.php?dateFrom=" + $("#dateFormatted").val();
            });
        
            $("body").on("click", "a[data-type='actionListWL']", function(event) {						                                                
                var tr = $(this).closest("tr");            
                var time = tr.attr("data-time"); 
                var date = tr.attr("data-date"); 
                var client = tr.attr("data-client");                                 

                $.ajax({
                    url: "getActionListWL.php",
                    method: "post",
                    dataType: "json",
                    data: {"client": client, "date": date, "time": time},
                    success: function (response) {                        
                        $("#TableactionListWL tbody tr").remove();
                        if (response.length > 0) {
                            $.each(response, function(i, obj) {
                                var tr = $("<tr data-id=" + response[i].id + "></tr>");                                 
                                tr.append( $("<td style = 'text-align: center'>" + response[i].date + "</td>") );
                                tr.append( $("<td style = 'text-align: center'>" + response[i].type + "</td>") );                                
                                tr.append( $("<td style = 'text-align: center'>" + response[i].displayName + "</td>") );                                
                                tr.append( $("<td style = 'text-align: left' data-field='note'>" + "<span data-role='content'> " + response[i].note + "</span>" + " <a href='#' style='float: right;' data-role='editField'><span class='glyphicon glyphicon-pencil'></span></a></td>") );
                                tr.append( $('<td data-field="utilized" ><input type="checkbox" name="utilized" '+ response[i].utilized + '></td>') );
                                tr.append( $('<td data-field="rejected" ><input type="checkbox" name="rejected" '+ response[i].rejected + '></td>') );                                
                                tr.append( $("<td style = 'text-align: center'>" + response[i].akce + "</td>") );                                
                                $("#TableactionListWL tbody").append(tr);
                                $("#zobrazit-actionListWL-modal").modal("show"); 
                            });
                        } else {
                            var tr = $("<tr></tr>");
                            var td = $("<td colspan='6' class='text-center'></td>");
                            td.html("Nebyly nalezeny žádné akce.");
                            tr.append(td);
                            $("#TableactionListWL tbody").append(tr);
                            $("#zobrazit-actionListWL-modal").modal("show"); 
                        }
                    },
                    beforeSend: function() {
                        var tr = $("<tr></tr>");
                        var td = $("<td colspan='6'></td>");
                        td.html("Probíhá načítání záznamů.");
                        tr.append(td);
                        $("#TableactionListWL tbody").append(tr);
                    }
                });
            });
            
            $("body").on("click", "a[data-type='actionListWLhistory']", function(event) {						                                                                
                var tr = $(this).closest("tr");            
                var time = tr.attr("data-time"); 
                var date = tr.attr("data-date"); 
                var client = tr.attr("data-client");                                 

                $.ajax({
                    url: "getActionListWLhistory.php",
                    method: "post",
                    dataType: "json",
                    data: {"client": client, "date": date, "time": time},
                    success: function (response) {                        
                        $("#TableactionListWLhistory tbody tr").remove();
                        if (response.length > 0) {
                            $.each(response, function(i, obj) {
                                var tr = $("<tr data-id=" + response[i].id + "></tr>");                                 
                                tr.append( $("<td style = 'text-align: center'>" + response[i].date + "</td>") );
                                tr.append( $("<td style = 'text-align: center'>" + response[i].WLdate + '<br>' + response[i].WLtime +"</td>") );
                                tr.append( $("<td style = 'text-align: center'>" + response[i].type + "</td>") );    
                                tr.append( $("<td style = 'text-align: center'>" + response[i].displayName + "</td>") );                                
                                tr.append( $("<td style = 'text-align: left'>" + response[i].note + "</td>") );                                
                                tr.append( $('<td><input type="checkbox" name="utilized" '+ response[i].utilized + '></td>') );
                                tr.append( $('<td><input type="checkbox" name="rejected" '+ response[i].rejected + '></td>') );                                                                
                                $("#TableactionListWLhistory tbody").append(tr);
                                $("#zobrazit-actionListWLhistory-modal").modal("show"); 
                            });
                        } else {
                            var tr = $("<tr></tr>");
                            var td = $("<td colspan='6' class='text-center'></td>");
                            td.html("Nebyly nalezeny žádné předchozí akce.");
                            tr.append(td);
                            $("#TableactionListWLhistory tbody").append(tr);
                            $("#zobrazit-actionListWLhistory-modal").modal("show"); 
                        }
                    },
                    beforeSend: function() {
                        var tr = $("<tr></tr>");
                        var td = $("<td colspan='6'></td>");
                        td.html("Probíhá načítání záznamů.");
                        tr.append(td);
                        $("#TableactionListWLhistory tbody").append(tr);
                    }
                });
            });
            
            $("#TableactionListWL").on("click", "td a[data-role='editField']", function(event) {
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
            
            $("#TableactionListWL").on("blur", "td input[type='text']", function() {
                var text = $(this).val();
                var span = $("<span data-role='content'></span>");

                span.text(text);
                $(this).replaceWith(span);
            });
            
            $("#TableactionListWL").on("click", "td a[data-role='editField']", function(event) {
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
            
            $("#TableactionListWL").on("blur", "td input[type='text']", function() {
                var text = $(this).val();
                var span = $("<span data-role='content'></span>");

                span.text(text);
                $(this).replaceWith(span);
            });

            $("#TableactionListWL").on("change", "td input", function() {
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
                    "url": "getActionListWLedit.php",
                    "method": "post",
                    "data": { "id": id, "field": field, "value": value },
                    "success": function(response) {
                        //console.log(response);
                        td.addClass("success");
                        setTimeout(function() { td.removeClass("success"); }, 1000);
                    }
                });
            });
        
           $("body").on("click", "a[data-type='phoneCallWLlog']", function(event) {						                                
                if (confirm('Zadat uskutečněný hovor?')) {                                  
                    var tr = $(this).closest("tr");            
                    var time = tr.attr("data-time"); 
                    var date = tr.attr("data-date"); 
                    var client = tr.attr("data-client");  
                    var therapist = tr.attr("data-therapist");
                    var therapistId = tr.attr("data-therapistId"); 

                    $.ajax({
                        url: "WLlogAction.php",
                        method: "post",
                        dataType: "text",
                        data: {"client": client, "date": date, "time": time, "therapistId": therapistId, "type": "phoneCall"},
                        success: function (response) {
                            //alert('Zápis telefonního hovoru proveden');
                            location.reload();
                        }
                    });
                }
             });
             
             $("body").on("click", "a[data-type='phoneCallNotSuccessWLlog']", function(event) {						                                
                if (confirm('Zadat hovor, který klient nezvedl?')) {                                  
                    var tr = $(this).closest("tr");            
                    var time = tr.attr("data-time"); 
                    var date = tr.attr("data-date"); 
                    var client = tr.attr("data-client");  
                    var therapistId = tr.attr("data-therapistId");   

                    $.ajax({
                        url: "WLlogAction.php",
                        method: "post",
                        dataType: "text",
                        data: {"client": client, "date": date, "time": time, "therapistId":therapistId, "type": "phoneCallNotSuccess"},
                        success: function (response) {
                            //alert('Zápis telefonního hovoru proveden');
                            location.reload();
                        }
                    });
                }
             });
             
            //funkce na generování hash pro SMS s nabídkou rezervace (nelze generovat až v php, jelikož se nejprve zobrazí modal s QR a pak teprve zápis do logu 
            function randomString(len, an) {
              an = an && an.toLowerCase();
              var str = "",
                i = 0,
                min = an == "a" ? 10 : 0,
                max = an == "n" ? 10 : 62;
              for (; i++ < len;) {
                var r = Math.random() * (max - min) + min << 0;
                str += String.fromCharCode(r += r > 9 ? r < 36 ? 55 : 61 : 48);
              }
              return str;
            } 
            
            $("body").on("click", "a[data-type='smsWLlog']", function(event) {						                                                                                                                   
                var tr = $(this).closest("tr");            
                var time = tr.attr("data-time"); 
                var date = tr.attr("data-date"); 
                var client = tr.attr("data-client");        
                var therapist = tr.attr("data-therapist");
                var therapistId = tr.attr("data-therapistId");
                var isergo = tr.attr("data-isergo");
                var isfyzio = tr.attr("data-isfyzio");
                
                var hash = randomString(5);                
                
                /*
                alert(time);
                alert(date);
                alert(client);
                alert(therapistId);                
                */
               
                $.ajax({
                    url: "getQRsmsWL.php",
                    method: "post",
                    dataType: "json",
                    data: {"client": client, "date": date, "time": time, "therapist": therapist, "therapistId": therapistId, "hash":hash, "isergo" : isergo, "isfyzio" : isfyzio},
                    success: function (response) {
                        $("#imgQRsms").attr("src", "data:image/png;base64," + response.img);
                        $("#qrSMSClient").html(response.client);
                        $("#qrSMSPhone").html(response.phone);
                        $("#qrSMSFinalText").html(response.smsText);
                        $("#date").val(date);
                        $("#time").val(time);
                        $("#client").val(client);
                        $("#therapist").val(therapistId);
                        $("#hash").val(hash);
                        $("#isergo").val(isergo);
                        $("#isfyzio").val(isfyzio);
                        $("#zobrazit-qrsms-modal").modal("show");                                                                
                    }
                });
            });
            
            $("#SMSzapisLog").click(function() {              
                $("#zobrazit-qrsms-modal").modal("hide");  
                var date = $("#date").val();
                var time = $("#time").val();
                var client = $("#client").val();  
                var therapistId = $("#therapist").val();  
                var hash = $("#hash").val();                 
                var isergo = $("#isergo").val();
                var isfyzio = $("#isfyzio").val();
                var service;
                
                if(isergo == 1) {service = 2;}      //pravidelná lekce ERGO
                if(isfyzio ==1) {service = 11;}     //fyzioterapie pro děti
                     
                //alert(service);
               
                $.ajax({
                    url: "WLlogAction.php",
                    method: "post",
                    dataType: "text",
                    data: {"client": client, "date": date, "time": time, "therapistId": therapistId, "type": "sms", "hash":hash, "service" : service},
                    success: function (response) {
                        //alert('Zápis poslané SMS proveden');
                        location.reload();
                    }
                });                
             }); 
             
             $("body").on("click", "a[data-type='emailWL_1_Log']", function(event) {						                                
                if (confirm('Poslat email a zaslat do logu?')) {                  
                    var tr = $(this).closest("tr");            
                    var time = tr.attr("data-time"); 
                    var date = tr.attr("data-date"); 
                    var client = tr.attr("data-client");
                    var therapist = tr.attr("data-therapist");
                    var therapistId = tr.attr("data-therapistId");

                    $.ajax({
                        url: "WLlogAction.php",
                        method: "post",
                        dataType: "text",
                        data: {"client": client, "date": date, "time": time, "type": "Email_1", "therapistId": therapistId, "therapist": therapist },
                        success: function (response) {
                            //alert('Zápis o poslaném emailu proveden');
                            location.reload();
                        }
                    });
                }
             }); 
             
             $("body").on("click", "a[data-type='emailWL_2_Log']", function(event) {						                                
                if (confirm('Poslat email a zaslat do logu?')) {                  
                    var tr = $(this).closest("tr");            
                    var time = tr.attr("data-time"); 
                    var date = tr.attr("data-date"); 
                    var client = tr.attr("data-client");
                    var therapist = tr.attr("data-therapist");
                    var therapistId = tr.attr("data-therapistId");

                    $.ajax({
                        url: "WLlogAction.php",
                        method: "post",
                        dataType: "text",
                        data: {"client": client, "date": date, "time": time, "type": "Email_2", "therapistId": therapistId, "therapist": therapist },
                        success: function (response) {
                            //alert('Zápis o poslaném emailu proveden');
                            location.reload();
                        }
                    });
                }
             });                         
            
            $("select[name='slot']").change(function() {
               
                var dateFrom = $("#dateFormatted").val();
                var today = "<?= $today?>";
                var selectedSlot = $("select[name='slot']").val();
               
                if (dateFrom === today) {
                    document.location = "reports_old?selectedSlot=" + selectedSlot;
                } else {
                    document.location = "reports_old?dateFrom=" + $("#dateFormatted").val() + "&selectedSlot=" + selectedSlot;
                }                
            });
            
            $("#vstupniVysetreniShow").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "reports_old?vstupniVysetreniShow";
                } else {
                    document.location = "reports_old";
                }
            });
        
            var prehledSlotu;
            if ($("#TableVolneVstupy td").length > 1) {
                prehledSlotu = $("#TableVolneVstupy").DataTable({
                    "order": [[11, "asc"]],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                    },
                    "responsive": false,
                    
                    "fixedHeader": true,
                    "pageLength": 10
                });
            };                       
            
            var prehledSlotu3;
            if ($("#tableObsazeneSloty td").length > 1) {
                prehledSlotu3 = $("#tableObsazeneSloty").DataTable({
                    "order": [[11, "asc"]],
                    
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                    },
                    "responsive": false,
                    
                    "fixedHeader": true,
                    "pageLength": 10
                });
            };

            $.widget.bridge("tlp", $.ui.tooltip); // ošetření konfliktu s widgetem tooltip() v Bootstrap, používáme ten z jQuery UI
                        
            var tableRezervacePoVstupu;
            if ($("#tableRezervacePoVstupu td").length > 1) {
                tableRezervacePoVstupu = $("#tableRezervacePoVstupu").DataTable({
                    "order": [[3, "asc"]],
                    
                    "responsive": false,
                    
                    "fixedHeader": true,
                    "pageLength": 100
                });
            };
        });

    </script>
</div>
</body>
</html>