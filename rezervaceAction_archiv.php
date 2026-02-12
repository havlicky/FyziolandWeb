<?php

require_once "php/class.messagebox.php";
require_once "php/class.settings.php";
require_once "php/PHPMailer/PHPMailer.php";
$messageBox = new MessageBox();

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
    // kontrola Google reCAPTCHA
    if (empty($_POST["g-recaptcha-response"])) {
        $messageBox->addText("Prosíme vyplňte pole s Google Captchou, bez ní nelze pokračovat.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            "secret" => "6LcR5TQUAAAAAEcQa2c32DAkyG56D3vtMeX2gCc2",
            "response" => $_POST["g-recaptcha-response"]
        )));
        $responseRaw = curl_exec($ch);
        curl_close($ch);
        
        $response = json_decode($responseRaw);
        if ($response->success !== TRUE) {
            $messageBox->addText("Selhalo ověření Google Captcha.");
            $messageBox->setClass("alert-danger");

            $_SESSION["messageBox"] = $messageBox;
            header("Location: rezervace");
            die();
        }
    }
    
    // povinný souhlas se zpracováním osobních údajů
    if ($_POST["personalDetailsAgreement"] !== "1") {
        $messageBox->addText("Pro založení rezervace je třeba vyjádřit souhlas se zpracováním osobních údajů.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    // pole musí být povinně vyplněna
    if ( empty($_POST["name"]) || empty($_POST["surname"]) || empty($_POST["email"])  || empty($_POST["phone"])) {
        $messageBox->addText("Pole <b>jméno</b>, <b>příjmení</b> a <b>e-mail</b> musí být povinně vyplněny. Vytvořte prosím rezervaci znovu, tentokrát s vyplněním všech povinných polí.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    // kontrola, že na zvolený čas je pro zvolenou službu ještě dostupný termín
    $query = "  SELECT
                    rps.service,
                    SUM((SELECT COUNT(r.id) FROM reservations AS r WHERE r.personnel = rps.person AND r.active = 1 AND date = :date1 AND CAST(CONCAT(r.hour, ':', r.minute) as time) = :time1)) AS pocetRezervaci,
                    SUM((SELECT COUNT(pat.id) FROM personAvailabilityTimetable AS pat WHERE pat.person = rps.person AND pat.date = :date2 AND pat.time = :time2)) AS pocetSlotu
                FROM relationPersonService AS rps
                WHERE
                    rps.service = :service AND
                    (:person1 IS NULL OR rps.person = :person2)
                GROUP BY
                    rps.service";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":date1", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time1", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":date2", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time2", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
    $stmt->bindValue(":person1", empty($_POST["personnel"]) ? NULL : $_POST["personnel"], PDO::PARAM_INT);
    $stmt->bindValue(":person2", empty($_POST["personnel"]) ? NULL : $_POST["personnel"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    if ($result === FALSE) {
        $messageBox->addText("Tento termín nelze rezervovat, vyberte prosím jiný.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    } else if ( intval($result->pocetRezervaci) >= intval($result->pocetSlotu) ) {
        $messageBox->addText("Tento termín rezervace je již bohužel obsazený, vyberte prosím jiný.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    // pokud kontroly projdou, může se rezervace uložit, musíme nejprve zjistit, komu rezervaci přiřadíme
    $query = "  SELECT
                    a.id,
                    a.newReservationAlerts,
                    a.email,
                    a.displayName,
                    s.id AS serviceId,
                    s.name AS service
                FROM relationPersonService AS rps
                LEFT JOIN adminLogin AS a ON a.id = rps.person
                LEFT JOIN services AS s ON s.id = rps.service
                WHERE
                    rps.service = :service AND
                    (:person1 IS NULL OR rps.person = :person2) AND
                    NOT EXISTS (
                        SELECT r.id
                        FROM reservations AS r
                        WHERE
                            r.personnel = rps.person AND
                            r.active = 1 AND
                            r.date = :date1 AND
                            CAST(CONCAT(r.hour, ':', r.minute) as time) = :time1
                    ) AND
                    EXISTS (
                        SELECT pat.id
                        FROM personAvailabilityTimetable AS pat
                        WHERE
                            pat.person = rps.person AND
                            pat.date = :date2 AND
                            pat.time = :time2
                    )
                ORDER BY rps.priority
                LIMIT 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
    $stmt->bindValue(":person1", empty($_POST["personnel"]) ? NULL : $_POST["personnel"], PDO::PARAM_INT);
    $stmt->bindValue(":person2", empty($_POST["personnel"]) ? NULL : $_POST["personnel"], PDO::PARAM_INT);
    $stmt->bindParam(":date1", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time1", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":date2", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time2", $_POST["time"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    if ($result === FALSE) {
        $messageBox->addText("Je nám líto, ale tento termín nelze rezervovat, vyberte prosím jiný.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    } else {
        $terapistId = intval($result->id);
        $terapistName = $result->displayName;
        $terapistEmail = $result->email;
        $chosenServiceName = $result->service;
        $chosenServiceId = $result->serviceId;
        $terapistEmailYN = $result->newReservationAlerts;
    }
    
    
    $query = "  INSERT INTO reservations (
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
                    'web',
                    AES_ENCRYPT(:IPaddress, '" . Settings::$mySqlAESpassword . "')
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
    $stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
    $stmt->bindValue(":phone", empty($_POST["phone"]) ? NULL : $_POST["phone"], PDO::PARAM_STR);
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_INT);
    $stmt->bindParam(":minute", $_POST["minute"], PDO::PARAM_INT);
    $stmt->bindParam(":personnel", $terapistId, PDO::PARAM_INT);
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
    $stmt->bindValue(":note", empty($_POST["note"]) ? NULL : $_POST["note"], PDO::PARAM_STR);
    $stmt->bindParam(":alert", $_POST["alert-type"], PDO::PARAM_STR);
    $stmt->bindValue(":personalDetailsAgreement", 1, PDO::PARAM_INT);

    $deleteHash = bin2hex(openssl_random_pseudo_bytes(20));
    $stmt->bindParam(":deleteHash", $deleteHash, PDO::PARAM_STR);

    $stmt->bindValue(":creationTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $stmt->bindParam(":IPaddress", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);

    if ($stmt->execute()) {
        $messageBox->addText("Vaše rezervace byla v pořádku vytvořena. Při Vaší návštěve se Vám bude věnovat terapeut/terapeutka <b>$terapistName</b>.<br><br>Současně byl odeslán potvrzovací e-mail na Vámi uvedenou e-mailovou adresu.");
        $messageBox->setDelay(7000);
        
        // email o rezervaci klientovi
        $mail = new PHPMailer\PHPMailer\PHPmailer();
        $mail->Host = "localhost";
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = "utf-8";
        $mail->IsHTML(true);
        
        $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
        $mail->AddAddress($_POST["email"], $_POST["name"] . " " . $_POST["surname"]);
        $mail->AddBCC("rezervace@fyzioland.cz");                    
    
        $mail->Subject = "Potvrzení rezervace - Fyzioland";
        $mail->Body = "Vážená klientko, vážený kliente,<br>";
        $mail->Body .= "potvrzujeme vytvoření rezervace s následujícími údaji:<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["name"] . " " . $_POST["surname"] . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Telefonní kontakt</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["phone"] . "</td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["hour"] . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($terapistName) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($chosenServiceName) . "</b></td></tr>";
        $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($_POST["note"]) . "</td></tr>";
        $mail->Body .= "</table>";
        $mail->Body .= "<br>";
        $mail->Body .= "V případě, že je třeba rezervaci z vážných důvodů zrušit, můžete tak učinit maximálně 48 hodin předem. Ke zrušení rezervace dojde kliknutím na následující odkaz <a href='https://fyzioland.cz/cancelReservation.php?email=" . urlencode($_POST["email"]) . "&hsh=" . $deleteHash . "'>zrušení rezervace</a>. ";
        $mail->Body .= "Potvrzení o zrušení rezervace Vám bude zasláno obratem na Váš email.";
        $mail->Body .= "<br><br>";
        $mail->Body .= "<b>Storno podmínky:</b> V případě, že se klient nedostaví na terapii nebo dojde ke zrušení rezervace ze strany klienta z libovolného důvodu později než 48 hodin před zahájením terapie, je klient povinen v souladu se všeobecnými obchodními podmínkami uhradit společnosti Fyzioland 100% z ceny terapie jako storno poplatek. Rezervací termínu na terapii vyjadřuje klient souhlas s těmito storno podmínkami.";
        $mail->Body .= "<br><br>";
        $mail->Body .= "<b>Důležité upozornění pro nezletilé klienty:</b> V případě, že se jedná o rezervaci pro nezletilého klienta, je nezbytné, aby jeho zákonný zástupce byl přítomen po celou dobu terapie v prostorách Fyzioland.";
        $mail->Body .= "<br><br>";        
        $mail->Body .= "Rádi bychom Vás také informovali, že od <b>3.10.2022 vstupuje v platnost nový ceník našich služeb</b>, jehož aktuální znění naleznete v horním menu na našich webových stránkách www.fyzioland.cz.";
        $mail->Body .= "<br><br>";        
        $mail->Body .= "Děkujeme Vám za Vaši rezervaci a těšíme se na setkání.<br><br>";
        $mail->Body .= "<table style='border-collapse: collapse;'><tr>";
        $mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
        $mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
        $mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
        $mail->Body .= "Kašovická 1608/4, 104 00 Praha 22 - Uhříněves<br>";
        $mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
        $mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
        $mail->Body .= "</td>";
        $mail->Body .= "</tr></table>";
        
        $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");
        
        $mail->Send();
        
        // Ergoterapie vstupní vyšetření - email pro klienta
        if ($chosenServiceId == 10) {
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
                
            $mail->Body .= "Ergoterapii (včetně vstupního vyšetření) provádíme pouze na základě indikace lékařem, či klinickým psychologem. V případě, že ještě doporučení od lékaře nemáte, rádi Vám poradíme, na koho se obrátit - doporučení nebo poukaz Vám vystaví praktický lékař (pediatr), psycholog, logoped, neurolog, rehabilitační lékař, ortoped nebo psychiatr. Pokud již máte doporučení k rehabilitaci uvedenou v lékařské zprávě nebo zprávě klinického psychologa, poprosíme Vás o její kopii. Lze ji také zaslat předem formou přílohy na emailovou adresu info@fyzioland.cz nebo přiložit jako soubor v rámci vyplňování senzorického dotazníku. Pokud se Vám nepodaří do doby vstupního vyšetření doporučení získat, kontaktujte nás, prosím, na tel. čísle 775 910 749.<br><br>";
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

            $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");            
            $mail->Send();    
        }
        
        // Info Email pro klienta - Ergoterapie - kontrolní vyšetření
        if ($chosenServiceId == 12) {  
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

            $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");            
            $mail->Send();            
        }
        
        // email o rezervaci terapeutovi bez tel. a email kontaktu
        if ($terapistEmailYN == 1) {
            $mail = new PHPMailer\PHPMailer\PHPmailer();
            $mail->Host = "localhost";
            $mail->SMTPKeepAlive = true;
            $mail->CharSet = "utf-8";
            $mail->IsHTML(true);

            $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");                
            $mail->ClearAllRecipients();
            $mail->AddAddress($terapistEmail);
            //$mail->AddBCC("rezervace@fyzioland.cz");

            $mail->Subject = "Nová rezervace klienta";
            $mail->Body = "Vážená kolegyně, vážený kolego,<br>";
            $mail->Body .= "zasíláme informaci o nové rezervaci klenta:<br><br>";
            $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $_POST["name"] . " " . $_POST["surname"] . "</td></tr>";            
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $_POST["date"])->format("j. n. Y") . " " . $_POST["hour"] . ":" . str_pad($_POST["minute"], 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($terapistName) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($chosenServiceName) . "</b></td></tr>";
            $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Vaše poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($_POST["note"]) . "</td></tr>";
            $mail->Body .= "</table>";
            $mail->Body .= "<br>";            
            $mail->Body .= "V případě, že je třeba rezervaci z vážných důvodů zrušit, můžete tak učinit maximálně 48 hodin předem. Ke zrušení rezervace dojde kliknutím na následující odkaz <a href='https://fyzioland.cz/cancelReservation.php?email=" . urlencode($_POST["email"]) . "&hsh=" . $deleteHash . "'>zrušení rezervace</a>. ";
            $mail->Body .= "Potvrzení o zrušení rezervace Vám bude zasláno obratem na Váš email.";
            $mail->Body .= "<br><br>";
            $mail->Body .= "<b>Storno podmínky:</b> V případě, že se klient nedostaví na terapii nebo dojde ke zrušení rezervace ze strany klienta z libovolného důvodu později než 48 hodin před zahájením terapie, je klient povinen v souladu se všeobecnými obchodními podmínkami uhradit společnosti Fyzioland 100% z ceny terapie jako storno poplatek. Rezervací termínu na terapii vyjadřuje klient souhlas s těmito storno podmínkami.";
            $mail->Body .= "<br><br>";
            $mail->Body .= "<b>Důležité upozornění pro nezletilé klienty:</b> V případě, že se jedná o rezervaci pro nezletilého klienta, je nezbytné, aby jeho zákonný zástupce byl přítomen po celou dobu terapie v prostorách Fyzioland.";
            $mail->Body .= "<br><br>";        
            $mail->Body .= "Děkujeme Vám za Vaši rezervaci a těšíme se na setkání.<br><br>";
            $mail->Body .= "<table style='border-collapse: collapse;'><tr>";
            $mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
            $mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
            $mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
            $mail->Body .= "Kašovická 1608/4, 104 00 Praha 22 - Uhříněves<br>";
            $mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
            $mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
            $mail->Body .= "</td>";
            $mail->Body .= "</tr></table>";

            $mail->AddEmbeddedImage("img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");

            $mail->Send();
        }
        
    } else {
        $messageBox->addText("Rezervaci se nepodařilo vytvořit. Zkuste to prosím znovu");
        $messageBox->setClass("alert-danger");
    }
}

$_SESSION["messageBox"] = $messageBox;
header("Location: rezervace");