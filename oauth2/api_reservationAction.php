<?php

// include our OAuth2 Server object
require_once __DIR__.'/server.php';
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $server->getResponse()->send();
    die;
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

if ($_GET["action"] === "reservationAction") {                   
    if ($_POST["resid"]>0 and $_POST["delconf"]==="true") {
        //tady se zneaktivní existující rezervace
        //echo "Chci smazat rezervaci";                
        $query = "  UPDATE reservations
                    SET active = 0
                    WHERE id = :id";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":id", $_POST["resid"], PDO::PARAM_INT);
        if ($stmt->execute()) {                                                                                                      
            $query = "  SELECT
                    r.id,
                    AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                    AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                    AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                    r.date,
                    r.hour,
                    r.minute,
                    r.note,                    
                    adminLogin.displayName AS therapistt,
                    adminLogin.email AS therapisttEmail,
                    adminLogin.cancelReservationAlerts,
                    services.name AS service,
                    r.service as serviceId,
                    r.personnel as therapisttId,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as time
                FROM reservations AS r
                LEFT JOIN adminLogin ON adminLogin.id = r.personnel
                LEFT JOIN services ON services.id = r.service
                WHERE
                    r.id = :id";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":id", $_POST["resid"], PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            // smazání přiřazené místnosti v personAvailabilityTimeTable                
            $query = "  UPDATE personAvailabilityTimetable SET room = NULL 
                            WHERE 
                        time = :time AND
                        date = :date AND
                        person = :person
                        ";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":time", $result->time, PDO::PARAM_STR);
            $stmt->bindParam(":date", $result->date, PDO::PARAM_STR);
            $stmt->bindParam(":person", $result->therapisttId, PDO::PARAM_INT);
            $stmt->execute();                

            $therapist = $result->therapistId;
            $service = $result->serviceId;
            $send_email_to_Client = 1;      
            $clientEmail = $result->email;
            $name = $result->name;
            $surname = $result->surname;
            $phone = $result->phone;
            $hour = $result->hour;
            $minute = $result->minute;
            $date = $result->date;
            $hash = null;
            $note = $result->note;      
            //$deleteReason = $_POST["deletereason"];
            $addMessageBox = 'N';
            
            $type = 'cancelGeneral'; //poslat emaily, které se mají poslat při zrušení rezervace z adminu
            include("../emailResSend.php");                                                                               
        } else {
            //rezervaci se nepodařilo zrušit        
        }
    }
    else {
    //tady se ukládá nová rezervace     
    // nejprve kontrola, že na zvolený čas ještě rezervace žádná neexistuje
        //echo "Jdu vytvořit rezervaci";
               
        $query = "  SELECT
                        COUNT(*) AS count
                    FROM reservations
                    WHERE
                        date = :date AND
                        hour = :hour AND
                        minute = :minute AND
                        personnel = :personnel AND
                        active = 1";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
        $stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_INT);
        $stmt->bindParam(":minute", $_POST["minute"], PDO::PARAM_INT);
        $stmt->bindParam(":personnel", $_POST["person"], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (intval($result["count"]) === 1) {
            //echo "Na tento čas již rezervace existuje";                       
            die();
        }

        // pokud kontroly projdou, může se rezervace uložit
        //echo "Kontrola, že je slot volný";
        $alert = "email";
        $query = "  INSERT INTO reservations (
                        client,
                        name,
                        surname,
                        email,
                        phone,
                        date,
                        hour,
                        minute,
                        personnel,
                        service,
                        note,
                        alert,
                        personalDetailsAgreement,
                        deleteHash,
                        creationTimestamp,
                        source,
                        IPaddress
                    ) VALUES (
                        :client,
                        AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                        :date,
                        :hour,
                        :minute,                         
                        :personnel,
                        :service,
                        :note,
                        :alert,
                        :personalDetailsAgreement,
                        :deleteHash,
                        :creationTimestamp,
                        CONCAT('ordinace', ' ', :displayName),
                        AES_ENCRYPT(:IPaddress, '" . Settings::$mySqlAESpassword . "')
                    )";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":client", empty($_POST["client"]) ? NULL : $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
        $stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
        $stmt->bindValue(":email", empty($_POST["email"]) ? NULL : $_POST["email"], PDO::PARAM_STR);
        $stmt->bindValue(":phone", empty($_POST["phone"]) ? NULL : $_POST["phone"], PDO::PARAM_STR);
        $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
        $stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_INT);
        $stmt->bindParam(":minute", $_POST["minute"], PDO::PARAM_INT);
        
        $stmt->bindParam(":personnel", $_POST["person"], PDO::PARAM_INT);
        $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
        $stmt->bindValue(":note", empty($_POST["note"]) ? NULL : $_POST["note"], PDO::PARAM_STR);
        $stmt->bindParam(":alert", $alert , PDO::PARAM_STR);
        $stmt->bindValue(":personalDetailsAgreement", 1, PDO::PARAM_INT);
        $stmt->bindParam(":displayName", $_POST["user"], PDO::PARAM_STR);

        $deleteHash = bin2hex(openssl_random_pseudo_bytes(20));
        $stmt->bindParam(":deleteHash", $deleteHash, PDO::PARAM_STR);

        $stmt->bindValue(":creationTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
        $stmt->bindParam(":IPaddress", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);

        if ($stmt->execute()) {
            $query = "  SELECT
                            (SELECT displayName FROM adminLogin WHERE id = :therapistt) AS therapistt,
                            (SELECT newReservationAlerts FROM adminLogin WHERE id = :therapistt) AS therapisttEmailYN,
                            (SELECT name FROM services WHERE id = :service) AS service,
                            (SELECT id FROM services WHERE id = :service) AS serviceId";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":therapistt", $_POST["person"], PDO::PARAM_INT);
            $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
            $stmt->execute();
            $resultDetail = $stmt->fetch(PDO::FETCH_OBJ);

            //poslání informačního emailu terapeutovi bez tel. čísla a emailu
            //echo "Posílám info email terapeutovi";
            if ($resultDetail->therapisttEmailYN == 1) {                                

                $mail = new PHPMailer\PHPMailer\PHPmailer();
                $mail->Host = "localhost";
                $mail->SMTPKeepAlive = true;
                $mail->CharSet = "utf-8";
                $mail->IsHTML(true);

                $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
                $mail->Subject = "Potvrzení rezervace - ". (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["hour"] . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT);

                $mail->Body = "<html><head></head><body style='padding: 10px;'>";
                $mail->Body .= "Vážená kolegyně, vážený kolego,<br><br>";
                $mail->Body .= "rádi bychom Vás informovali, že byla vytvořena následující rezervace:<br><br>";
                $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["name"] . " " . $_POST["surname"] . "</td></tr>";            
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["hour"] . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->therapistt) . "</b></td></tr>";
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->service) . "</b></td></tr>";
                $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($_POST["note"]) . "</td></tr>";                
                $mail->Body .= "</table>";
                $mail->Body .= "<br>";
                $mail->Body .= "V případě, že je třeba rezervaci z&nbsp;vážných důvodů zrušit, můžete tak učinit maximálně 48 hodin předem. Ke zrušení rezervace dojde kliknutím na následující odkaz <a href='https://fyzioland.cz/cancelReservation.php?email=" . urlencode($_POST["email"]) . "&hsh=" . $deleteHash . "'>zrušení rezervace</a>. ";
                $mail->Body .= "Potvrzení o zrušení rezervace Vám bude zasláno obratem na Váš email.";
                $mail->Body .= "<br><br>";
                $mail->Body .= "<b>Storno podmínky:</b> V případě, že se klient nedostaví na terapii nebo dojde ke zrušení rezervace ze strany klienta z libovolného důvodu později než 48 hodin před zahájením terapie, je klient povinen v souladu se všeobecnými obchodními podmínkami uhradit společnosti Fyzioland 100% z ceny terapie jako storno poplatek. Rezervací termínu na terapii vyjadřuje klient souhlas s těmito storno podmínkami.";
                $mail->Body .= "<br><br>";
                $mail->Body .= "<b>Důležité upozornění pro nezletilé klienty:</b> V případě, že se jedná o rezervaci pro nezletilého klienta, je nezbytné, aby jeho zákonný zástupce byl přítomen po celou dobu terapie v prostorách Fyzioland.";
                $mail->Body .= "<br><br>";                            
                $mail->Body .= "Důležité upozornění: V případě, že se jedná o rezervaci pro nezletilého klienta, je nezbytné, aby jeho zákonný zástupce byl přítomen po celou dobu terapie v prostorách Fyzioland.";
                $mail->Body .= "<br><br>";  
                $mail->Body .= "Automatický email systému Fyzioland<br><br>";
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

                $queryTerapistMail = "  SELECT
                                            displayName,
                                            email
                                        FROM adminLogin
                                        WHERE
                                            id = :therapistt";
                $stmtTerapistMail = $dbh->prepare($queryTerapistMail);
                $stmtTerapistMail->bindParam(":therapistt", $_POST["person"], PDO::PARAM_INT);
                $stmtTerapistMail->execute();
                $resultTerapistMail = $stmtTerapistMail->fetch(PDO::FETCH_OBJ);

                $mail->AddAddress($resultTerapistMail->email, $resultTerapistMail->displayName);
                $mail->Send();
            }

            // Info Email pro klienta - Ergoterapie - vstupní vyšetření (první návštěva)                    
            if ($resultDetail->serviceId == 10) {  
                //echo "Posílám info email klientovi - Ergo 1. návštěva";
                $mail = new PHPMailer\PHPMailer\PHPmailer();
                $mail->Host = "localhost";
                $mail->SMTPKeepAlive = true;
                $mail->CharSet = "utf-8";
                $mail->IsHTML(true);

                $mail->SetFrom("info@fyzioland.cz", "Fyzioland");
                $mail->ClearAllRecipients();
                $mail->AddAddress($_POST["email"], $_POST["name"] . " " . $_POST["surname"]);
                $mail->AddBCC("rezervace@fyzioland.cz");
                $mail->Subject = "Ergoterapie - informace k první návštěvě (vstupní vyšetření)";

                $mail->Body = "<html><head></head><body style='padding: 10px;'>";
                $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
                $mail->Body .= "děkujeme Vám za zájem o naše služby a dovolte nám Vás krátce seznámit s průběhem Vaší první návštěvy. Rehabilitační klinika Fyzioland se nachází na adrese Kašovická 4, Praha 22 - Uhříněves.<br><br>";
                $mail->Body .= "Vstupní vyšetření trvá 55 minut a probíhá nenásilnou formou pomocí herních aktivit s dítětem a rozhovorem mezi rodičem a terapeutem. Na tomto základě následně připraví terapeut individuální rehabilitační plán. Rehabilitační plán je sestaven z řady na sebe navazujících bloků a zohledňuje cíle, kterých má být terapií dosaženo. Na navazující návštěvě je rehabilitační plán detailně vysvětlen a diskutován s rodiči před zahájením jednotlivých navazujících terapií.<br><br>";

                $mail->Body .= "Před samotným vstupním vyšetřením Vás požádáme o vyplnění senzorického dotazníku. Na vyplnění dotazníku si prosím vyhraďte 30-45 minut. Senzorický dotazník je pro nás důležitým podkladem pro vstupní vyšetření. Dotazník je proto potřeba vyplnit nejpozději 24 hodin před vstupním vyšetřením. Dotazník je elektronický a zobrazí se Vám po kliknutí na následující odkaz: ";
                $mail->Body .= "<a href='https://www.fyzioland.cz/dotaznik/index.php?id=" . $deleteHash . "'>senzorický dotazník</a><br><br>";

                $mail->Body .= "Ergoterapii (včetně vstupního vyšetření) provádíme pouze na základě indikace lékařem, či klinickým psychologem. V případě, že ještě doporučení od lékaře nemáte, rádi Vám poradíme, na koho se obrátit - doporučení nebo poukaz Vám vystaví praktický lékař (pediatr), psycholog, logoped, neurolog, rehabilitační lékař, ortoped nebo psychiatr. Pokud již máte doporučení k ergoterapii nebo rehabilitaci uvedenou v lékařské zprávě nebo zprávě klinického psychologa, poprosíme Vás o její kopii. Lze ji také zaslat předem formou přílohy na emailovou adresu info@fyzioland.cz nebo přiložit jako soubor v rámci vyplňování senzorického dotazníku. Pokud se Vám nepodaří do doby vstupního vyšetření doporučení získat, kontaktujte nás, prosím, na tel. čísle 775 910 749. ";
            
                $mail->Body .= "Kliknutím na následující odkaz se Vám zobrazí jednoduchý průvodce, který Vám usnadní získání indikace od lékaře: ";
                $mail->Body .= "<a href='https://www.fyzioland.cz/PruvodceIndikace1-1.pdf'>Průvodce indikací od lékaře</a><br><br>";

                $mail->Body .= "A co s sebou? Pohodlné tepláčky, legínky, šortky, protiskluzové ponožky (sportovní obuv není třeba a k některým aktivitám ani není vhodná) a již zmíněné lékařské zprávy a doporučení od lékaře.<br><br>";
                $mail->Body .= "Jakékoliv dotazy Vám velice rádi zodpovíme na tel. čísle:  +420 775 920 475 nebo emailem na adrese info@fyzioland.cz.<br><br>";
                $mail->Body .= "Těšíme se na Vás.<br><br>";

                $mail->Body .= "Automatický email systému Fyzioland<br><br>";
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
            }
            
            // Info Email pro klienta - Ergoterapie - kontrolní vyšetření
            if ($resultDetail->serviceId == 12) {  
                $mail = new PHPMailer\PHPMailer\PHPmailer();
                $mail->Host = "localhost";
                $mail->SMTPKeepAlive = true;
                $mail->CharSet = "utf-8";
                $mail->IsHTML(true);

                $mail->SetFrom("info@fyzioland.cz", "Fyzioland");
                $mail->ClearAllRecipients();
                $mail->AddAddress($_POST["email"], $_POST["name"] . " " . $_POST["surname"]);
                $mail->AddBCC("rezervace@fyzioland.cz");
                $mail->Subject = "Ergoterapie - informace ke kontrolnímu vyšetření";

                $mail->Body = "<html><head></head><body style='padding: 10px;'>";
                $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
                $mail->Body .= "děkujeme za rezervaci Vašeho termínu na kontrolní vyšetření. Rádi bychom Vás požádali o vyplnění senzorického dotazníku. Dotazník, prosím, vyplňte nejpozději dva pracovní dny před samotným kontrolním vyšetřením. Stejný dotazník jste již vyplňoval/a v rámci vstupního vyšetření a jeho opětovné vyplnění nám slouží k vyhodnocení procesu rehabilitace. Na jeho vyplnění si, prosím, vyhraďte 30-45 minut. Senzorický dotazník je pro nás jedním z důležitých podkladů pro celé kontrolní vyšetření. Dotazník je elektronický a zobrazí se Vám po kliknutí na následující odkaz: ";
                $mail->Body .= "<a href='https://www.fyzioland.cz/dotaznik/index.php?id=" . $deleteHash . "'>senzorický dotazník</a><br><br>";            
                $mail->Body .= "Jakékoliv dotazy Vám velice rádi zodpovíme na tel. čísle:  +420 775 910 749 nebo emailem na adrese info@fyzioland.cz.<br><br>";
                $mail->Body .= "Těšíme se na Vás.<br><br>";

                $mail->Body .= "Automatický email systému Fyzioland<br><br>";
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
            }

            //email o vytvořené rezervaci na rezervace@fyzioland.cz (včetně telefonu a emailové adresy
            //echo "Posílám info email na rezervace@fyzioland o rezervaci";
            $mail = new PHPMailer\PHPMailer\PHPmailer();
            $mail->Host = "localhost";
            $mail->SMTPKeepAlive = true;
            $mail->CharSet = "utf-8";
            $mail->IsHTML(true);

            $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");

            $mail->Subject = "Potvrzení rezervace - ". (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["hour"] . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT);

            $mail->Body = "<html><head></head><body style='padding: 10px;'>";
            $mail->Body .= "Vážená klientko, vážený kliente,<br><br>";
            $mail->Body .= "rádi bychom Vás informovali, že jsme pro Vás vytvořili následující rezervaci:<br><br>";
            $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["name"] . " " . $_POST["surname"] . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Telefonní kontakt</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["phone"] . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["hour"] . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->therapistt) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($resultDetail->service) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($_POST["note"]) . "</td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Rezervaci vytvořil</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $resultDetail->therapistt . "</td></tr>";
            $mail->Body .= "</table>";
            $mail->Body .= "<br>";
            $mail->Body .= "V případě, že je třeba rezervaci z&nbsp;vážných důvodů zrušit, můžete tak učinit maximálně 48 hodin předem. Ke zrušení rezervace dojde kliknutím na následující odkaz <a href='https://fyzioland.cz/cancelReservation.php?email=" . urlencode($_POST["email"]) . "&hsh=" . $deleteHash . "'>zrušení rezervace</a>. ";
            $mail->Body .= "Potvrzení o zrušení rezervace Vám bude zasláno obratem na Váš email.";
            $mail->Body .= "<br><br>";
            $mail->Body .= "<b>Storno podmínky:</b> V případě, že se klient nedostaví na terapii nebo dojde ke zrušení rezervace ze strany klienta z libovolného důvodu později než 48 hodin před zahájením terapie, je klient povinen v souladu se všeobecnými obchodními podmínkami uhradit společnosti Fyzioland 100% z ceny terapie jako storno poplatek. Rezervací termínu na terapii vyjadřuje klient souhlas s těmito storno podmínkami.";
            $mail->Body .= "<br><br>";
            $mail->Body .= "Důležité upozornění: V případě, že se jedná o rezervaci pro nezletilého klienta, je nezbytné, aby jeho zákonný zástupce byl přítomen po celou dobu terapie v prostorách Fyzioland.";
            $mail->Body .= "<br><br>";  
            //$mail->Body .= "Rádi bychom Vás také informovali, že dne <b>3.10.2022 vstoupil v platnost nový ceník našich služeb</b>, jehož aktuální znění naleznete v horním menu na našich webových stránkách www.fyzioland.cz.";
            //$mail->Body .= "<br><br>";        
            $mail->Body .= "Děkujeme Vám za Vaši rezervaci a&nbsp;těšíme se na Vaši návštěvu.<br><br>";
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
            $mail->AddAddress("rezervace@fyzioland.cz", "Fyzioland - rezervace");

            $mail->Send();

            // email o vytvořené rezervaci klientovi
            // echo "Posílám info email klientovi o rezervaci";                       
            
            if ( $send_email ) {                                
                
                if (!empty($_POST["email"])) { 
                    try {
                        $mail->ClearAllRecipients();
                        $mail->AddAddress($_POST["email"], $_POST["name"] . " " . $_POST["surname"]);
                        if ($mail->Send()) {
                            //echo "Email klientovi odeslán";                            
                        } else {
                            //echo "Email klientovi NEodeslán";
                        }
                    } catch (Exception $e) {
                        unset($e);
                    }
                } else {

                }
            } else {

            }
        } else {
            //echo "Rezervace NEBYLA vytvořena";
        }        
    }
}
//echo "Dojel jsem až do konce";  
//echo json_encode($result);
  
//header("Location: viewReservations.php?date=" . $_POST["backTo"]);