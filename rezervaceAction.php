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
    
    // kontrola, že na zvolený čas je pro zvolenou službu ještě dostupný termín (tj. výpočet, kolik osob umí danou službu a současně má volný slot pro dané datum a čas a současně nemá aktivní rezervaci v dané datum a čas)
    $query = "  SELECT
                    count(rps.person) as pocetVolnychSlotu
                FROM relationPersonService AS rps
                
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
                        ) AND
                        NOT EXISTS (
                               SELECT
                                   pbs.id
                               FROM patBanServices pbs                               
                               WHERE
                                   pbs.patId = (SELECT id FROM personAvailabilityTimetable pat WHERE pat.person = rps.person AND pat.date = :date3 AND pat.time = :time3 ) AND
                                   pbs.serviceId = :service2
                           )           ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":date1", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time1", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":date2", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":date3", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time2", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":time3", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
    $stmt->bindParam(":service2", $_POST["service"], PDO::PARAM_INT);
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
    } else if ( intval($result->pocetVolnychSlotu) == 0 ) {
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
                    ) AND
                    NOT EXISTS (
                        SELECT
                            pbs.id
                        FROM patBanServices pbs                               
                        WHERE
                            pbs.patId = (SELECT id FROM personAvailabilityTimetable pat WHERE pat.person = rps.person AND pat.date = :date3 AND pat.time = :time3 ) AND
                            pbs.serviceId = :service2
                    ) 
                ORDER BY rps.priority
                LIMIT 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
    $stmt->bindParam(":service2", $_POST["service"], PDO::PARAM_INT);
    $stmt->bindValue(":person1", empty($_POST["personnel"]) ? NULL : $_POST["personnel"], PDO::PARAM_INT);
    $stmt->bindValue(":person2", empty($_POST["personnel"]) ? NULL : $_POST["personnel"], PDO::PARAM_INT);
    $stmt->bindParam(":date1", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time1", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":date2", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time2", $_POST["time"], PDO::PARAM_STR);
    $stmt->bindParam(":date3", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":time3", $_POST["time"], PDO::PARAM_STR);
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
        
        //************************************
        //NOVÝ ZPŮSOB POSÍLÁNÍ EMAILŮ - START
        //************************************       
                
        $therapist = $terapistId;
        $service = $_POST["service"];
        $send_email_to_Client = 1;
        $clientEmail = $_POST["email"];
        $name = $_POST["name"];
        $surname = $_POST["surname"];
        $phone = $_POST["phone"];
        $hour = $_POST["hour"];
        $minute = $_POST["minute"];
        $date = $_POST["date"];
        $hash = $deleteHash;
        $note = $_POST["note"];        
        $deleteReason = null;
        $addMessageBox = 'N';
        $source = 'Klient v on-line kalednáři';
        
        $type = 'new'; //poslat emaily, které se mají poslat při nové rezervaci        
        include("emailResSend.php");                                
        
    } else {
        $messageBox->addText("Rezervaci se nepodařilo vytvořit. Zkuste to prosím znovu");
        $messageBox->setClass("alert-danger");
    }
}

$_SESSION["messageBox"] = $messageBox;
header("Location: rezervace");