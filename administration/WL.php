<?php
$pageTitle = "FL - WL";

/*
 POKUD SE NA ČEKAČCE NEZOBRAZÍ ŽÁDNí klienti je problém v duplicitě v tabulce clientWLparam
 
 SELECT count(id) FROM clientWLparam GROUP BY client, service
 * mělo by být max 1 - u klientů u kterých není je nejjednodušší způsob uložti znovu klienta v ordinaci, tím se senchronizuje
  
 */

require_once "checkLogin.php";
require_once "../header.php";
require_once "../php/class.settings.php";

$days = array(1 => "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle");  
$today = new DateTime();
$today = $today->format("Y-m-d");   

if (isset($_GET["selectedSlot"])) {
    $selectedSlot = $_GET["selectedSlot"];    
} else {
    $selectedSlot = 'NULL';
}

if (isset($_GET["dateFrom"])) {
    if (!$dateFrom = (new DateTime)->createFromFormat("Y-m-d", $_GET["dateFrom"])) {
        
    }
} else {
    $dateFrom = (new DateTime()); 
}

//$conditions = isset($_GET["conditions"]) ? true : false;

if(isset($_GET["therapist"])) {
    $therapist = $_GET["therapist"];
} else {
    $therapist = '';    
}

if(isset($_GET["conditions"])) {
    $conditions = $_GET["conditions"];
} else {
    $conditions = "Y";    
}

if(isset($_GET["daysahead"])) {
    $daysahead = $_GET["daysahead"];
} else {
    $daysahead = 14;    
}
$lastDate = new DateTime();
$lastDate = (new DateTime($lastDate->format("Y-m-d")))->add(new DateInterval("P" . ($daysahead) . "D"));
$lastDate = $lastDate->format("j. n. Y"); 

?>

