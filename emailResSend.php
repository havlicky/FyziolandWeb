<?php

//Dotaz na doplňující informace o rezervaci, které potřebuji do emailu
$query = "  
    SELECT
        displayName as therapist,
        newReservationAlerts as therapistEmailYN,
        email as therapistEmail,
        (SELECT name FROM services WHERE id = :service) as serviceName                        
    FROM adminLogin
    WHERE id = :therapist
 ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":therapist", $therapist, PDO::PARAM_INT);
$stmt->bindParam(":service", $service, PDO::PARAM_INT);
$stmt->execute();
$resAddInfo = $stmt->fetch(PDO::FETCH_OBJ);

// Dotaz na všechny emaily, které se mají s vybraným typem služby poslat
$query = " SELECT email FROM emailsToServices LEFT JOIN emailParamRes ON emailParamRes.id = emailsToServices.email WHERE service = :service AND emailParamRes.type = :type";
$stmt = $dbh->prepare($query);        
$stmt->bindParam(":service", $service, PDO::PARAM_INT);
$stmt->bindParam(":type", $type, PDO::PARAM_STR);
$stmt->execute();
$emails = $stmt->fetchALL(PDO::FETCH_OBJ);

If(count($emails) == 0) {
    if($addMessageBox == 'Y') {$messageBox->addText("Nebyly nalezeny žádné emaily k odeslání");}
            
} else {
    foreach ($emails as $email) {
        //$messageBox->addText("Odesílám emaily:<br>");
        //zjištění parametrů emailu, který chci poslat
        $query = " 
            SELECT                    
                shortcut,
                cc,
                subject,
                sendToAllowedTherapist,
                sendToAllowedClient,
                textClient,
                textTherapist
            FROM emailParamRes WHERE id = :email";
        $stmt = $dbh->prepare($query);        
        $stmt->bindParam(":email", $email->email, PDO::PARAM_INT);            
        $stmt->execute();
        $emailParam = $stmt->fetch(PDO::FETCH_OBJ);

        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);            
        $mail->ClearAllRecipients();

        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");

        //nastavení příjemců (adresát + skrytá kopie)
        if ($emailParam->sendToAllowedClient == 1 && $send_email_to_Client == 1 && !empty($clientEmail)) {$mail->AddAddress($clientEmail, $name . " " . $surname);}     
        //  $email->sendToAllowedClient == 1 = obecné určení, zda se jedná typ emailu určeného pro klienta a 
        //  $send_email_to_Client === "1" znamená, že klient při rezervaci zaklikl, že chce dostat notifikace emailem (neb v administraci jsem při zrušení odsouhlasil poslání emailem)
        //  == musí být splněny obě podmínky, aby email odešel na klienta          
        if (!empty($emailParam->cc)) {
            $recipientsArray = explode(";",$emailParam->cc); 
            foreach($recipientsArray as $recipient) {
                $mail->addBCC($recipient);
            }                        
        }              

        //nastavení parametrů v předmětu emailu
        $replacements = array(
            "#date#" => (new DateTime())->createFromFormat("Y-m-d", $date)->format("j. n. Y"),
            "#time#" => $hour . ":" . str_pad($minute, 2, "0", STR_PAD_LEFT)
        );
        foreach ($replacements as $search => $replace) {
             $emailParam->subject = str_replace($search, $replace, $emailParam->subject);
        }

        $mail->Subject =  $emailParam->subject;   

        //nastavení parametrů v těle emailu
        $replacements = array(
            "#name#" => $name,
            "#surname#" => $surname,
            "#date#" => (new DateTime())->createFromFormat("Y-m-d", $date)->format("j. n. Y"),
            "#time#" => $hour . ":" . str_pad($minute, 2, "0", STR_PAD_LEFT),
            "#phone#" => $phone,
            "#email#" => $clientEmail,
            "#hash#" => $hash,
            "#therapist#" => $resAddInfo ->therapist,
            "#service#" => $resAddInfo ->serviceName,
            "#note#" => $note,
            "#source#" => $source,
            "#deletereason#" => $deleteReason,
            "#deletesource#" => $deleteSource
        );
        foreach ($replacements as $search => $replace) {
             $emailParam->textClient = str_replace($search, $replace, $emailParam->textClient);
        }

        $mail->Body .= $emailParam->textClient;   
        $mail->AddEmbeddedImage("Logo.png", "fyzioland_logo", "fyzioland_logo.png");

        if (!empty($clientEmail) && !empty($emailParam->cc)) { 
            try {
                if ($mail->Send()) {
                    if($addMessageBox == 'Y') {$messageBox->addText("Odeslán e&#8209;mail - " . $emailParam->shortcut);}
                } else {
                    if($addMessageBox == 'Y') {$messageBox->addText("Bohužel se NEPODAŘILO odeslat  e&#8209;mail -" . $emailParam->shortcut);}
                    if($addMessageBox == 'Y') {$messageBox->setClass("alert-danger");}
                }
            } catch (Exception $e) {
                unset($e);
                if($addMessageBox == 'Y') {$messageBox->addText("Bohužel se NEPODAŘILO odeslat e&#8209;mail - ". $emailParam->shortcut);}
                if($addMessageBox == 'Y') {$messageBox->setClass("alert-danger");}
            }
        }

        if (empty($clientEmail)) {if($addMessageBox == 'Y') {$messageBox->addText("E&#8209;mailová adresa klienta nebyla zadána, a&nbsp;tedy e&#8209;mail klientovi nemohl být odeslán - ". $emailParam->shortcut);}}                        
        if ($send_email_to_Client === "0" ) {if($addMessageBox == 'Y') {$messageBox->addText("E&#8209;mail NEBYL podle požadavku odeslán - ". $emailParam->shortcut);}}

        // poslání emailu - speciální verze pro terapeuta (pokud email patří také terapeutovi a terapeut má nastaveno, že může dostat kopii emailu)
        if($emailParam->sendToAllowedTherapist == 1 && $resAddInfo ->therapistEmailYN == 1) {
            $mail = new PHPMailer\PHPMailer\PHPmailer();
            $mail->Host = "localhost";
            $mail->SMTPKeepAlive = true;
            $mail->CharSet = "utf-8";
            $mail->IsHTML(true);            
            $mail->ClearAllRecipients();

            $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");

            if (!empty($clientEmail)) {$mail->AddAddress($resAddInfo->therapistEmail);}            

            $mail->Subject =  $emailParam->subject;

            $replacements = array(
                "#name#" => $name,
                "#surname#" => $surname,
                "#date#" => (new DateTime())->createFromFormat("Y-m-d", $date)->format("j. n. Y"),
                "#time#" => $hour . ":" . str_pad($minute, 2, "0", STR_PAD_LEFT),
                "#hash#" => $hash,
                "#therapist#" => $resAddInfo ->therapist,
                "#service#" => $resAddInfo ->serviceName,
                "#note#" => $note               
            );
            foreach ($replacements as $search => $replace) {
                 $emailParam->textClient = str_replace($search, $replace, $emailParam->textClient);
            }

            $mail->Body .= $emailParam->textTherapist;                
            $mail->AddEmbeddedImage("Logo.png", "fyzioland_logo", "fyzioland_logo.png");
            if(!empty($therapistEmail)) { $mail->Send();}
        }
    }
}