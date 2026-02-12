<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
$messageBox = new MessageBox();

session_start();

if (isset($_GET["geId"])) {
   
    $query = "  SELECT
        AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
        AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
        AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email,
        ge.id,
        ge.title,
        ge.date,                
        ge.timeFrom,
        ge.timeTo,
        a.displayName AS instructorName,
        ge.price,
        ge.semestralCourse,
        ge.includePaymentInstructionsIntoEmail,
        CASE WHEN ge.semestralCourse IS NULL THEN
                NULL
            ELSE
                (SELECT groupExcercisesCount FROM semestralCourses WHERE id = ge.semestralCourse)
            END AS semestralCourseGroupExcercisesCount,
        ge.description
    FROM groupExcercises AS ge
    LEFT JOIN adminLogin AS a ON a.id = ge.instructor
    LEFT JOIN groupExcercisesParticipants gep ON gep.groupExcercise = ge.id
    LEFT JOIN clients c ON c.id = gep.client
    WHERE
        ge.id = :geId AND
        c.id = :clientId";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":geId", $_GET["geId"], PDO::PARAM_INT);
    $stmt->bindParam(":clientId", $_GET["clientId"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");
    $mail->AddAddress($result->email, $result->name . " " . $result->surname);
    $mail->AddBCC("rezervace@fyzioland.cz");            

    $mail->Subject = "Potvrzení rezervace na skupinové cvičení";

    $mail->Body = "<html><head></head><body style='padding: 10px;'>";
    $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";

    if (empty($result->semestralCourse)) {
        $mail->Body .= "rádi bychom Vás informovali, že jsme pro Vás provedli rezervaci na následující skupinové cvičení:<br><br>";

        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px; width: 100px;'>Název cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->title . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . date("j. n. Y", strtotime($result->date)) . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Čas</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . date("G:i", strtotime($result->timeFrom)) . " - " . date("G:i", strtotime($result->timeTo)) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Lektor</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . $result->instructorName . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Cena</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . number_format($result->price, 0, ",", " ") . " Kč</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Popis cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->description) . "</td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br><br>";
        
     } else {
         $mail->Subject = "Potvrzení přihlášení na kurz";
            
        $mail->Body .= "rádi bychom Vás informovali, že jsme pro Vás provedli závaznou rezervaci na následující kurz:<br><br>";

        $query = "SELECT date, timeFrom, timeTo FROM groupExcercises WHERE semestralCourse = :semestralCourse ORDER BY date, timeFrom, timeTo";
        $stmtDates = $dbh->prepare($query);
        $stmtDates->bindValue(":semestralCourse", $result->semestralCourse, PDO::PARAM_INT);
        $stmtDates->execute();
        $dates = $stmtDates->fetchAll(PDO::FETCH_OBJ);
        $datesToEmail = array();
        foreach ($dates as $date) {
            $date = new DateTime($date->date);
            $timeFrom = date("G:i", strtotime($result->timeFrom));
            $timeTo = date("G:i", strtotime($result->timeTo));

            $datesToEmail[] = $date->format("j. n. Y") . " " . $timeFrom . " - " . $timeTo;
        }

        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px; width: 100px;'>Název kurzu</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->title . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Počet lekcí</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->semestralCourseGroupExcercisesCount . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Rozpis lekcí</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . implode(",<br>", $datesToEmail) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Lektor</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . $result->instructorName . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Cena</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . number_format($result->price, 0, ",", " ") . " Kč</b></td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br><br>";
    }
    
    if ($result->includePaymentInstructionsIntoEmail === "1") {
            $mail->Body .= "<p><b>Platební instrukce:</b><br>Do 5 kalendářních dní od registrace prosíme proveďte platbu předem na účet č. 2401198774/2010, do poznámky pro příjemce uveďte prosím jméno a příjmení účastníka.</p>";
        }

    $mail->Body .= "<table style='border-collapse: collapse;'><tr>";
    $mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
    $mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
    $mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
    $mail->Body .= "Kašovická 1608/4, 104 00 Praha 10 - Uhříněves<br>";
    $mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
    $mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
    $mail->Body .= "</td>";
    $mail->Body .= "</tr></table><br>";
    $mail->Body .= "Více informací o našich službách najdete na našem webu <a href='fyzioland.cz'>fyzioland.cz</a>";
    $mail->Body .= "</body></html>";

    $mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");        

    $mail->Send();
    $messageBox->addText("Klientovi byl odeslán notifikační e-mail.");                
    }
else {
   $messageBox->addText("Email se NEPODAŘILO odeslat.");
   $messageBox->setClass("alert-danger");
}

$_SESSION["messageBox"] = $messageBox;

header("Location: viewGroupReservations.php");