<div class="container-fluid" id="administrace-rezervaci">    
    <?php include "menu.php" ?>                               
     
        
    <div class="col-lg-9 col-lg-offset-2"> 
        <h2 class="text-center" style="margin-down: 15px;">
            <b>Návrhy pro nové rezervace v následujících <?= $daysahead?> (do <?=$lastDate?>) kalendářních dnech</b>
        </h2>
    </div>    
    <div class="row" style='margin-top: 25px;'>               
        <div class="col-lg-1 col-lg-offset-2">
            <label for="terapist">Volné sloty od </label>
            <input class="form-control text-center" style="width: 100%;" name="date" value="<?= $days[$dateFrom->format("N")] ?>, <?= $dateFrom->format("j. n. Y") ?>">
            <input class="form-control" type="hidden" name="dateFormatted" id="dateFormatted" value="<?= $dateFrom->format("Y-m-d") ?>">
        </div>
        <div class="col-lg-1">
            <label for="daysahead">Dní dopředu</label>
            <select name="daysahead" id= "daysahead" class="form-control text-center" style="width: 100%;">                                                   
                <option value="7" <?= $daysahead == '7' ? "selected" : "" ?>>7</option>
                <option value="14" <?= $daysahead == '14' ? "selected" : "" ?>>14</option>
                <option value="30" <?= $daysahead == '30' ? "selected" : "" ?>>30</option>
                <option value="60" <?= $daysahead == '60' ? "selected" : "" ?>>60</option>
                <option value="90" <?= $daysahead == '90' ? "selected" : "" ?>>90</option>
                <option value="120" <?= $daysahead == '120' ? "selected" : "" ?>>120</option>
                <option value="180" <?= $daysahead == '180' ? "selected" : "" ?>>180</option>
            </select>
        </div>
        <div class="col-lg-1">
            <div class="form-group">
                <label for="therapist">Terapeut/terapeutka</label>
                <select name="therapist" id="therapist" class="form-control" style="width: 100%;">
                    <option value="">Všichni</option>
                     <?php
                            $query = "  SELECT
                                            a.id,
                                            a.displayName
                                        FROM adminLogin AS a
                                        WHERE
                                            a.active = 1 AND
                                            a.activeWL = 1 AND
                                            EXISTS (SELECT id FROM relationPersonService WHERE person = a.id)
                                        ORDER BY a.displayName";
                            $stmt = $dbh->prepare($query);
                            $stmt->execute();
                            $resultsUsers = $stmt->fetchAll(PDO::FETCH_OBJ);

                        ?>
                    <?php foreach ($resultsUsers as $user): ?>
                        <option value="<?= $user->id ?>" <?= $user->id == $therapist ? "selected" : "" ?>><?= $user->displayName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        
    
            <?php                    
            // z důvodu toho, aby mi to pro aktuální den ukazovalo pouze volné sloty pozdějí než aktuální čas a v ostatní dny všechny volné sloty nezávisle na čase
            $dateFromFormatted_temp = $dateFrom->format("Y-m-d");
            if ($dateFromFormatted_temp === $today) {
                $dateFromFormatted = $dateFrom->format("Y-m-d H:i");
            } else {
                $dateFromFormatted = $dateFrom->format("Y-m-d");
            }
            $reportedClients = array();

            // dotaz na neobsazené sloty terapeutů včetně služeb, které nabízejí
            $query = "  
                SELECT
                    pat.id as patId,
                    pat.date as date,            
                    pat.time as time,
                    al.displayName AS therapist,
                    al.shortcut as shortcut,
                    '' as services,
                    al.id as therapistId,                    
                    (SELECT res.id FROM reservations res 
                     WHERE                                               
                        res.personnel = pat.person AND 
                        res.date = pat.date AND 
                        DATE_FORMAT(CAST(CONCAT(res.hour, ':', res.minute, ':00') AS TIME), '%H:%i') = pat.time AND 
                        res.active = 0 AND 
                        res.deleteTimestamp >
                            (SELECT MAX(actionTimestamp)FROM logWL
                             WHERE
                                pat.date = logWL.WLdate AND
                                pat.time = logWL.WLtime AND
                                pat.person = logWL.therapist
                        )
                     LIMIT 1
                    ) as recentlyDeleted,
                    SUBSTRING(DAYNAME(pat.date),1,3) as denTydne,
                    IFNULL(
                            (SELECT DATE_FORMAT(MAX(l.actionTimestamp),'%d.%m.%Y') FROM logWL l WHERE pat.person = l.therapist AND pat.time = l.WLtime AND pat.date = l.WLdate),
                            ' !!----- NEPOSLÁNO -----!! '
                    ) as lastSMS,
                    (SELECT COUNT(l.actionTimestamp) FROM logWL l WHERE pat.person = l.therapist AND pat.time = l.WLtime AND pat.date = l.WLdate) as countSentSMS,
                    IF(
                        (SELECT COUNT(l.actionTimestamp) FROM logWL l WHERE pat.person = l.therapist AND pat.time = l.WLtime AND pat.date = l.WLdate AND l.actionTimestamp IS NOT NULL) < 1 AND TIMESTAMPDIFF(day, curdate(), pat.date)<=14
                        ,
                        1,
                        0
                    ) as alert

                FROM personAvailabilityTimetable pat                                        
                LEFT JOIN adminLogin al ON al.id = pat.person

                WHERE                    
                    al.activeWL = 1 AND
                    (:therapist = '' OR :therapist = pat.person) AND
                    CAST(CONCAT(pat.date, ' ', pat.time) as datetime)>=:dateFrom AND
                    pat.date < CURRENT_TIMESTAMP() + INTERVAL :daysahead DAY AND                     
                    !EXISTS(SELECT r.id FROM reservations r
                        WHERE
                            r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND 
                            CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
                        LIMIT 1)
                ORDER BY date, time
            ";

            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":dateFrom", $dateFromFormatted, PDO::PARAM_STR);
            $stmt->bindParam(":therapist", $therapist, PDO::PARAM_INT);
            $stmt->bindParam(":daysahead", $daysahead, PDO::PARAM_INT);
            $stmt->execute();
            $VolneSloty = $stmt->fetchAll(PDO::FETCH_OBJ);               
            $pocetVolneSloty = count($VolneSloty);                                 

            if($selectedSlot === 'NULL') {
                $selectedSlot = $VolneSloty[0]->patId;
             }
             ?>
        </div>
        <div class="col-lg-2">
            <label for="terapist">Konkrétní slot </label>
            <select name="slot" id= "slot" class="form-control" style="width: 100%;">                                
                <?php foreach ($VolneSloty as $volnySlot): ?>
                    <?php if ($volnySlot->alert == 1 || $volnySlot->recentlyDeleted == 1): ?>
                        <option style = "color: red; font-weight:bold" value="<?= $volnySlot->patId ?>" <?= $selectedSlot == $volnySlot->patId ? "selected" : "" ?>><?= (new DateTime($volnySlot->date))->format("j. n. Y") . ' (' . $volnySlot->denTydne .') '.(new DateTime($volnySlot->time))->format("H:i") . ' '.  $volnySlot->shortcut . ' (poslední SMS: ' . $volnySlot->lastSMS .') [' .$volnySlot->countSentSMS .']'    ?></option>
                    <?php else: ?>
                        <option value="<?= $volnySlot->patId ?>" <?= $selectedSlot == $volnySlot->patId ? "selected" : "" ?>><?= (new DateTime($volnySlot->date))->format("j. n. Y") . ' (' . $volnySlot->denTydne .') '.(new DateTime($volnySlot->time))->format("H:i") . ' '.  $volnySlot->shortcut . ' (poslední SMS: ' . $volnySlot->lastSMS .') [' .$volnySlot->countSentSMS .']'  ?></option>
                    <?php endif; ?>    
                <?php endforeach; ?>                
            </select>
        </div>
        <div class="col-lg-1">
            <label for="terapist">Podmínky</label>
            <select name="conditions" id= "conditions" class="form-control text-center" style="width: 180px;">                                                   
                <option value="NE" <?= $conditions == 'Y' ? "selected" : "" ?>>Standardní</option>
                <option style = "color: red; font-weight:bold" value="SOFT" <?= $conditions == 'SOFT' ? "selected" : "" ?>>SOFT PODMÍNKY</option>
                <option style = "color: red; font-weight:bold" value="N" <?= $conditions == 'N' ? "selected" : "" ?>>!!!! ŽÁDNÉ PODMÍNKY!!!!</option>                
            </select>
        </div>
        <div class="col-lg-1">
                <?php
                    $query = "  
                    SELECT
                        COUNT(l.id) as pocet                        
                    FROM logWL l        
                    LEFT JOIN adminLogin al ON al.id = l.user
                    WHERE 
                        l.actionTimestamp IS NULL AND
                        (l.WLdate>curdate() OR IF(l.WLdate = curdate(), l.WLtime> curtime()+ INTERVAL 1 HOUR, FALSE)) AND
                        !EXISTS(SELECT r.id FROM reservations r WHERE r.active = 1 AND r.personnel = l.therapist AND r.date = l.WLdate AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = l.WLtime) AND            
                        EXISTS(
                            SELECT id
                            FROM personAvailabilityTimetable pat
                            WHERE
                                pat.date = l.WLdate AND            
                                pat.time = l.WLtime AND 
                                pat.person = l.therapist
                        )
                    ";

                    $stmt = $dbh->prepare($query);
                    $stmt->execute();
                    $pocetVeFronte = $stmt->fetch(PDO::FETCH_OBJ);
                ?>

            <div class="form-group">
                <label for="age">Počet SMS ve frontě</label>
                <input type="text" class="form-control" name="pocetVeFronte" id="pocetVeFronte" style="width: auto;" value=" <?= $pocetVeFronte->pocet?>" readonly>
            </div>
        </div>
        <div class="col-lg-2">
            <div class ="row">
                <button type="button" class="btn btn-primary"  style="margin-left: 15px; margin-top: 25px; width: auto;" type="button" name="reset" id = "reset">RESET</button>
                <button type="button" class="btn btn-secondary"  style="margin-left: 15px; margin-top: 25px; width: auto;" type="button" name="analysis" id = "analysis">Rozklad podmínek</button>
            </div>
        </div>                        
    </div>        
    
    <div class="row"> 
        <div class="col-lg-1 col-lg-offset-2">
            <button type="button" class="btn btn-success"  style="margin-left: 15px; margin-top: 25px; width: auto;" type="button" name="automatWL" id = "automatWL">Automat BĚŽÍ - stiskem VYPNI</button>
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
            $lastMondayString = $lastMonday->format('Y-m-d');
            //echo($lastMondayString);
            //die();

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
             // DLE ČEKACÍ LISTINY 
             // ******************************************************************

            $query = "               
                SELECT
                    CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) as client, 
                    IF(c.date IS NULL, NULL, ROUND(DATEDIFF(now(), c.date)/365,1)) as age,
                    (SELECT COUNT(id) FROM logWL WHERE actionTimestamp BETWEEN DATE_ADD(CURDATE(), INTERVAL - 45 DAY) AND CURDATE() AND client = t.client) as pocetNabidek,
                    (SELECT COUNT(id) FROM logWL WHERE actionTimestamp BETWEEN DATE_ADD(CURDATE(), INTERVAL - 45 DAY) AND CURDATE() AND client = t.client AND utilized = 1) as pocetVyuzito,
                    (SELECT COUNT(id) FROM logWL WHERE client = t.client AND utilized = 1) as pocetVyuzitoCelkem,
                    (SELECT DATE_FORMAT(logWL.actionTimestamp,'%d.%m.%Y') FROM logWL WHERE logWL.client = t.client ORDER BY logWL.actionTimestamp DESC LIMIT 1) as lastWLaction,
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
                        '<FONT COLOR=RED>ANO</FONT>', 'NE'
                    ) as cancelByFL,
                    CASE
                        WHEN t.service = 10 THEN CONCAT('<FONT COLOR = IndianRed>', s.shortcut, '</FONT>')
                        WHEN t.service = 1 THEN CONCAT('<FONT COLOR = Purple><b>', s.shortcut, '</b></FONT>')
                        ELSE s.shortcut 
                    END as serviceName,
                    t.service,
                    c.id as clientId,
                    CASE
                        WHEN (SELECT p.urgent FROM clientWLparam p WHERE p.client = t.client AND p.service = t.service) = 1 THEN '<FONT COLOR = red>ANO</FONT>' 
                        ELSE '<FONT COLOR = gray>NE</FONT>' 
                    END as urgent,
                    CONCAT(IF(p.freqW IS NULL,'', CONCAT('W: ', p.freqW)), IF(p.freqM IS NULL,'',CONCAT('M: ', p.freqM)), IF(p.freqM2 IS NULL,'',CONCAT('M2: ', p.freqM2)),'<br>', p.slottype) as frekvence,
                    (SELECT GROUP_CONCAT(al.shortcut SEPARATOR ', ') FROM clienttherapists2 ct
                        LEFT JOIN adminLogin al ON al.id = ct.therapist
                        WHERE ct.client = t.client AND ct.service = t.service) as therapists,                                
                    CONCAT(IFNULL(
                        (SELECT GROUP_CONCAT(CONCAT('<b>', IF(clientWLparam.note IS NULL, '', CONCAT('<FONT COLOR = MediumVioletRed>',services.shortcut, ': ', '</FONT>')), '</b>'), IFNULL(clientWLparam.note,'') SEPARATOR '<br>')
                            FROM clientWLparam
                            LEFT JOIN services ON services.id = clientWLparam.service
                            WHERE clientWLparam.client = t.client AND clientWLparam.note IS NOT NULL),''),                                
                        IF((SELECT noteWL FROM clients WHERE id = t.client) IS NULL,'', CONCAT('<br><FONT COLOR = MediumVioletRed><b>Obecná: </b><FONT COLOR = black>',(SELECT noteWL FROM clients WHERE id = t.client), '<br>')),
                        IF(!EXISTS(SELECT note FROM weeknotes WHERE client = t.client AND firstDayOfWeek BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY)),'', CONCAT('<br><FONT COLOR = MediumVioletRed><b>Specifická: </b><FONT COLOR = black>',(SELECT note FROM weeknotes WHERE client = t.client AND firstDayOfWeek BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY)),'<br>')),
                        IFNULL(
                            (SELECT GROUP_CONCAT(CONCAT('<b>', logWL.type, '.: ', '</b>', DATE_FORMAT(logWL.actionTimestamp,'%d.%m.%Y %H:%i'),' [', IFNULL(adminLogin.shortcut,''), ' ', IFNULL(DATE_FORMAT(logWL.creationTimestamp, '%d.%m.%Y %H:%i'),''), '] ','<i><b>' , logWL.note, IF(logWL.rejected=1,'<FONT COLOR=red> (Odmítnuto)<FONT COLOR=black>',''),IF(logWL.utilized=1,'<FONT COLOR=green> (Využito)<FONT COLOR=black>',''),'</i></b>') SEPARATOR '<br>') 
                                FROM logWL                 
                                LEFT JOIN adminLogin ON adminLogin.id = logWL.user
                                WHERE logWL.client = t.client AND logWL.WLdate = :date AND logWL.WLtime = :time AND logWL.therapist = :therapist),
                            '')                                
                        ) as notes,
                    (SELECT GROUP_CONCAT(CONCAT(DAY(r.date), '.', MONTH(r.date), ' (', a.shortcut,')') SEPARATOR ', ')                                                              
                        FROM reservations r                               
                        LEFT JOIN adminLogin a ON a.id = r.personnel
                        WHERE
                            (r.client = t.client OR 
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
                            (r.client = t.client OR 
                             r.email = c.email OR
                             r.phone = c. phone    
                            ) AND
                            r.active = 1 AND
                            r.date > :date
                        ORDER BY
                            r.date) as budouciRezervace,
                    (SELECT IF(MAX(DATE_FORMAT(actionTimestamp,'%Y-%m-%d')) = curdate(), 1,0) FROM logWL WHERE client = t.client AND WLdate = :date AND WLtime = :time and therapist = :therapist) as lastLogWLToday,                  
                    IF(
                        EXISTS(SELECT id FROM logWL WHERE client = t.client AND WLdate = :date AND WLtime = :time and therapist = :therapist AND actionTimestamp IS NULL),
                        'ANO',
                        'NE'
                    ) as SMSveFronte,
                    -- doplnění infa, kdo a kdy vytvořil zařazení na čekací listinu
                    (SELECT                         
                        IF(l.creationTimestamp IS NULL, 
                            '', 
                            CONCAT('<FONT color = gray><small>(', al.shortcut, ', ', DATE_FORMAT(l.creationTimestamp, '%d.%m.%Y %H:%i'), ')</FONT><small>')
                        )                         
                        FROM logWL l
                        LEFT JOIN adminLogin al ON al.id = l.user
                        WHERE
                            l.client = t.client AND
                            l.WLdate = :date AND
                            l.WLtime = :time AND
                            l.therapist = :therapist AND
                            l.actionTimestamp IS NULL
                        ORDER BY l.creationTimestamp DESC
                        LIMIT 1
                        
                    ) as created 
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client
                LEFT JOIN services s ON s.id = t.service
                LEFT JOIN adminLogin a ON a.id = t.therapist                
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND
                    (:conditions = 'N' OR
                        (   -- pro případ žádných podmínek
                            
                            (
                                IFNULL(
                                    (SELECT noWL FROM weeknotes WHERE client = t.client AND firstDayOfWeek = :lastMonday),
                                    0
                                ) = 0
                            ) AND 

                            -- má zájem o daný slot
                            IF(:conditions = 'SOFT',
                                TRUE,                                
                                (
                                    IF (p.slottype = 'indiv', t.client IN (SELECT client FROM clientAvailabilityWL WHERE time = :time AND date = :date), FALSE) OR 
                                    CASE
                                        WHEN p.slottype = 'forenoon' THEN :time < CAST('13:00:00' AS time)
                                        WHEN p.slottype = 'afternoon' THEN :time > CAST('12:00:00' AS time)
                                        WHEN p.slottype = 'all' THEN TRUE
                                        WHEN p.slottype IS NULL THEN FALSE
                                    END
                                )
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
                                ) < IFNULL(p.freqW, 0) OR
                                (
                                    (SELECT COUNT(id) FROM reservations r
                                        WHERE
                                           (
                                                r.client = c.id OR 
                                                (
                                                    r.client IS NULL AND
                                                    (r.phone = c.phone OR c.email = r.email) AND TRIM(AES_DECRYPT(c.name , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.name , '" . Settings::$mySqlAESpassword . "')) AND TRIM(AES_DECRYPT(c.surname , '" . Settings::$mySqlAESpassword . "')) = TRIM(AES_DECRYPT(r.surname , '" . Settings::$mySqlAESpassword . "'))
                                                )
                                            ) AND
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
                                            ) AND
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
                                            ) AND
                                           (
                                                IF(t.service = 2, (r.service = 2 OR r.service = 12), r.service = t.service)
                                            ) AND
                                           r.active = 1 AND
                                           r.sooner IS NULL AND
                                           r.date BETWEEN DATE_ADD(:date, INTERVAL - ((SELECT p.freqM2 FROM clientWLparam p WHERE p.client = t.client AND p.service = t.service)-1)*30 DAY) AND DATE_ADD(:date, INTERVAL + 60 DAY)                                   
                                   ) < 1
                                )
                            )
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
        if (!$stmt) {
            echo "Chyba při prepare(): " . $conn->error;
        }
        $stmt->bindParam(":time", $volnySlot->time, PDO::PARAM_STR);
        $stmt->bindParam(":time2", $volnySlot->time, PDO::PARAM_STR);
        $stmt->bindParam(":date", $volnySlot->date, PDO::PARAM_STR);
        $stmt->bindParam(":therapist", $volnySlot->therapistId, PDO::PARAM_INT);
        $stmt->bindParam(":lastMonday", $lastMondayString, PDO::PARAM_STR);
        $stmt->bindParam(":firstDayOfMonth", $firstDayOfMonthString, PDO::PARAM_STR);
        $stmt->bindParam(":lastDayOfMonth", $lastDayOfMonthString, PDO::PARAM_STR);
        $stmt->bindParam(":conditions", $conditions, PDO::PARAM_STR);        
                     
        if (!$stmt->execute()) {
            
            echo "Time: "; echo($volnySlot->time); echo "<br>";
            echo "Date: "; echo($volnySlot->date); echo "<br>";
            echo "TherapistId: "; echo($volnySlot->therapistId); echo "<br>";
            echo "LastMonday: "; echo($lastMondayString); echo "<br>";
            echo "FirstDayOfMonth: "; echo($firstDayOfMonthString); echo "<br>";
            echo "lastDayOfMonth: "; echo($lastDayOfMonthString); echo "<br>";
            echo "Conditions: "; echo($conditions); echo "<br><br>";    
            
            echo "Chyba při SQL dotazu:<br>";
            
            echo $stmt->error . "<br>";
            echo $stmt->errno . "<br>";
            echo $stmt->sqlstate;
        } 
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);                                         

        //echo(count($results));
        if (count($results) > 0) {
            foreach ($results as $result) {
                //echo($result->clientId);
                $reportedClients[] = $result->clientId;

            }            
        } 

        ?>               

        <div class="col-lg-9 col-lg-offset-2">
            <h4 class="text-left">
               DLE ČEKACÍ LISTINY A PŘIŘAZENÉHO TERAPEUTA
               <button type="button"  class="btn btn-secondary"  style="margin-left: 10px; width: auto;" type="button" name="sendSMStoALL" id = "<?= $volnySlot->patId ?>">Zařadit do fronty <b>všechny klienty</b> uvedené v tabulce <b>dle standardních podmínek</b></button>
            </h4>                        
        </div>                        
            
        <div class="col-lg-9 col-lg-offset-2">
            <table id = "tabulka" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 2%;" class="text-center">Link</th>
                        <th style="width: 2%;" class="text-center">#</th>
                        <th style="width: 8%;" class="text-center">Klient</th>
                        <th style="width: 5%;" class="text-center">Služba</th>
                        <th style="width: 3%;" class="text-center">Terapeut</th>
                        <th style="width: 2%;" class="text-center">Věk</th>
                        <th style="width: 3%;" class="text-center">Urgentní</th>
                        <th style="width: 3%;" class="text-center">Zrušena posl. rez. ze strany FL</th>
                        <th style="width: 8%;" class="text-center">Akce</th>
                        <th style="width: 6%;" class="text-center">nabídek 45D/akcep. 45D/akc. total</th>
                        <th style="width: 5%;" class="text-center">Posl. nabídka WL</th>
                        <th style="width: 3%;" class="text-center">Frekvence</th>
                        <th style="width: 12%;" class="text-center">Předchozí rezervace</th>
                        <th style="width: 10%;" class="text-center">Budoucí rezervace</th>
                        <th style="width: 28%;" class="text-center">Poznámky</th>
                        
                        


                    </tr>
                </thead>
                <tbody>
                    <?php if (count($results) > 0): ?>
                        <?php $pocet = 0?>
                        <?php foreach ($results as $result): ?>
                            <?php $pocet = $pocet + 1?>
                    
                                <?php if ($result->SMSveFronte == 'ANO'): ?>    
                                   <tr style ="background-color: silver" data-date="<?= (new DateTime($volnySlot->date))->format("Y-m-d") ?>" data-time="<?= (new DateTime($volnySlot->time))->format("H:i") ?>" data-client="<?= $result->clientId?>" data-therapistId="<?= $volnySlot->therapistId?>" data-therapist="<?= $volnySlot->therapist?>" data-service="<?= $result->service?>">                                       
                               <?php else: ?>
                                   <tr data-date="<?= (new DateTime($volnySlot->date))->format("Y-m-d") ?>" data-time="<?= (new DateTime($volnySlot->time))->format("H:i") ?>" data-client="<?= $result->clientId?>" data-therapistId="<?= $volnySlot->therapistId?>" data-therapist="<?= $volnySlot->therapist?>" data-service="<?= $result->service?>">
                               <?php endif; ?>   
                                                
                                <td class="text-center"><button class="btn btn-secondary"  style="width: auto;margin-right: 2px" type="button" name="ordinace">O</button></td>
                                <td class="text-center"><?= $pocet ?></td>
                                <?php if ( !empty($result->lastLogWLToday == 0) ): ?>    
                                    <td class="text-left"><?= $result->client ?>  </td>                                             
                                <?php else: ?>
                                    <td class="text-left"><FONT COLOR=blue><?= $result->client ?></FONT></td>                            
                                <?php endif; ?>

                                <td class="text-center"><?= $result->serviceName?></td>                                                                                                   
                                <td class="text-center"><?= '<b>'. $result->therapists .  '</b>' ?></td>
                                <td class="text-center"><?= $result->age?></td>
                                <td class="text-center"><?= $result->urgent?></td>
                                <td class="text-center"><?= $result->cancelByFL?></td>
                                <td class="text-center">
                                    <a href="#" data-type="smsSend" title="Odeslat SMS s nabídkou termínu">
                                        <span class="glyphicon glyphicon-send"></span>
                                    </a>
                                    <a href="#" data-type="smsQR" title="Načíst QR pro SMS s nabídkou termínu do logu">
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
                                <td class="text-center"><?= $result->pocetNabidek . ' / ' . $result->pocetVyuzito  . ' / ' . $result->pocetVyuzitoCelkem ?></td>
                                <td class="text-center"><?= $result->lastWLaction ?></td>
                                <td class="text-center"><?= $result->frekvence?></td>                                                    
                                <td class="text-center"><?= $result->posledniRezervace ?></td>
                                <td class="text-center"><?= $result->budouciRezervace ?></td>                                
                                <?php if ($result->SMSveFronte == 'ANO'): ?>    
                                    <td class="text-left"><?= $result->notes . '<b><FONT color = blue>SMS ZAŘAZENA DO FRONTY K ODESLÁNÍ</FONT></b> ' . $result->created ?></td> 
                                <?php else: ?>
                                    <td class="text-left"><?= $result->notes ?></td>                                                               
                                <?php endif; ?>
                                
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                            <tr><td colspan="15" class="text-center">Nebyly nalezeny žádné možnosti rezervací.</td></tr>    
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    <?php } ?>
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
                <input type="hidden" id="service" name="service" value="">
                <input type="hidden" id="message" name="message" value="">
                <input type="hidden" id="QR" name="QR" value="">
                
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
            $.fn.modal.Constructor.prototype.enforceFocus = function () {};

            $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();                       
            
            var btnWL = document.getElementById('automatWL');  
            $.ajax({
                url: "automatWLstate.php",
                method: "post",
                dataType: "json",
                data: {"value": 1},
                success: function (response) {                    
                    if(response.state === '1') {
                        btnWL.className = 'btn btn-success';
                        btnWL.textContent = 'Automat BĚŽÍ - stiskem VYPNI';
                    } else {
                        btnWL.className = 'btn btn-danger';
                        btnWL.textContent = 'Automat NEběží - stiskem ZAPNI';
                    }                                                        
                }
            });
        
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

            if ($("#tabulka td").length > 1) {
		tabulka = $("#tabulka").DataTable({                    
			"order": [[0, "asc"]],
			"language": {
				"url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
			},
			"responsive": false,                    
			"fixedHeader": true,
			"pageLength": 100
		});
            };
        
            $("input[name='date']").change(function() {               
                    window.location = "WL.php?dateFrom=" + $("#dateFormatted").val();
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
            
            $("body").on("click", "a[data-type='smsSend']", function(event) {
                event.preventDefault();
                var tr = $(this).closest("tr");            
                var time = tr.attr("data-time"); 
                var date = tr.attr("data-date"); 
                var client = tr.attr("data-client");        
                var therapist = tr.attr("data-therapist");
                var therapistId = tr.attr("data-therapistId");
                var isergo = tr.attr("data-isergo");
                var isfyzio = tr.attr("data-isfyzio");
                var service = tr.attr("data-service");
                
                var hash = randomString(5);
                
                $.ajax({
                    url: "getQRsmsWL2.php",
                    method: "post",
                    dataType: "json",
                    data: {"client": client, "date": date, "time": time, "therapist": therapist, "therapistId": therapistId, "hash":hash, "service": service},
                    success: function (response) {                        
                        $("#date").val(date);
                        $("#time").val(time);
                        $("#client").val(client);
                        $("#therapist").val(therapistId);
                        $("#hash").val(hash);
                        $("#isergo").val(isergo);
                        $("#isfyzio").val(isfyzio);
                        $("#service").val(service);
                        $("#message").val(response.smsText);
                        $("#QR").val('N');  //posílání SMS napřímo bez QR kódu
                        $("#SMSzapisLog").click();                                                             
                    }
                });
                
            });
            
            $("body").on("click", "a[data-type='smsQR']", function(event) {						                                                                                                                   
                var tr = $(this).closest("tr");            
                var time = tr.attr("data-time"); 
                var date = tr.attr("data-date"); 
                var client = tr.attr("data-client");        
                var therapist = tr.attr("data-therapist");
                var therapistId = tr.attr("data-therapistId");
                var isergo = tr.attr("data-isergo");
                var isfyzio = tr.attr("data-isfyzio");
                var service = tr.attr("data-service");
                
                var hash = randomString(5);                
                
                /*
                alert(time);
                alert(date);
                alert(client);
                alert(therapistId);                
                */
               
                $.ajax({
                    url: "getQRsmsWL2.php",
                    method: "post",
                    dataType: "json",
                    data: {"client": client, "date": date, "time": time, "therapist": therapist, "therapistId": therapistId, "hash":hash, "service": service},
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
                        $("#service").val(service);
                        $("#message").val(response.smsText);
                        $("#QR").val('Y');
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
                var service = $("#service").val();
                var message = $("#message").val();
                var QR = $("#QR").val();
                                
                $.ajax({
                    url: "WLlogAction.php",
                    method: "post",
                    dataType: "text",
                    data: {"client": client, "date": date, "time": time, "therapistId": therapistId, "type": "sms", "hash":hash, "service" : service, "message" : message, "QR": QR},
                    success: function (response) {
                        //alert('Zápis poslané SMS proveden');
                        if(QR=='N') {                            
                            // nezobrazuji QR kód, rovnou zařazuji SMS do fronty
                            $("#tabulka").find("tbody tr[data-client = '" + client + "']")[0].style.backgroundColor = "silver";
                        }
                        if(QR=='Y') {
                            location.reload();
                        }
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
            
            $("[name='ordinace']").click(function(event) {   
                var tr = $(this).closest("tr");
                var client = tr.attr("data-client");
                window.open('http://192.168.1.204/index.php?client=' + client + '&label=wl', '_blank');
            });
            
             $("[name='sendSMStoALL']").click(function() {
                var idSlot = $(this).attr("id")                              
                $.ajax({
                    url: "sendSMStoALLinSlot.php",
                    method: "post",
                    dataType: "text",
                    data: {"idSlot": idSlot},
                    success: function (response) {
                        alert('Všchni klienti daného slotu přiřazeni do fronty na SMS');   
                        location.reload();
                    }
                });                                         
             });
             
             $("#therapist").change(function() {                    
                var dateFrom = $("#dateFormatted").val();
                var today = "<?= $today?>";  
                var selectedtherapist = $("select[name='therapist']").val();
                var selectedSlot = $("select[name='slot']").val();
                var conditions = $("select[name='conditions']").val();
                var daysahead = $("select[name='daysahead']").val();
                var cesta;                                                
                
                cesta =  "WL?daysahead=" + daysahead;
               
                if (dateFrom != today) {cesta = cesta + "&dateFrom=" + $("#dateFormatted").val();}
                if (conditions != "Y") {cesta = cesta + "&conditions=" + conditions;}
                if (selectedtherapist != '') {cesta = cesta + "&therapist="+selectedtherapist;}                
                                
                document.location = cesta;
             });
             
             $("#conditions").change(function() {                    
                $("select[name='therapist']").trigger("change");                    
             });
             
             $("#daysahead").change(function() {                    
                    $("select[name='therapist']").trigger("change");
             });
             
              $("select[name='slot']").change(function() {
                var dateFrom = $("#dateFormatted").val();
                var today = "<?= $today?>";                
                var selectedSlot = $("select[name='slot']").val();
                var selectedtherapist = $("select[name='therapist']").val();
                var conditions = $("select[name='conditions']").val();
                var daysahead = $("select[name='daysahead']").val();
                var cesta;

                                
                cesta =  "WL?daysahead=" + daysahead + "&selectedSlot="+ selectedSlot;
               
                if (dateFrom != today) {cesta = cesta + "&dateFrom=" + $("#dateFormatted").val();}
                if (conditions != 'Y') {cesta = cesta + "&conditions=" + conditions;}
                if (selectedtherapist != '') {cesta = cesta + "&therapist="+selectedtherapist;}
                
                document.location = cesta;
            });
            
            $("[name='reset']").click(function() {
                document.location = "WL.php";
            });
            
            $("[name='automatWL']").click(function() {                
                var btn = document.getElementById('automatWL');                
                if(btn.className=='btn btn-success') {
                    //zde vypnout automat                  
                    $.ajax({
                        url: "automatWLonoff.php",
                        method: "post",
                        dataType: "text",
                        data: {"value": 0},
                        success: function (response) {
                            btn.className = 'btn btn-danger';
                            btn.textContent = 'Automat NEběží - stiskem ZAPNI';
                            alert('Automat byl vypnut');
                        }
                    });
                } else {
                    //zde zapnout automat
                    $.ajax({
                        url: "automatWLonoff.php",
                        method: "post",
                        dataType: "text",
                        data: {"value": 1},
                        success: function (response) {
                            btn.className = 'btn btn-success';
                            btn.textContent = 'Automat BĚŽÍ - stiskem VYPNI';
                            alert('Automat byl ZAPNUT');
                        }
                    });
                }
            });
            
            $("[name='analysis']").click(function() {
                var selectedSlot = $("select[name='slot']").val();                
                window.open( "WL_2?selectedSlot=" + selectedSlot,'_blank');			
            });
        });

    </script>
</div>
</body>
</html>