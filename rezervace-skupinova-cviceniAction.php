<?php

require_once "php/class.settings.php";
require_once "php/PHPMailer/PHPMailer.php";
require_once "php/PHPMailer/Exception.php";
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

if (isset($_POST["submit"])) {
    if (!isset($_SESSION["loggedUserId"])) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Tato stránka je přístupná pouze s platným přihlášením.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    }
    
    if (empty($_POST["groupExcerciseId"])) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Tato stránka vyžaduje, aby byla zvoleno skupinové cvičení.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    }
    
    $query = "SELECT COUNT(id) AS count FROM groupExcercisesParticipants WHERE groupExcercise = :groupExcercise AND client = :client";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":groupExcercise", $_POST["groupExcerciseId"], PDO::PARAM_INT);
    $stmt->bindValue(":client", $_SESSION["loggedUserId"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    if (intval($result->count) >= 1) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Na toto skupinové cvičení jste již přihlášeni.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    }
    
    $query = "  SELECT
                    ge.title,
                    ge.date,
                    ge.timeFrom,
                    ge.timeTo,
                    ge.instructor,
                    a.displayName AS instructorName,
                    a.email AS instructorEmail,
                    ge.price,
                    ge.description,
                    ge.capacity,
                    (SELECT COUNT(id) FROM groupExcercisesParticipants WHERE groupExcercise = ge.id) AS occupancy,
                    ge.description,
                    ge.semestralCourse,
                    ge.includePaymentInstructionsIntoEmail,
                    CASE WHEN ge.semestralCourse IS NULL THEN
                        NULL
                    ELSE
                        (SELECT groupExcercisesCount FROM semestralCourses WHERE id = ge.semestralCourse)
                    END AS semestralCourseGroupExcercisesCount
                FROM groupExcercises AS ge
                LEFT JOIN adminLogin AS a ON a.id = ge.instructor
                WHERE ge.id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":id", $_POST["groupExcerciseId"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    if ($result === FALSE) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Vámi vybrané skupinové cvičení neexistuje.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    } else if (intval($result->occupancy) >= intval($result->capacity)) {        
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Je nám líto, ale Vámi zvolené skupinové cvičení je již obsazené.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    }

    $isPartOfSemestralCourse = !empty($result->semestralCourse);
    if ( !$isPartOfSemestralCourse ) {
        $query = "  INSERT INTO groupExcercisesParticipants (
                        groupExcercise,
                        client,
                        note
                    ) VALUES (
                        :groupExcercise,
                        :client,
                        :note
                    )";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":groupExcercise", $_POST["groupExcerciseId"], PDO::PARAM_INT);
        $stmt->bindValue(":client", $_SESSION["loggedUserId"], PDO::PARAM_STR);
        $stmt->bindValue(":note", $_POST["note"], PDO::PARAM_STR);
    } else {
        $query = "  INSERT INTO groupExcercisesParticipants (
                        groupExcercise,
                        client,
                        note
                    )
                    SELECT
                        ge.id,
                        :client,
                        :note
                    FROM groupExcercises AS ge
                    WHERE
                        ge.semestralCourse = :semestralCourse";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":client", $_SESSION["loggedUserId"], PDO::PARAM_STR);
        $stmt->bindValue(":note", $_POST["note"], PDO::PARAM_STR);
        $stmt->bindValue(":semestralCourse", $result->semestralCourse, PDO::PARAM_INT);
    }

    if ($stmt->execute()) {
        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
        
        $mail->Body = "<html><head></head><body style='padding: 10px;'>";
        $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
        if (empty($result->semestralCourse)) {
            $mail->Subject = "Potvrzení rezervace na skupinové cvičení";
            
            $mail->Body .= "děkujeme Vám za Vaši závaznou rezervaci na následující skupinové cvičení:<br><br>";

            $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px; width: 100px;'>Název cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->title . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . date("j. n. Y", strtotime($result->date)) . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Čas</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . date("G:i", strtotime($result->timeFrom)) . " - " . date("G:i", strtotime($result->timeTo)) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Lektor</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . $result->instructorName . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Cena</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . number_format($result->price, 0, ",", " ") . " Kč</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Popis cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->description) . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Poznámka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($_POST["note"]) . "</td></tr>";
            $mail->Body .= "</table>";
        } else {
            $mail->Subject = "Potvrzení přihlášení na kurz";
            
            $mail->Body .= "děkujeme Vám za Vaši závaznou rezervaci na následující kurz:<br><br>";
            
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
        }
        
        if ($result->includePaymentInstructionsIntoEmail === "1") {
            $mail->Body .= "<p><b>Platební instrukce:</b><br>Do 5 kalendářních dní od registrace prosíme proveďte platbu předem na účet č. 2401198774/2010, do poznámky pro příjemce uveďte prosím jméno a příjmení účastníka. V případě potřeby se na nás prosím obraťte telefonicky na čísle 775 910 749 nebo emailem na info@fyzioland.cz</p>";
        }
        
        $mail->Body .= "Děkujeme Vám a&nbsp;těšíme se na Vaši návštěvu.<br><br>";
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

        $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");

        $mail->AddAddress($_SESSION["loggedUserEmail"]);
        //$mail->AddBCC("rezervace@fyzioland.cz");        
        $mail->Send();                       
        
        $_SESSION["registerSuccess"] = "<span class='glyphicon glyphicon-ok' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerSuccess"] .= "Děkujeme, Vaše rezervace byla úspěšně uložena, potvrzení bylo odesláno na Váš e&#8209;mail.";
    }
} else if (isset($_POST["logout"])) {
    if (!isset($_SESSION["loggedUserId"])) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Tato stránka je přístupná pouze s platným přihlášením.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    }
    
    if (empty($_POST["groupExcerciseId"])) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Tato stránka vyžaduje, aby byla zvoleno skupinové cvičení.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    }
    
    $query = "SELECT COUNT(id) AS count FROM groupExcercisesParticipants WHERE groupExcercise = :groupExcercise AND client = :client";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":groupExcercise", $_POST["groupExcerciseId"], PDO::PARAM_INT);
    $stmt->bindValue(":client", $_SESSION["loggedUserId"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    if (intval($result->count) === 0) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Na toto skupinové cvičení nejste přihlášeni, nemůžete se tedy odhlásit.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    }
    
    $query = "  SELECT
                    ge.title,
                    ge.date,
                    DATE_FORMAT(ge.timeFrom, '%H:%i') AS timeFrom,
                    ge.timeTo,
                    ge.instructor,
                    a.displayName AS instructorName,
                    a.email AS instructorEmail,
                    ge.price,
                    ge.description,
                    ge.capacity,
                    (SELECT COUNT(id) FROM groupExcercisesParticipants WHERE groupExcercise = ge.id) AS occupancy,
                    ge.description,
                    fn_groupExcercises_getLogoutDeadline(ge.date, ge.timeFrom) AS logoutDeadline,
                    ge.semestralCourse
                FROM groupExcercises AS ge
                LEFT JOIN adminLogin AS a ON a.id = ge.instructor
                WHERE ge.id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":id", $_POST["groupExcerciseId"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    $logoutDeadline = (new DateTime())->createFromFormat("Y-m-d H:i:s", $result->logoutDeadline);
    
    if ($result === FALSE) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Vámi vybrané skupinové cvičení neexistuje.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    } else if ( $logoutDeadline < (new DateTime()) ) {        
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Je nám líto, ale z tohoto skupinového cvičení bylo možné se nejpozději odhlásit " . $logoutDeadline->format("j. n. Y") . " v&nbsp;" . $logoutDeadline->format("G:i") . ". Kontaktujte nás prosím telefonicky.";
        
        header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
        die();
    }
    
    $isPartOfSemestralCourse = !empty($result->semestralCourse);
    if ( !$isPartOfSemestralCourse ) {
        $query = "  DELETE FROM groupExcercisesParticipants
                    WHERE 
                        groupExcercise = :groupExcercise AND
                        client = :client";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":groupExcercise", $_POST["groupExcerciseId"], PDO::PARAM_INT);
        $stmt->bindValue(":client", $_SESSION["loggedUserId"], PDO::PARAM_STR);
    } else {
        $query = "  DELETE FROM groupExcercisesParticipants
                    WHERE 
                        groupExcercise IN (SELECT id FROM groupExcercises WHERE semestralCourse = :semestralCourse) AND
                        client = :client";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":semestralCourse", $result->semestralCourse, PDO::PARAM_INT);
        $stmt->bindValue(":client", $_SESSION["loggedUserId"], PDO::PARAM_STR);
    }
    
    if ($stmt->execute()) {
        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
        $mail->Subject = "Zrušení rezervace na skupinové cvičení";
        
        $mail->Body = "<html><head></head><body style='padding: 10px;'>";
        $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
        $mail->Body .= "Vaše rezervace na skupinové cvičení byla úspěšně <b>zrušena</b>.<br><br>";
        
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px; width: 100px;'>Název cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->title . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . date("j. n. Y", strtotime($result->date)) . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Čas</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . date("G:i", strtotime($result->timeFrom)) . " - " . date("G:i", strtotime($result->timeTo)) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Lektor</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . $result->instructorName . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Cena</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . number_format($result->price, 0, ",", " ") . " Kč</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Popis cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->description) . "</td></tr>";
        $mail->Body .= "</table><br><br>";
        
        $mail->Body .= "Rádi Vás v případě Vašeho zájmu přivítáme v jiném termínu.<br><br>";
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

        $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");

        $mail->AddAddress($_SESSION["loggedUserEmail"]);
        $mail->AddBCC("rezervace@fyzioland.cz");        
        $mail->Send();
        
        $_SESSION["registerSuccess"] = "<span class='glyphicon glyphicon-ok' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerSuccess"] .= "Děkujeme, Vaše rezervace byla úspěšně zrušena, potvrzení bylo odesláno na Váš e&#8209;mail.";
    }            
}

