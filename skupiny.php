<?php

session_start();

require_once "php/class.settings.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$date = empty($_GET["date"]) ? date("Y-m-d") : $_GET["date"];
$numberOfShownDays = empty($_GET["numberOfShownDays"]) ? 7 : intval($_GET["numberOfShownDays"]);

$_SESSION["redirectAfterLogin"] = "rezervace-skupinova-cviceni?date=" . $date;

$daysOfWeek = array("Neděle", "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota");

/*
if ($numberOfShownDays < 7) {
    $dateFrom = $date;
} else {
    if (intval(date("w", strtotime($date))) !== 1) {
        $dateFrom = date("Y-m-d", strtotime("previous Monday", strtotime($date)));
    } else {
        $dateFrom = $date;
    }
}
*/

$dateFrom = $date;
$dateTo = date("Y-m-d", strtotime($dateFrom . " +$numberOfShownDays days"));

?>

<div class="skupinova-cviceni-kalendar">
    <?php
        $query = "  SELECT
                        ge.id,
                        ge.title,
                        ge.date,
                        ge.timeFrom,
                        ge.timeTo,
                        TIMESTAMPDIFF(MINUTE, ge.timeFrom, ge.timeTo) AS durationInMinutes,
                        a.displayName AS instructor,
                        ge.price,
                        ge.capacity,
                        ge.minAttendance,
                        (SELECT COUNT(id) FROM groupExcercisesParticipants WHERE groupExcercise = ge.id) AS occupancy,
                        (SELECT COUNT(id) FROM groupExcercisesParticipants WHERE groupExcercise = ge.id AND client = :client) AS userIsLogged,
                        ge.description,
                        ge.canceled,
                        fn_groupExcercises_getTime(ge.date, ge.timeFrom, ge.minutesBeforeForCancellation) AS criticalTime,
                        ge.semestralCourse,
                        CASE WHEN ge.semestralCourse IS NULL THEN
                            NULL
                        ELSE
                            (SELECT groupExcercisesCount FROM semestralCourses WHERE id = ge.semestralCourse)
                        END AS semestralCourseGroupExcercisesCount,
                        CASE WHEN ge.semestralCourse IS NULL THEN
                            NULL
                        ELSE
                            (SELECT date FROM groupExcercises WHERE semestralCourse = ge.semestralCourse ORDER BY date ASC LIMIT 1)
                        END AS semestralCourseFirstGroupExcercise,
                        CASE WHEN ge.semestralCourse IS NULL THEN
                            NULL
                        ELSE
                            (SELECT date FROM groupExcercises WHERE semestralCourse = ge.semestralCourse ORDER BY date DESC LIMIT 1)
                        END AS semestralCourseLastGroupExcercise
                    FROM groupExcercises AS ge
                    LEFT JOIN adminLogin AS a ON a.id = ge.instructor
                    WHERE
                        ge.date >= :dateFrom AND
                        ge.date < :dateTo AND
                        webCalendar = 1
                    ORDER BY ge.date, ge.timeFrom";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":dateFrom", $dateFrom, PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $dateTo, PDO::PARAM_STR);
        $stmt->bindValue(":client", isset($_SESSION["loggedUserId"]) ? $_SESSION["loggedUserId"] : "0", PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($results as $result) {
            $timeFrom = date("G:i", strtotime($result->timeFrom));
            $timeTo = date("G:i", strtotime($result->timeTo));
            $weekday = $daysOfWeek[(new DateTime())->createFromFormat("Y-m-d", $result->date)->format("w")];
            $dayAndMonth = (new DateTime())->createFromFormat("Y-m-d", $result->date)->format("j. n.");
            $userIsLogged = (intval($result->userIsLogged) >= 1 ? 1 : 0);
            
            $anchorMinute = intval(date("i", strtotime($result->timeFrom))) < 30 ? "00" : "30";
            $anchorTimeFrom = date("G", strtotime($result->timeFrom)) . ":" . $anchorMinute;
            $topOffset = (date("i", strtotime($result->timeFrom)) - $anchorMinute) / 30;
            
            $passed = ( (new DateTime())->createFromFormat("Y-m-d H:i:s", $result->date . " " . $result->timeFrom) <= (new DateTime()) ? TRUE : FALSE);
            
            $criticalTime = (new DateTime($result->criticalTime))->format("j. n. Y G:i");
            $isFull = ($result->occupancy >= $result->capacity ? true : false);
            
    ?>  
    <a href="#" class="item small <?= ($passed || $result->canceled === "1") ? 'passed' : ''; ?>" data-type="event" title="<?= htmlentities($result->title); ?>" data-id="<?= $result->id; ?>" data-day="<?= $result->date ?>" data-weekday="<?= $weekday ?>" data-day-and-month="<?= $dayAndMonth ?>" data-anchor-time-from="<?= $anchorTimeFrom ?>" data-time-from="<?= $timeFrom ?>" data-time-to="<?= $timeTo ?>" data-top-offset="<?= $topOffset ?>" data-length="<?= $result->durationInMinutes / 30 ?>" data-title="<?= htmlentities($result->title); ?>" data-instructor="<?= $result->instructor; ?>" data-capacity="<?= $result->capacity; ?>" data-occupancy="<?= $result->occupancy; ?>" data-description="<?= htmlentities($result->description) ?>" data-price="<?= number_format($result->price, 0, ",", " ") . " Kč" ?>" data-user-is-logged="<?= $userIsLogged ?>" data-canceled="<?= $result->canceled ?>" data-minimal-attendance="<?= $result->minAttendance ?>" data-critical-time="<?= $criticalTime ?>" data-semestral-course="<?= $result->semestralCourse ?>" data-semestral-course-group-excercises-count="<?= $result->semestralCourseGroupExcercisesCount ?>" data-semestral-course-first-group-excercise="<?= $result->semestralCourseFirstGroupExcercise ?>" data-semestral-course-last-group-excercise="<?= $result->semestralCourseLastGroupExcercise ?>">
        <div style="text-align: center;">Čas: <?= $timeFrom . " - " . $timeTo ?></div>
        <div class="item-title" style="text-align: center;"><?= $result->title; ?></div>
        <div style="text-align: center;"><?= $result->instructor; ?></div>
        
        <?php if ( $passed ): ?>
        <div style="text-align: center;" class="item-title">Již proběhlo</div>
        <?php elseif ( $result->canceled === "1" ): ?>
        <div style="text-align: center;" class="item-title">Nekoná se</div>
        <?php elseif ( $isFull ): ?>
        <div style="text-align: center;" class="item-title">Kapacita naplněna</div>
        <?php else: ?>
        <div style="text-align: center;">Obsazenost: <?= $result->occupancy; ?>/<?= $result->capacity; ?></div>
        <?php endif; ?>
        
        <?php if ($userIsLogged === 1): ?>
        <span class="glyphicon glyphicon-ok" data-mark="loggedIn" aria-hidden="true"></span>
        <?php endif; ?>
    </a>
    <?php
        }
    ?>
    <table class="table table-bordered" id="reservationsGroup">
        <thead>
            <tr>
                <th style="width: 60px;">&nbsp;</th>
                <?php
                    for ($i = 0; $i < $numberOfShownDays; $i++) {
                        $curDate = date("Y-m-d", strtotime($dateFrom . " +$i days"));
                        $dayOfWeek = $daysOfWeek[date("w", strtotime($curDate))];
                ?>
                <th data-role="days">
                    <?= $dayOfWeek ?>
                    <br>
                    <?= date("j. n.", strtotime($curDate)); ?>
                </th>
                <?php
                    }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($results) > 0) {
                $startTime = (new DateTimeImmutable())->createFromFormat("G:i", "7:00");
                $endTime = (new DateTimeImmutable())->createFromFormat("G:i", "23:30");
                $processedTime = new DateTime($startTime->format("G:i"));
                
                $lastRowFree = true;
                $rowFree = true;
                $freeRows = array();
                while ($processedTime <= $endTime) {
                    $hour = $processedTime->format("G");
                    $minute = $processedTime->format("i");
                    $time = $hour . ":" . $minute;
                    
                    $lastRowFree = $rowFree;
                    foreach ($results as $result) {
                        $anchorMinute = intval(date("i", strtotime($result->timeFrom))) < 30 ? "00" : "30";
                        $anchorTimeFrom = date("G", strtotime($result->timeFrom)) . ":" . $anchorMinute;
            
                        //$eventStart = (new DateTime())->createFromFormat("G:i:s", $result->timeFrom);
                        $eventStart = (new DateTime())->createFromFormat("G:i", $anchorTimeFrom);
                        $eventEnd = (new DateTime())->createFromFormat("G:i:s", $result->timeTo);
                        
                        if ($eventStart <= $processedTime && $eventEnd > $processedTime) {
                            $rowFree = false;
                            break;
                        }
                        $rowFree = true;
                    }
                    
                    if ($lastRowFree && $rowFree) {
                        $freeRows[] = $time;
                        
                        if ($processedTime < $endTime) {
                            $processedTime->add(new DateInterval("PT30M"));
                            continue;
                        }
                    }
                    if (count($freeRows) > 0) {
                        echo "<tr>";
                        echo "<td class='trTitle'>{$freeRows[0]}<br>-<br>" . $freeRows[count($freeRows) - 1] . "</td>";
                        echo "<td class='trTitle groupedEmpty' colspan='$numberOfShownDays'></td>";
                        echo "</tr>";
                        
                        $lastRowFree = false;
                        $rowFree = false;
                        $freeRows = array();
                        
                        if ($processedTime >= $endTime) {
                            break;
                        }
                    }
                    
                    echo "<tr data-hour='$hour' data-minute='$minute' data-free='$rowFree'>";
                    echo "<td class='trTitle'>$time</td>";
                    for ($j = 0; $j < $numberOfShownDays; $j++) {
                        $curDate = date("Y-m-d", strtotime($dateFrom . " +$j days"));

                        echo "<td class='' style='background-color: white;'><div class='tableCell' data-time='$time' data-hour='$hour' data-minute='$minute' data-day='$curDate'></div></td>";
                    }
                    echo "</tr>";
                    
                    $processedTime->add(new DateInterval("PT30M"));
                }
            } else {
                $query = "SELECT COUNT(id) AS count FROM groupExcercises WHERE date >= :dateFrom";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":dateFrom", $dateFrom, PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_OBJ);
                if (intval($result->count) === 0) {
                    $alert = "Pro tato období nebyla prozatím vložena skupinová cvičení do našeho rezervačního systému. Cvičení se obvykle opakují každý týden.<br>Prosíme o chvilku strpení, brzy zde jednotlivá cvičení vypíšeme a budete se moci přihlašovat :-)";
                } else {
                    $alert = "V tomto období nebyla vypsána žádná skupinová cvičení, zkuste prosím zvolit jiné období.";
                }
                
                echo "<tr>";
                echo "<td class='trTitle' colspan='" . ($numberOfShownDays + 1) . "'>$alert<br><br><br></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>