<?php

function generateCsv($data, $delimiter = ';', $enclosure = '"') {
    $handle = fopen('php://temp', 'r+');
    foreach ($data as $line) {
        fputcsv($handle, $line, $delimiter, $enclosure);
    }
    rewind($handle);
    while (!feof($handle)) {
        $contents .= fread($handle, 8192);
    }
    fclose($handle);
    return $contents;
}

$hourToSend = 20;
$minuteToSend = 30;

$thisFriday = (new DateTime())->setTime($hourToSend, $minuteToSend, 0)->format("Y-m-d");
$lastFriday = (new DateTime())->setTime($hourToSend, $minuteToSend, 0)->sub(new DateInterval("P7D"))->format("Y-m-d");


$lastFriday = new DateTime("2021-07-03 20:30:00");
$thisFriday = new DateTime("2021-07-09 20:30:00");


$pageTitle = "FL - admin rezervace";

require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
require_once "../php/class.settings.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$query = "  SELECT
                r.date,
                r.hour,
                r.minute,
                a.displayName,
                s.name AS service,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                r.note
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON r.personnel = a.id
            WHERE 
                r.active = 1 AND
                ADDTIME(r.date, MAKETIME(r.hour, r.minute, 0)) BETWEEN :dateFrom AND :dateTo
            ORDER BY r.date, r.hour, r.minute";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $lastFriday->format("Y-m-d H:i:s"), PDO::PARAM_STR);
$stmt->bindValue(":dateTo", $thisFriday->format("Y-m-d H:i:s"), PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$headers = array("Datum", "Hodina", "Minuta", "Personál", "Služba", "Jméno", "Příjmení", "Email", "Telefon", "Poznámka");
array_unshift($results, $headers);

$mail = new PHPMailer\PHPMailer\PHPmailer();
$mail->Host = "localhost";
$mail->SMTPKeepAlive = true;
$mail->CharSet = "utf-8";
$mail->IsHTML(true);

$mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
//$mail->AddAddress("michal.oplt@hosolutions.cz", "Michal Oplt");
$mail->AddAddress("jiri.havlicky@fyzioland.cz", "Jiří Havlický");
$mail->Body = "Viz příloha";
$mail->addStringAttachment(chr(0xEF) . chr(0xBB) . chr(0xBF) . generateCsv($results), "prehled.csv");

$mail->Subject = "Přehled rezervací za týden od {$lastFriday->format("j. n. Y")} do {$thisFriday->format("j. n. Y")}";
if ($mail->Send()) {
    echo "Mail sent";
}