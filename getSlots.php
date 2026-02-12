<?php

require_once "php/class.settings.php";
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

// dočasná tabulka s lidmi, kteří umí danou službu nebo s konkrétním vybraným člověkěm
$dbh->query("DROP TABLE IF EXISTS tmp_person");
$dbh->query("CREATE TEMPORARY TABLE tmp_person ( personId int NOT NULL )");

if (!empty($_POST["person"])) {
    $stmt = $dbh->prepare(" INSERT INTO tmp_person (personId)
                            SELECT
                                a.id
                            FROM adminLogin AS a
                            WHERE
                                EXISTS (SELECT id FROM relationPersonService WHERE person = a.id AND service = :service) AND
                                a.active = 1 AND
                                a.id = :person
                            ORDER BY a.displayName ");
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
    $stmt->bindParam(":person", $_POST["person"], PDO::PARAM_INT);
} else {
    $stmt = $dbh->prepare(" INSERT INTO tmp_person (personId)
                            SELECT
                                a.id
                            FROM adminLogin AS a
                            WHERE
                                EXISTS (SELECT id FROM relationPersonService WHERE person = a.id AND service = :service) AND
                                a.active = 1
                            ORDER BY a.displayName ");
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
}
$stmt->execute();

// dočasná tabulka s počty slotů k výše vybraným lidem
$dbh->query("DROP TABLE IF EXISTS tmp_slot");
$dbh->query("   CREATE TEMPORARY TABLE tmp_slot ( 
                    date date NOT NULL,
                    time time NOT NULL,
                    slotCount int NOT NULL
                )");

$stmt = $dbh->prepare(" INSERT INTO tmp_slot (date, time, slotCount)
                        SELECT
                            pat.date,
                            pat.time,
                            COUNT(pat.id)
                        FROM personAvailabilityTimetable AS pat
                        JOIN tmp_person ON tmp_person.personId = pat.person
                        WHERE pat.date BETWEEN :dateFrom AND :dateTo
                        GROUP BY pat.date, pat.time");
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();

// dočasná tabulka s počty rezervací k výše vybraným lidem
$dbh->query("DROP TABLE IF EXISTS tmp_reservation");
$dbh->query("   CREATE TEMPORARY TABLE tmp_reservation ( 
                    date date NOT NULL,
                    time time NOT NULL,
                    reservationCount int NOT NULL
                )");

$stmt = $dbh->prepare(" INSERT INTO tmp_reservation (date, time, reservationCount)
                        SELECT
                            r.date,
                            CAST(CONCAT(r.hour, ':', r.minute) as time),
                            COUNT(r.id)
                        FROM reservations AS r
                        JOIN tmp_person ON tmp_person.personId = r.personnel
                        WHERE
                            r.date BETWEEN :dateFrom AND :dateTo AND
                            r.active = 1
                        GROUP BY r.date, r.hour, r.minute");
$stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
$stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
$stmt->execute();


$query = "  SELECT
                IFNULL(tmp_slot.date, tmp_reservation.date) AS date,
                DATE_FORMAT(IFNULL(tmp_slot.time, tmp_reservation.time), '%H:%i') AS time
            FROM tmp_slot
            LEFT JOIN tmp_reservation ON tmp_reservation.date = tmp_slot.date AND tmp_reservation.time = tmp_slot.time
            WHERE
                IFNULL(tmp_slot.slotCount, 0) > IFNULL(tmp_reservation.reservationCount, 0) AND
                /*
                CASE WHEN HOUR(NOW()) <= 23
                    THEN DATE_ADD(CAST(IFNULL(tmp_slot.date, tmp_reservation.date) AS date), INTERVAL 0 DAY) > CAST(NOW() AS  date)
                    ELSE DATE_ADD(CAST(IFNULL(tmp_slot.date, tmp_reservation.date) AS date), INTERVAL -1 DAY) > CAST(NOW() AS  date)
                END
                */
                DATE_ADD(CAST(CONCAT(IFNULL(tmp_slot.date, tmp_reservation.date), ' ', IFNULL(tmp_slot.time, tmp_reservation.time)) AS datetime), INTERVAL -2 HOUR) > NOW()
            ORDER BY
                IFNULL(tmp_slot.date, tmp_reservation.date),
                IFNULL(tmp_slot.time, tmp_reservation.time)";
$stmt = $dbh->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);