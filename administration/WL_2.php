<?php
$pageTitle = "FL - WL - vyřazení";

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
        <h2 class="text-center">
            <b>Návrhy pro nové rezervace v následujících 15 kalendářních dnech</b>
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
    $reportedClients = array();

    // dotaz na neobsazené sloty terapeutů včetně služeb, které nabízejí
    $query = "  
        SELECT
            pat.id as patId,
            pat.date as date,            
            pat.time as time,
            al.displayName AS therapist,
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
            SUBSTRING(DAYNAME(pat.date),1,3) as denTydne
        FROM personAvailabilityTimetable pat                                        
        LEFT JOIN adminLogin al ON al.id = pat.person

        WHERE                    
            CAST(CONCAT(pat.date, ' ', pat.time) as datetime)>=:dateFrom AND
            pat.date < CURRENT_TIMESTAMP() + INTERVAL 120 DAY AND                     
            !EXISTS(SELECT r.id FROM reservations r
                WHERE
                    r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND 
                    CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
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
                    <option value="<?= $volnySlot->patId ?>" <?= $selectedSlot == $volnySlot->patId ? "selected" : "" ?>><?= (new DateTime($volnySlot->date))->format("j. n. Y") . ' (' . $volnySlot->denTydne .') '.(new DateTime($volnySlot->time))->format("H:i") . ' '.  $volnySlot->therapist  ?></option>
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
             // DLE ČEKACÍ LISTINY 
             // ******************************************************************

            $query = "               
                SELECT
                    'Všichni klienti na WL na daného terapeuta' as reason,                   
                    COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client                    
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client                                
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND                   
                    p.activeWL = 1 
                
                UNION
                
                SELECT
                    'Nechtějí primárně daný časový slot' as reason,                   
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client                    
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client                                
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND 
                    -- nemá zájem o daný slot
                    (
                        IF (p.slottype = 'indiv', t.client NOT IN (SELECT client FROM clientAvailabilityWL WHERE time = :time AND date = :date), FALSE) OR 
                        CASE
                            WHEN p.slottype = 'forenoon' THEN :time > CAST('12:00:00' AS time)
                            WHEN p.slottype = 'afternoon' THEN :time < CAST('13:00:00' AS time)
                            WHEN p.slottype = 'all' THEN FALSE
                            WHEN p.slottype IS NULL THEN TRUE
                        END
                    ) AND
                    p.activeWL = 1                                 
                
                UNION
                
                SELECT
                    'noWL pro daný týden' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client                    
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client                                
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND
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
                    (
                        IFNULL(
                            (SELECT noWL FROM weeknotes WHERE client = t.client AND firstDayOfWeek = :lastMonday),
                            0
                        ) = 1
                    ) 
                    
                UNION

                SELECT
                    'Nemoc 5 dní' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client                    
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client                                
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND
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
                    EXISTS(
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
                        )                
                UNION
                
                SELECT
                    'Smazaná rezervace pro tento slot' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client                                
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND
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
                    ) > 0                                        
                
                UNION
                
                SELECT
                    'Hledám termín pro vstup-ergo a v daném dni již máte terapuet 2 vstupy a více' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client          
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND                    
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
                    IF(t.service = 10, (SELECT COUNT(id) FROM reservations res WHERE res.personnel = t.therapist AND res.active = 1 AND res.service = 10 AND res.date=:date)>=2, FALSE)
                
                UNION
                
                SELECT
                    'Hledám termín pro vstup-ergo a terapeut má již překročen limit pro něj na max počet vstupů na daný týden' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client          
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service
                LEFT JOIN adminLogin a ON a.id = t.therapist

                WHERE
                    t.therapist = :therapist AND                    
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
                    IF (t.service = 10, (SELECT COUNT(id) FROM reservations res WHERE res.personnel = t.therapist AND res.active = 1 AND res.service = 10 AND res.date BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY))>=a.maxEntryErgoPerWeek, FALSE)
                
                UNION
                
                SELECT
                    'Překročena týdenní frekvence' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client          
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND                    
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
                    (SELECT COUNT(id)FROM reservations r
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
                            r.date BETWEEN :lastMonday AND DATE_ADD(:lastMonday, INTERVAL 6 DAY)                                    
                    ) >= p.freqW 
                
                UNION
                
                SELECT
                    'Překročená měsíční frekvence' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client
          
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND                    
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
                    ) >= p.freqM 
                    
                UNION
                
                SELECT
                    'Není překročena měsíční frekvence ale existuje rezervace +/- 5 dní' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client
          
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND                    
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
                            EXISTS(SELECT r.id FROM reservations r
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
                                    r.service = t.service AND
                                    r.date BETWEEN DATE_ADD(:date, INTERVAL -5 DAY) AND DATE_ADD(:date, INTERVAL 5 DAY)                                    
                            )   
                        )
                UNION
                
                SELECT
                    'Překročená frekvence M2' as reason,
                    -COUNT(c.name) as pocet,
                    GROUP_CONCAT(CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) SEPARATOR ', ') as client
                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client
          
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND                    
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
                    
                    IF(p.freqM2 IS NULL, FALSE,
                        (SELECT COUNT(id) FROM reservations r
                           WHERE
                               (r.client = c.id OR 
                                r.email = c.email OR
                                r.phone = c. phone
                               ) AND
                               (
                                    IF(t.service = 2, (r.service = 2 OR r.service = 12), r.service = t.service)
                                ) AND 
                               r.active = 1 AND
                               r.sooner IS NULL AND
                               r.date BETWEEN DATE_ADD(:date, INTERVAL - ((SELECT p.freqM2 FROM clientWLparam p WHERE p.client = t.client AND p.service = t.service)-1)*30 DAY) AND DATE_ADD(:date, INTERVAL + 60 DAY)                                   
                       ) > 0
                    )    
                ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":time", $volnySlot->time, PDO::PARAM_STR);        
        $stmt->bindParam(":date", $volnySlot->date, PDO::PARAM_STR);
        $stmt->bindParam(":therapist", $volnySlot->therapistId, PDO::PARAM_INT);
        $stmt->bindParam(":lastMonday", $lastMondayString, PDO::PARAM_STR);
        $stmt->bindParam(":firstDayOfMonth", $firstDayOfMonthString, PDO::PARAM_STR);
        $stmt->bindParam(":lastDayOfMonth", $lastDayOfMonthString, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);                                           

        ?>               

        <div class="col-lg-9 col-lg-offset-2">
            <h4 class="text-left">
               VYŘAZENÍ KLIENTI Z REPORTU
            </h4>
        </div>

        <div class="col-lg-9 col-lg-offset-2">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 20%;" class="text-center">Důvod vyřazení</th>
                        <th style="width: 2%;" class="text-center">Počet</th>
                        <th style="width: 2%;" class="text-center">Kumulativně</th>
                        <th style="width: 78%;" class="text-center">Klient</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($results) > 0): ?>
                        <?php $celkPocet = 0?>
                        <?php foreach ($results as $result): ?>
                             <?php $celkPocet = $celkPocet + $result->pocet?>
                            <tr>                                       
                                <td class="text-left"><?= $result->reason ?></td>
                                <td class="text-center"><?= $result->pocet ?></td>
                                <td class="text-center"><?= $celkPocet ?></td>
                                <td class="text-left"><?= $result->client ?>  </td>                                                                                                                                                   
                            </tr>
                        <?php endforeach; ?>  
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    <?php } ?>
            
        <div class="row" style = "margin-bottom: 5px;">
            <div class="col-lg-3 col-lg-offset-2 text-center">
                <div class="form-group">
                    <br>
                    <label for="client">Vybrat klienta:</label>
                    <select id="client" name="client" class="form-control">
                        <option value=""></option>
                        <?php
                            $query = "  SELECT
                                            c.id,
                                            AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
                                            AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                                            AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                                            AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email,
                                            YEAR(c.date) as year,
                                            ROUND(DATEDIFF(now(), c.date)/365,1) as age
                                        FROM clients AS c
                                        ORDER BY
                                            AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'),
                                            AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')";
                            $stmt = $dbh->prepare($query);
                            $stmt->execute();
                            $resultClients = $stmt->fetchAll(\PDO::FETCH_OBJ);
                            foreach ((array)$resultClients as $client):                   
                                // vlastnosti do atributů data-atribut="hodnota"
                                $attributes = array();
                                foreach ($client as $key => $property) {
                                    $attributes[] = "data-" . $key . '="' . htmlspecialchars($property) . '"';
                                }
                                $attributesString = implode(" ", $attributes)
                        ?>


                        <option value="<?= $client->id ?>" <?= $attributesString ?>>
                            <?= $client->surname . ", " . $client->name?>
                            <?= empty($client->year) ? "":" (ročník {$client->year}"?>
                            <?= empty($client->phone) ? "":" {$client->phone})"?>
                            <?= empty($client->email) ? "":" {$client->email}"?>
                        </option>
                        <?php
                            endforeach;
                        ?>
                    </select>                
                </div>
            </div>
            <div class="col-lg-4">            
                <button type="button" class="btn btn-success"  style="margin-left: 15px; margin-top: 40px; width: auto;" type="button" name="deleteFutWLClient" id = "showFutWLClient">POČET SMS VE FRONTĚ</button>                
                <button type="button" class="btn btn-danger"  style="margin-left: 15px; margin-top: 40px; width: auto;" type="button" name="deleteFutWLClient" id = "deleteFutWLClient">SMAZAT BUDOUCÍ NEPOSLANOU ČEKACÍ LISTINU</button>                
            </div>
        </div>        
            
    </div>                    

    <script>
        $(document).ready(function () {
                     
            $.fn.modal.Constructor.prototype.enforceFocus = function () {};
            $.fn.select2.defaults.set( "width", "100%" );
             
            $("#client").select2({                           
                placeholder: "Začněte psát příjmení klienta",
                language: {
                    noResults: function () {
                        return $('<a href="#" id="notFound">Nenalezeny žádné výsledky</a>');
                    }
                },
                selectOnClose: true
            }); 
            
            $("#deleteFutWLClient").click(function(event) {      
                var client = $("select[name='client']").val();
                if (client === '') {
                    alert('Nejprve vyberte prosím klienta')
                } else {                
                    if (confirm("Skutečně si přejete odstranit SMS ve frontě?")) {
                        event.preventDefault();

                        $.ajax({
                            url: "deleteFutWLClient.php?_=" + new Date().getTime(),
                            method: "post",
                            data: {"client" : client},
                            dataType: "json",
                            success: function(response) {
                                alert('Počet smazaných záznamů: ' + response.pocetSmazanych);
                            } 
                        });
                    }
                }
            });
            
            $("#showFutWLClient").click(function(event) {      
                var client = $("select[name='client']").val();
                if (client === '') {
                    alert('Nejprve vyberte prosím klienta')
                } else {     
                    $.ajax({
                        url: "showFutWLClient.php?_=" + new Date().getTime(),
                        method: "post",
                        data: {"client" : client},
                        dataType: "json",
                        success: function(response) {
                            alert('Počet SMS ve frontě: ' + response.pocetSMSveFronte + ' | ' + response.WLdate);
                        } 
                    });
                }
            });
        });
    </script>
</div>
</body>
</html>