<?php

// include our OAuth2 Server object
require_once __DIR__.'/server.php';
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $server->getResponse()->send();
    die;
}

require_once "../php/class.settings.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

if ($_GET["action"] === "get") {
    $query = "  SELECT
                    id,
                    AES_DECRYPT(name, '" . Settings::$mySqlAESpassword . "') AS name,
                    AES_DECRYPT(surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                    AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') AS email,
                    AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                    fyzduration,
                    difficulty,
                    rehBed,
                    lastEditDate
                FROM clients
                WHERE lastEditDate >= :lastCheck
                ORDER BY id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":lastCheck", $_POST["lastCheck"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} else if ($_GET["action"] === "put") {
    $clients = json_decode($_POST["clients"]);
    $clienttherapists = json_decode($_POST["clienttherapists"]);
    $clientwlparams = json_decode($_POST["clientwlparams"]);
    if (count($clients) > 0) {
        foreach ($clients as $client) {
            // zjištění, zda již klient z lokálu je v tabulce
            $query = "SELECT COUNT(id) AS count FROM clients WHERE id = :id";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":id", $client->id, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            
            if ($result->count === "1") {
                $query = "  UPDATE clients SET
                                name = AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                                surname = AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                                email = AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                                phone = AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                                fyzduration = :fyzduration,
                                rehBed = :rehBed,
                                difficulty = :difficulty,
                                date = :date,
                                lastEditDate = :lastEditDate
                            WHERE
                                id = :id";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":name", $client->name, PDO::PARAM_STR);
                $stmt->bindParam(":surname", $client->surname, PDO::PARAM_STR);
                $stmt->bindParam(":email", $client->email, PDO::PARAM_STR);
                $stmt->bindParam(":phone", $client->phone, PDO::PARAM_STR);
                $stmt->bindParam(":id", $client->id, PDO::PARAM_STR);
                $stmt->bindParam(":fyzduration", $client->fyzduration, PDO::PARAM_INT);
                $stmt->bindParam(":rehBed", $client->rehBed, PDO::PARAM_STR);
                $stmt->bindParam(":difficulty", $client->difficulty, PDO::PARAM_INT);
                $stmt->bindValue(":date", empty($client->birthday) ? NULL : $client->birthday, PDO::PARAM_STR);
                $stmt->bindParam(":lastEditDate", $client->lastEditDate, PDO::PARAM_STR);
                $stmt->execute();
            } else {
                //jedná se o nového klienta
                // je nutno mu také nastavit updateSlot = automat
                $query = "  INSERT INTO clients (
                                id,
                                name,
                                surname,
                                email,
                                phone,
                                fyzduration,
                                rehBed,
                                difficulty,
                                slotTypes,
                                date,
                                lastEditDate
                            ) VALUES (
                                :id,
                                AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                                AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                                AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                                AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                                :fyzduration,
                                :rehBed,                                
                                :difficulty,
                                'auto',
                                :date,
                                :lastEditDate
                            )";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":id", $client->id, PDO::PARAM_STR);
                $stmt->bindParam(":name", $client->name, PDO::PARAM_STR);
                $stmt->bindParam(":surname", $client->surname, PDO::PARAM_STR);
                $stmt->bindParam(":email", $client->email, PDO::PARAM_STR);
                $stmt->bindParam(":phone", $client->phone, PDO::PARAM_STR);
                $stmt->bindParam(":difficulty", $client->difficulty, PDO::PARAM_INT);
                $stmt->bindParam(":fyzduration", $client->fyzduration, PDO::PARAM_INT);
                $stmt->bindParam(":rehBed", $client->rehBed, PDO::PARAM_STR);
                $stmt->bindValue(":date", empty($client->birthday) ? NULL : $client->birthday, PDO::PARAM_STR);
                $stmt->bindParam(":lastEditDate", $client->lastEditDate, PDO::PARAM_STR);
                $stmt->execute();
            }
            
            // synchronizace terapeutek z lokální tabulky clienttherapists
            $stmt = $dbh->prepare("DELETE FROM clienttherapists2 WHERE client = :id");
            $stmt->bindParam(":id", $client->id, PDO::PARAM_STR);
            $stmt->execute();
            
            if (count($clienttherapists) > 0) {
                foreach ($clienttherapists as $therapist) {
                    if ($therapist->client == $client->id ) {
                        $query = "INSERT INTO clienttherapists2 (client, therapist, service) VALUES (:client, :therapist, :service)";
                        $stmt = $dbh->prepare($query);
                        $stmt->bindParam(":client", $client->id, PDO::PARAM_STR);
                        $stmt->bindParam(":service", $therapist->service, PDO::PARAM_INT);
                        $stmt->bindParam(":therapist", $therapist->therapist, PDO::PARAM_INT);
                        $stmt->execute();
                    }
                }
            }
            
            // synchronizace tabulky s parametry waiting listu
            $stmt = $dbh->prepare("DELETE FROM clientWLparam WHERE client = :id");
            $stmt->bindParam(":id", $client->id, PDO::PARAM_STR);
            $stmt->execute();
            
            if (count($clientwlparams) > 0) {
                foreach ($clientwlparams as $clientwlparam) {
                    if ($clientwlparam->client == $client->id ) {
                        $query = "INSERT INTO clientWLparam (client, service, activeWL, urgent, freqW, freqM, freqM2, slottype, note, lastEditDate, lastEditUser) VALUES (:client, :service, :activeWL, :urgent, :freqW, :freqM, :freqM2, :slottype, :note, :lastEditDate, :lastEditUser)";
                        $stmt = $dbh->prepare($query);
                        $stmt->bindParam(":client", $client->id, PDO::PARAM_STR);
                        $stmt->bindParam(":service", $clientwlparam->service, PDO::PARAM_INT);
                        $stmt->bindParam(":activeWL", $clientwlparam->activeWL, PDO::PARAM_INT);
                        $stmt->bindParam(":urgent", $clientwlparam->urgent, PDO::PARAM_INT);
                        $stmt->bindValue(":freqW", empty($clientwlparam->freqW) ? NULL : $clientwlparam->freqW, PDO::PARAM_INT);
                        $stmt->bindValue(":freqM", empty($clientwlparam->freqM) ? NULL : $clientwlparam->freqM, PDO::PARAM_INT);
                        $stmt->bindValue(":freqM2", empty($clientwlparam->freqM2) ? NULL : $clientwlparam->freqM2, PDO::PARAM_INT);
                        $stmt->bindValue(":slottype", empty($clientwlparam->slottype) ? NULL : $clientwlparam->slottype, PDO::PARAM_STR);
                        $stmt->bindValue(":note", empty($clientwlparam->note) ? NULL : $clientwlparam->note, PDO::PARAM_STR);
                        $stmt->bindParam(":lastEditUser", $clientwlparam->lastEditDateUser, PDO::PARAM_INT);
                        $stmt->bindParam(":lastEditDate", $clientwlparam->lastEditDate, PDO::PARAM_STR);
                        $stmt->execute();
                    }
                }
            }
        }
    }
} else if ($_GET["action"] === "getReservations") {
    $query = "  SELECT                                        
                    CONCAT(
                    DAY(r.date), 
                    '.', 
                    MONTH(r.date), 
                    '.', 
                    YEAR(r.date), ' '
                    ) as date,
                    r.date as dateformatted,
                    CONCAT(
                    DAY(r.date), 
                    '.', 
                    MONTH(r.date), 
                    '.', 
                    YEAR(r.date), '<br>',
                    ' (', a.shortcut, ' ', DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i'), ')') as date_therapist_time,

                    SUBSTRING(DAYNAME(r.date),1,3) as denTydne,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS timeFrom,                    
                    CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ', AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')) AS clientName,
                    s.name,
                    a.displayName AS personnel,
                    a.shortcut,
                    r.note,
                    r.source,
                    DATE_FORMAT(r.creationTimestamp,'%d.%m.%Y %H:%i') as creationTimestamp,
                    r.id,
                    CONCAT(
                        IF (r.qFinished IS NULL, '', CONCAT(
                                    '<a href=\'php/getQuestResults.php?id=', r.id,				
                                    '\' title=\'Stáhnout výsledky dotazníku ke vstupnímu vyšetření\' name=\'entryExamination\' style=\'margin-left: 5px;\'>',
                                    '<span class=\'glyphicon glyphicon-dashboard\'></span>',
                                    '</a>'
                            )),
                        IF (r.qFinished IS NULL, '', CONCAT(
                                    '<a href=\'#\'',
                                    ' data-idres=\'',
                                    r.id,
                                    '\'',
                                    ' data-resdate=\'',
                                    r.date,
                                    '\'',
                                    '\' title=\'Zobrazit odpovědi dotazníku ke vstupnímu vyšetření\' name=\'entryExaminationQuest\' style=\'margin-left: 5px;\'>',
                                    '<span class=\'glyphicon glyphicon-eye-open\'></span>',
                                    '</a>'
                            )),
                        IF (r.attName = '', '', CONCAT(
                                    '<a href=\'#\'',
                                    ' data-idres=\'',
                                    r.id,
                                    '\'',
                                    '\' title=\'Uložit soubor s indikací od lékaře\' name=\'indicationFileTransfer\' style=\'margin-left: 5px;\'>',
                                    '<span class=\'glyphicon glyphicon-file\'></span>',
                                    '</a>'
                            ))
                    ) as akce
                FROM reservations r
                LEFT JOIN adminLogin a ON a.id = r.personnel
                LEFT JOIN services s ON s.id = r.service
                WHERE
                    (r.client = :clientId OR 
                     AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR
                     AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone    
                    ) AND
                    (CASE
                        WHEN :resType = 'future' THEN CAST(CONCAT(r.date, ' ', r.hour, ':', r.minute) as datetime) >= :dateFrom
                        WHEN :resType2 = 'history' THEN CAST(CONCAT(r.date, ' ', r.hour, ':', r.minute) as datetime) <= :dateFrom
                    END)                        
                        AND 
                    r.active = 1
                ORDER BY
                    	(CASE :resType3 WHEN 'history' THEN r.date END) DESC,
                    	(CASE :resType4 WHEN 'future' THEN r.date END) ASC";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":clientId", $_POST["clientId"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);    
    $stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":resType", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType2", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType3", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType4", $_POST["resType"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo json_encode($results);
    
} else if ($_GET["action"] === "getCancelledReservations") {
    $query = "  SELECT                                        
                    CONCAT(
                    DAY(r.date), 
                    '.', 
                    MONTH(r.date), 
                    '.', 
                    YEAR(r.date)) as date,                         
                    r.date as dateformatted,
                    SUBSTRING(DAYNAME(r.date),1,3) as denTydne,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS timeFrom,                    
                    CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ', AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')) AS clientName,
                    s.name,
                    a.displayName AS personnel,
                    a.shortcut,
                    r.note,
                    r.source,
                   DATE_FORMAT(r.creationTimestamp,'%d.%m.%Y %H:%i') as creationTimestamp,
                   CONCAT(DATE_FORMAT(r.deleteTimestamp,'%d.%m.%Y %H:%i'),' ', IFNULL(al.shortcut, ''), ' ', IFNULL(r.deleteReason,''), ' (', TIMESTAMPDIFF(HOUR, r.deleteTimestamp, DATE_FORMAT(CONCAT(r.date, ' ', r.hour,':',r.minute),'%Y.%m.%d %H:%i')),' hod)' ) as deleteTimestamp,
                   
                    r.id,
                    CONCAT(
                        IF (r.qFinished IS NULL, '', CONCAT(
                                    '<a href=\'php/getQuestResults.php?id=', r.id,				
                                    '\' title=\'Stáhnout výsledky dotazníku ke vstupnímu vyšetření\' name=\'entryExamination\' style=\'margin-left: 5px;\'>',
                                    '<span class=\'glyphicon glyphicon-dashboard\'></span>',
                                    '</a>'
                            )),
                        IF (r.qFinished IS NULL, '', CONCAT(
                                    '<a href=\'#\'',
                                    ' data-idres=\'',
                                    r.id,
                                    '\'',
                                    '\' title=\'Zobrazit odpovědi dotazníku ke vstupnímu vyšetření\' name=\'entryExaminationQuest\' style=\'margin-left: 5px;\'>',
                                    '<span class=\'glyphicon glyphicon-eye-open\'></span>',
                                    '</a>'
                            ))
                    ) as akce
                FROM reservations r
                LEFT JOIN adminLogin a ON a.id = r.personnel
                LEFT JOIN adminLogin al ON al.id = r.deleteUser
                LEFT JOIN services s ON s.id = r.service
                WHERE
                    (r.client = :clientId OR 
                     AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email OR
                     AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') = :phone    
                    ) AND
                    (CASE
                        WHEN :resType = 'future' THEN CAST(CONCAT(r.date, ' ', r.hour, ':', r.minute) as datetime) >= :dateFrom
                        WHEN :resType2 = 'history' THEN CAST(CONCAT(r.date, ' ', r.hour, ':', r.minute) as datetime) <= :dateFrom
                    END) AND                  
                    r.active = 0                
                    ORDER BY
                    	(CASE :resType3 WHEN 'history' THEN r.date END) DESC,
                    	(CASE :resType4 WHEN 'future' THEN r.date END) ASC
                    ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":clientId", $_POST["clientId"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);    
    $stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":resType", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType2", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType3", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType4", $_POST["resType"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo json_encode($results);
    
} else if ($_GET["action"] === "getAttachment") {
    
    $query = "  SELECT
                    r.attName,
                    r.attSize,
                    e.extension,
                    r.attExt
                FROM reservations r
                LEFT JOIN enum_attachmentallowedextensions e ON e.id = r.attExt
                WHERE
                    r.id = :resid";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":resid", $_POST["resid"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);        
    
  
    if ($result === false) {        
        return;
    }
      
    $fileName = "../dotaznik/attachments/" . $_POST["resid"] . ".zip";
    if (file_exists($fileName)) {
        
        //flag do rezervace, že byl soubor přenesen
        $query = "  UPDATE reservations SET recomSaved = 1 WHERE id = :resid";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":resid", $_POST["resid"], PDO::PARAM_INT);
        $stmt->execute();        
                
        $stmt = $dbh->query("SELECT value FROM settings WHERE name = 'zipPassword'");
        $resultPassword = $stmt->fetch(PDO::FETCH_OBJ);
        $zipPassword = $resultPassword->value;
        
        $zip = new ZipArchive();
        $zip->open($fileName);
        $zip->setPassword($zipPassword);
        
        $content = $zip->getFromIndex(0);
        
        $json = new stdClass();
        $json->fileName = $result->attName . "." . $result->extension;
        $json->attName = $result->attName;
        $json->extensionid = $result->attExt;
        $json->attsize = $result->attSize;
        $json->extension = $result->extension;
        $json->fileContent = base64_encode($content);
        
        echo json_encode($json);                            
    }
    
} else if ($_GET["action"] === "getReservationsForAutomat") {
    $query = "  SELECT                                        
                    r.id,
                    r.client,
                    r.date,
                    DATE_FORMAT(r.date,'%d.%m.%Y') as dateFormatted,
                    r.creationTimestamp,
                    r.deleteTimestamp,
                    AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') as name,
                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') as surname,
                    AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') as email,
                    AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') as phone,
                    r.service,
                    r.personnel,
                    r.deleteReason
                FROM reservations r
                
                WHERE                    
                    (
                        r.source = 'web' AND 
                        r.automatTimestampByCreation IS NULL AND 
                        r.creationTimestamp > DATE_ADD(CURDATE(), INTERVAL -5 DAY) AND
                        (r.service = 8 OR r.service = 10 OR r.service = 12)
                    ) OR
                    (
                        r.deleteSource = 'Klient odkazem z emailu' AND 
                        r.automatTimestampByDeletion IS NULL AND 
                        r.deleteTimestamp>DATE_ADD(CURDATE(), INTERVAL -5 DAY)
                    )                                     
                    ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":clientId", $_POST["clientId"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);    
    $stmt->bindParam(":phone", $_POST["phone"], PDO::PARAM_STR);    
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":resType", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType2", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType3", $_POST["resType"], PDO::PARAM_STR);
    $stmt->bindParam(":resType4", $_POST["resType"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo json_encode($results);
} else if ($_GET["action"] === "automatWL") {
    echo('Zahájení skriptu na automat SMS WL:<br>');
    $query = " SELECT value as state FROM settings WHERE name = 'automatWL'";
    $stmt = $dbh->prepare($query);                      
    $stmt->execute();
    $WLstate = $stmt->fetch(PDO::FETCH_OBJ); 
    
    if ($WLstate->state === '0') {
        echo('Automat je vypnut v tabulce settings');
        die();
    }        
         
    $query = "  
        SELECT
            pat.id as patId,
            DATE_FORMAT(pat.date, '%d.%m.%Y') as date,
            pat.time,
            al.shortcut
        FROM personAvailabilityTimetable pat                                        
        LEFT JOIN adminLogin al ON al.id = pat.person

        WHERE                    
            al.activeWL = 1 AND
            al.automatWL = 1 AND
            pat.date >= CURDATE() + INTERVAL 1 DAY AND
            pat.date <= CURRENT_TIMESTAMP() + INTERVAL al.daysaheadWL DAY AND                     
            !EXISTS(SELECT r.id FROM reservations r
                WHERE
                    r.active = 1 AND r.personnel = pat.person AND r.date = pat.date AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = pat.time
                LIMIT 1)
        ";

    $stmt = $dbh->prepare($query);                      
    $stmt->execute();
    $VolneSloty = $stmt->fetchAll(PDO::FETCH_OBJ);                                       
    
    if (count($VolneSloty) == 0 ) {
        echo('Nejsou žádné neobsazené sloty pro čekačku automatem<br>');
    }
    
    foreach ($VolneSloty as $VolnySlot_id) {                
        echo('<br>----------------------------------------------------------<br>');
        echo('Projíždím slot č. ' . $VolnySlot_id->patId . ' '.$VolnySlot_id->date . ' ' . $VolnySlot_id->time . ' ' . $VolnySlot_id->shortcut);
        echo('<br>----------------------------------------------------------<br>');
                
        //info o volném slotu
        $query = "SELECT
                    pat.id as patId,
                    pat.date, 
                    DATE_FORMAT(pat.date,'%d.%m.%Y') as dateFormatted,
                    pat.time, 
                    DATE_FORMAT(pat.time,'%H:%i') as timeFormatted,
                    pat.person as therapistId, 
                    a.displayName as therapist,
                    pat.emailZapnoutSoft,
                    DATEDIFF(pat.date, CURDATE()) as daysToFreeSlot
                FROM personAvailabilityTimetable pat 
                LEFT JOIN adminLogin a ON a.id = pat.person         
                WHERE pat.id = :idSlot ";

        $stmt = $dbh->prepare($query);                                    
        $stmt->bindValue(":idSlot", $VolnySlot_id->patId, PDO::PARAM_INT);
        $stmt->execute();
        $volnySlot = $stmt->fetch(PDO::FETCH_OBJ);

        //test, zda nemám poslat na sebe email, že je potřeba zadat SOFT podmínky:
        if($volnySlot->daysToFreeSlot <=2 && $volnySlot->emailZapnoutSoft == 0) {
            // poslat email a nastavit, že by zaslán
            $mail = new PHPMailer\PHPMailer\PHPmailer();
            $mail->Host = "localhost";
            $mail->SMTPKeepAlive = true;
            $mail->CharSet = "utf-8";
            $mail->IsHTML(true);            
            $mail->ClearAllRecipients();

            $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
            $mail->AddAddress('jiri.havlicky@fyzioland.cz');
            $mail->Subject =  'NASTAV SOFT PODMÍNKY: ' .  $volnySlot->therapist . ' ' . $volnySlot->dateFormatted . ' ' . $volnySlot->timeFormatted; 
            $mail->Body .= "Zaklikej manuálně klienty ze soft podmínek na poslání";
            $mail->Send();
            echo('Odeslán email s upozorněním na zapnutí soft podmínek<br>');
            
            $query = "UPDATE personAvailabilityTimetable SET emailZapnoutSoft = 1 WHERE id = :patId";
            $stmt = $dbh->prepare($query);                                    
            $stmt->bindValue(":patId", $volnySlot->patId, PDO::PARAM_INT);            
            $stmt->execute();            
        }
        
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

        //dotaz na klienty na čekací listině
        // je to stejný dotaz jako sendSMStoALLinSLots
        // !!!!!!!!!!!! BACHA !!!!!!!!!!!!!!!!! ALE DOTAZ JE ROZŠÍŘENÝ O POSLEDNÍ PODMNÍKU, ŽE KLIENT JEŠTĚ NEMÁ ZÁZNAM NA DANÝ SLOT V TABULCE WLLOG A NEBO UŽ MU BYLA ZASLÁNA SMS DÁVNO
        
        $query = "                       
                SELECT
                    c.id as clientId,
                    IF(c.date IS NULL, NULL, ROUND(DATEDIFF(now(), c.date)/365,1)) as age,
                    t.service as serviceId,
                    s.textSMSwl as service,
                    CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ', AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) as client, 
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
                            ORDER BY res.date DESC, res.hour DESC LIMIT 1
                            ), 
                        'ANO', 'NE'
                    ) as cancelByFL

                FROM clienttherapists2 t
                LEFT JOIN clients c ON c.id = t.client
                LEFT JOIN services s ON s.id = t.service
                LEFT JOIN adminLogin a ON a.id = t.therapist
                INNER JOIN clientWLparam p ON p.client = t.client AND t.service = p.service

                WHERE
                    t.therapist = :therapist AND
                    (
                        IFNULL(
                            (SELECT noWL FROM weeknotes WHERE client = t.client AND firstDayOfWeek = :lastMonday),
                            0
                        ) = 0
                    ) AND
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
                        ) < p.freqW OR
                        (
                            (SELECT COUNT(id) FROM reservations r
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
                                    )  AND
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
                                    )  AND
                                   (
                                        IF(t.service = 2, (r.service = 2 OR r.service = 12), r.service = t.service)
                                    ) AND
                                   r.active = 1 AND
                                   r.sooner IS NULL AND
                                   r.date BETWEEN DATE_ADD(:date, INTERVAL - ((SELECT p.freqM2 FROM clientWLparam p WHERE p.client = t.client AND p.service = t.service)-1)*30 DAY) AND DATE_ADD(:date, INTERVAL + 60 DAY)                                   
                           ) < 1
                        )
                    ) AND
                    
                    -- toto je ta podmínky navíc oproti sendSMStoALLinSLots
                    (
                        !EXISTS(
                            -- když existuje tento select, tak nezařazuji do posílání SMS tohoto klienta
                            SELECT 
                               id 
                            FROM logWL 
                            WHERE 
                                client = t.client AND 
                                WLdate = :date AND 
                                WLtime = :time AND 
                                therapist = :therapist AND
                                (                                                                                                            
                                    -- jedná se o blízký termín (od teď za 2 dny včetně) a už má SMS ve frontě nebo doba od poslání poslední SMS už je kratší než 2 dny                                     
                                    -- jinak neobsazené termíny nabízíme hned a pak 1x týdně opakovaná SMS pokud zůstávají neobsazené
                                    -- zde musí být podmínky napsány tak, kdy není potřeba zasílat novou SMS - protože pokud NOT EXISTS tak ji přidáme na seznam SMS k odeslání
                                    IF(
                                        ( SELECT COUNT(id) FROM logWL WHERE logWL.client = t.client AND logWL.WLdate = :date AND logWL.WLtime = :time AND logWL.therapist = :therapist AND logWL.actionTimestamp IS NULL )> 0 OR 
                                        ( DATEDIFF(:date, CURDATE()) <=2 AND DATEDIFF(CURDATE(), (SELECT MAX(logWL.actionTimestamp) FROM logWL WHERE logWL.client = t.client AND logWL.WLdate = :date AND logWL.WLtime = :time AND logWL.therapist = :therapist)) <2 ) OR
                                        ( DATEDIFF(:date, CURDATE()) >2 AND DATEDIFF(CURDATE(), (SELECT MAX(logWL.actionTimestamp) FROM logWL WHERE logWL.client = t.client AND logWL.WLdate = :date AND logWL.WLtime = :time AND logWL.therapist = :therapist)) <7 ),
                                        
                                        TRUE,
                                        FALSE 
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
        $stmt->bindParam(":time", $volnySlot->time, PDO::PARAM_STR);
        $stmt->bindParam(":time2", $volnySlot->time, PDO::PARAM_STR);
        $stmt->bindParam(":date", $volnySlot->date, PDO::PARAM_STR);
        $stmt->bindParam(":therapist", $volnySlot->therapistId, PDO::PARAM_INT);
        $stmt->bindParam(":lastMonday", $lastMondayString, PDO::PARAM_STR);
        $stmt->bindParam(":firstDayOfMonth", $firstDayOfMonthString, PDO::PARAM_STR);
        $stmt->bindParam(":lastDayOfMonth", $lastDayOfMonthString, PDO::PARAM_STR);
        $stmt->execute();
                
        if ($stmt->execute()) {
            echo "Dotaz na klienty bez chyb - OK<br> ";
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);   
        } else {
            echo "Dotaz selhal: <br>";
            printf("Error: %s.\n", $stmt->error);
            die();
        }  


        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);            
        $mail->ClearAllRecipients();

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
        $mail->AddAddress('jiri.havlicky@fyzioland.cz');
        $mail->Subject =  'WL automat - ' .  $volnySlot->therapist . ' ' . $volnySlot->dateFormatted . ' ' . $volnySlot->timeFormatted; 
        $mail->Body .= "Vážený uživateli, <br><br>";
        $mail->Body .= "zasíláme přehled klientů zařazených na čekací listinu pro: <b>" .  $volnySlot->therapist . ' ' . $volnySlot->dateFormatted . ' ' . $volnySlot->timeFormatted . ':</b><br><br>'; 
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";        
                
        $početNaWL = 0;
        foreach ($results as $result) {
            echo('             Vkládám klienta id ' . $result->clientId . ', datum: ' . $volnySlot->date . ', čas: '. $volnySlot->time . ', terapeut: '. $volnySlot->therapistId . '<br>');
            $početNaWL = $početNaWL + 1;
            $hash = bin2hex(openssl_random_pseudo_bytes(5));
            $smsText = 'Fyzioland: Nabídka uvolněného termínu na ' . $result->service . ' '.  
                    $volnySlot->dateFormatted . " v " . $volnySlot->timeFormatted .
                    ' pro ' . $result->client .
                    ' (terapeut '. $volnySlot->therapist . '). Máte-li o termín zájem, klikněte na následující odkaz www.fyzioland.cz/rezervaceActionWLq?hsh=' . $hash .
                    ' pro potvrzení zájmu o rezervaci (pokud bude termín ještě volný, potvrzení o rezervaci Vám přijde emailem). Fyzioland' ;
                    

            $query = "
                INSERT 
                    INTO logWL(client, WLdate, WLtime, service, therapist, message, type, actionTimestamp, hash, creationTimestamp, user)
                    VALUES(:clientId, :WLdate, :WLtime, :serviceId, :therapistId, :message, 'SMS', NULL, :hash, NOW(), 38  )
                    ";

            $stmt = $dbh->prepare($query);                                    
            $stmt->bindValue(":clientId", $result->clientId, PDO::PARAM_STR);
            $stmt->bindValue(":WLdate", $volnySlot->date, PDO::PARAM_STR);
            $stmt->bindValue(":WLtime", $volnySlot->time, PDO::PARAM_STR);
            $stmt->bindValue(":serviceId", $result->serviceId, PDO::PARAM_INT);
            $stmt->bindValue(":therapistId", $volnySlot->therapistId, PDO::PARAM_INT);
            $stmt->bindValue(":message", $smsText, PDO::PARAM_STR);
            $stmt->bindValue(":hash", $hash, PDO::PARAM_STR);
            $stmt->execute();
                        
            $mail->Body .= 
                "<tr>
                    <td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'> ". $volnySlot->therapist ."</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->client. "</td>
                    <td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->service . "</td>
                </tr>";
        }              
        
        $mail->Body .= "</table>";
        $mail->Body .= "<br>";
        $mail->Body .= "Celkový počet oslovených klientů pro tento slot a terapeuta je: " . $početNaWL;
        if($početNaWL > 0) {
            $mail->Send();
            echo('Odeslán email s přehledem oslovených klientů<br>');
        } else {
            echo('Neexistují žádní klienti, kteří by splňovali podmínky na zařazení na čekací listinu<br>');
        }
    }
} else if ($_GET["action"] === "zapniApkuNaCekacku") {
     $query = "  
        SELECT
            COUNT(l.id) as pocet,
            CURTIME() as cas,
            CAST('7:30:00' as time) as timeMIN,
            CAST('19:00:00' as time) AS timeMAX
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
        
        
        echo('Počet SMS ve froně: ' . $pocetVeFronte->pocet . '<br>');
        echo('Aktuální čas: ' . $pocetVeFronte->cas. '<br>');
        echo('Začátek upozornění: ' . $pocetVeFronte->timeMIN. '<br>');
        echo('Konec upozornění: ' . $pocetVeFronte->timeMAX. '<br>');
        echo('<br>')  ;
        
        if($pocetVeFronte->pocet > 0 && $pocetVeFronte->cas >  $pocetVeFronte->timeMIN && $pocetVeFronte->cas <  $pocetVeFronte->timeMAX) {
            echo('Podmínky splněny, posílám email');
            
            $mail = new PHPMailer\PHPMailer\PHPmailer();
            $mail->Host = "localhost";
            $mail->SMTPKeepAlive = true;
            $mail->CharSet = "utf-8";
            $mail->IsHTML(true);            
            $mail->ClearAllRecipients();

            $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
            $mail->AddAddress('jiri.havlicky@fyzioland.cz');
            $mail->Subject =  'Zapni čekačku - ve frontě je ' . $pocetVeFronte->pocet .  ' SMS'; 
            $mail->Body .= "Automatický email ze systému.";            
            $mail->Send();
        } else {            
            echo('Podmínky nesplněny. Email NEPOSÍLÁM.');
        }
}