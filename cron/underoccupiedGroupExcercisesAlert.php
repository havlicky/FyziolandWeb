<?php

require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";

if ( $_GET["hash"] !== "sendFromCron" ) {
    echo "Tento skript je spouštěn pouze automaticky.";
    die();
}

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$query = "  SELECT
                ge.id,
                ge.title,
                ge.date,
                a.displayName AS instructor,
                a.email AS instructorEmail,
                DATE_FORMAT(ge.timeFrom, '%H:%i') AS timeFrom,
                DATE_FORMAT(ge.timeTo, '%H:%i') AS timeTo,
                DATE_ADD(TIMESTAMP(ge.date, ge.timeFrom), INTERVAL -80 MINUTE) AS calculatedDeadline,
                (SELECT COUNT(id) FROM groupExcercisesParticipants AS gep WHERE gep.groupExcercise = ge.id) AS attendance,
                ge.minAttendance,
                ge.price,
                ge.description
            FROM groupExcercises AS ge
            LEFT JOIN adminLogin AS a ON a.id = ge.instructor
            WHERE
                (SELECT COUNT(id) FROM groupExcercisesParticipants AS gep WHERE gep.groupExcercise = ge.id) < ge.minAttendance AND
                fn_groupExcercises_getUnderoccupancyEmailTime(ge.date, ge.timeFrom) <= NOW() AND
                TIMESTAMP(ge.date, ge.timeTo) >= NOW() AND
                ge.underoccupancyAlertSent IS NULL AND
                ge.canceled = 0
            ORDER BY ge.date, ge.timeFrom";
$stmt = $dbh->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

if (count($results) === 0) {
    die();
}

$mail = new PHPMailer\PHPMailer\PHPmailer();
$mail->Host = "localhost";
$mail->SMTPKeepAlive = true;
$mail->CharSet = "utf-8";
$mail->IsHTML(true);
$mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");

foreach ($results as $result) {
    $mail->clearAllRecipients();
    $mail->addAddress($result->instructorEmail, htmlspecialchars($result->instructor));
    $mail->addAddress("jiri.havlicky@fyzioland.cz");
    
    $mail->Subject = "Upozornění na nedostatek přihlášených na skupinovém cvičení";
    $mail->Priority = 1;
    
    $mail->Body = "<html><head></head><body style='padding: 10px;'>";
    $mail->Body .= "Rezervační systém zjistil, že na skupinové cvičení je přihlášeno pouze <b>{$result->attendance} z {$result->minAttendance} potřebných</b> přihlášených účastníků.<br><br>";
    $mail->Body .= "<b>Instrukce pro lektora:</b> vyčkejte na zprávu ze společnosti Fyzioland ohledně informace, zda se skupinové cvičení koná či nikoliv, ozveme se Vám obratem.<br><br>";

    $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px; width: 100px;'>Název cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->title . "</td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . date("j. n. Y", strtotime($result->date)) . "</td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Čas</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . date("G:i", strtotime($result->timeFrom)) . " - " . date("G:i", strtotime($result->timeTo)) . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Lektor</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . $result->instructor . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Cena</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . number_format($result->price, 0, ",", " ") . " Kč</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Popis cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->description) . "</td></tr>";
    $mail->Body .= "</table><br><br>";
    
    $mail->Body .= "<table style='border-collapse: collapse; margin-top: 15px;'><tr>";
    $mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
    $mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
    $mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
    $mail->Body .= "Kašovická 1608/4, 104 00 Praha 22 - Uhříněves<br>";
    $mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
    $mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
    $mail->Body .= "</td>";
    $mail->Body .= "</tr></table>";
    $mail->Body .= "</body></html>";

    $mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");
    
    $mail->Send();
    
    
    $query = "UPDATE groupExcercises SET underoccupancyAlertSent = NOW() WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $result->id, PDO::PARAM_INT);
    $stmt->execute();
    
    echo $mail->Body;
    echo "<hr>";
}