// Poslání info o celkovém počtu přihlášených klientů na skup. cvičení na email rezervace a instruktorovi
    
    $query = "  SELECT
                    ge.title,
                    ge.date,
                    DATE_FORMAT(ge.date, '%d. %m. %Y') as dateFormatted,
                    DATE_FORMAT(ge.timeFrom, '%H:%i') AS timeFrom,
                    ge.timeTo,
                    ge.instructor,
                    a.displayName AS instructorName,
                    a.email AS instructorEmail,
                    a.id as instructorId,
                    ge.price,
                    ge.description,
                    ge.capacity,
                    (SELECT COUNT(id) FROM groupExcercisesParticipants WHERE groupExcercise = ge.id) AS occupancy,
                    ge.description,
                    fn_groupExcercises_getLogoutDeadline(ge.date, ge.timeFrom) AS logoutDeadline,
                    ge.semestralCourse
                FROM groupExcercises AS ge
                LEFT JOIN adminLogin AS a ON a.id = ge.instructor
                WHERE ge.id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":id", $_POST["groupExcerciseId"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");

    $mail->Body = "<html><head></head><body style='padding: 10px;'>";
    $mail->Subject = "Změna počtu SKUP: " . $result->title. ' ' . $result->dateFormatted . ' ' . $result->occupancy . '/'. $result->capacity;
    $mail->Body .= "Vážená kolegyně, vážený kolego,<br><br>";
    $mail->Body .= "došlo k přihlášení/odhlášení klienta na následující skupinové cvičení:<br><br>";

    $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Klient</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . $_SESSION["loggedUserName"] . ' ' . $_SESSION["loggedUserSurname"] . "</b></td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px; width: 100px;'>Název cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->title . "</td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . date("j. n. Y", strtotime($result->date)) . "</td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Čas</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . date("G:i", strtotime($result->timeFrom)) . " - " . date("G:i", strtotime($result->timeTo)) . "</td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Lektor</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->instructorName . "</td></tr>";    
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Počet přihlášených</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->occupancy) . "</td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Celková kapacita</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($result->capacity) . "</td></tr>";
    $mail->Body .= "</table>";
    
    $mail->Body .= "<br>";
    $mail->Body .= "Automatický email systému Fyzioland.<br><br>";

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

    $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");

    $mail->ClearAllRecipients();
    $mail->AddAddress("rezervace@fyzioland.cz");
    if($result->instructorId !=3) {
        $mail->AddAddress($result->instructorEmail);
    }
    
    $mail->Send(); 

header("Location: rezervace-skupinova-cviceni?date=" . $_POST["backTo"]);
die();



