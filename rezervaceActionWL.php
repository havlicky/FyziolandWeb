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

if (empty($_POST["hsh"])) {
    $messageBox->addText("Požadovaná stránka není samostatně přístupná, využijte prosím odkaz v e-mailu.");
    $messageBox->setClass("alert-danger");

    $_SESSION["messageBox"] = $messageBox;
    header("Location: index.php");
    die();
} else {
    $query = "  
        SELECT
            c.id as client,
            AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
            AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
            AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email,
            AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
            l.WLdate as date,
            HOUR(l.WLtime) as hour,
            MINUTE(l.WLtime) as minute,                                        
            adminLogin.displayName AS therapist,          
            adminLogin.email AS therapistEmail,
            adminLogin.newReservationAlerts,                    
            l.therapist as therapistId,
            TIME_FORMAT(l.WLtime, '%H:%i') as time,
            l.service
        FROM logWL AS l
        LEFT JOIN adminLogin ON adminLogin.id = l.therapist                
        LEFT JOIN clients c ON c.id = l.client
        WHERE                    
            l.hash = :hash ";
    $stmt = $dbh->prepare($query);    
    $stmt->bindParam(":hash", $_POST["hsh"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (count($results) === 0) {
        $messageBox->addText("V databázi se nenachází žádný odpovídající požadavek na čekací listině.");
        $messageBox->setClass("alert-danger");

        $_SESSION["messageBox"] = $messageBox;
        header("Location: rezervace");
        die();
    }
    
    $result = $results[0];
    // kontrola, že na zvolený čas ještě rezervace žádná neexistuje
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
    $stmt->bindParam(":date", $result->date, PDO::PARAM_STR);
    $stmt->bindParam(":hour", $result->hour, PDO::PARAM_INT);
    $stmt->bindParam(":minute", $result->minute, PDO::PARAM_INT);
    $stmt->bindParam(":personnel", $result->therapistId, PDO::PARAM_INT);
    $stmt->execute();
    $resultRes = $stmt->fetch(PDO::FETCH_ASSOC);
    if (intval($resultRes["count"]) === 1) {
        $messageBox->addText("Termín je již bohužel obsazen. Je nám líto. Vyčkejte, až Vás budeme informovat o dalším uvolněném termínu.");
        $messageBox->setClass("alert-danger");        
        $_SESSION["messageBox"] = $messageBox;
        
        //doplnit zaslání emailu, že termín byl již obsazen.¨        
        header("Location: index.php");
        die();
    }
    
    // pokud kontroly projdou, může se rezervace uložit    
    $source = 'Z čekací listiny';
    $alert = 'email';
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
                    :alert,
                    :personalDetailsAgreement,
                    :deleteHash,
                    :creationTimestamp,
                    :source,
                    AES_ENCRYPT(:IPaddress, '" . Settings::$mySqlAESpassword . "')
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":client", $result->client, PDO::PARAM_STR);
    $stmt->bindParam(":name", $result->name, PDO::PARAM_STR);
    $stmt->bindParam(":surname", $result->surname, PDO::PARAM_STR);
    $stmt->bindValue(":email", $result->email, PDO::PARAM_STR);
    $stmt->bindValue(":phone", $result->phone, PDO::PARAM_STR);
    $stmt->bindParam(":date", $result->date, PDO::PARAM_STR);
    $stmt->bindParam(":hour", $result->hour, PDO::PARAM_INT);
    $stmt->bindParam(":minute", $result->minute, PDO::PARAM_INT);
    $stmt->bindParam(":personnel", $result->therapistId, PDO::PARAM_INT);
    $stmt->bindParam(":service", $result->service, PDO::PARAM_INT);    
    $stmt->bindParam(":alert", $alert, PDO::PARAM_STR);
    $stmt->bindValue(":personalDetailsAgreement", 1, PDO::PARAM_INT);
    $stmt->bindParam(":source", $source, PDO::PARAM_STR);
    
    $deleteHash = bin2hex(openssl_random_pseudo_bytes(20));
    $stmt->bindParam(":deleteHash", $deleteHash, PDO::PARAM_STR);

    $stmt->bindValue(":creationTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $stmt->bindParam(":IPaddress", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
        
   if ($stmt->execute()) {
                       
        $query = "UPDATE logWL SET utilized = 1 WHERE hash=:hash  ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":hash", $_POST["hsh"], PDO::PARAM_STR);
        $stmt->execute();      
        
        //smaže všechny neposlané nabídky z WL, aby nedošlo k překročení frekvence (ve froně mohou stát na poslaní další SMS, které se stávají vytvořením rezervace nerelevantní) a automat to případně znovu "nakrmí", pokud by ještě po vytvořené rezervaci nebyla splněna frekvence
        $query = "DELETE FROM logWL WHERE client = :client AND actionTimestamp IS NULL";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":client", $result->client, PDO::PARAM_STR);
        $stmt->execute();      

        //info pro mailing
        $therapist = $result->therapistId;
        $service = $result->service;
        $send_email_to_Client = 1;      
        $clientEmail = $result->email;
        $name = $result->name;
        $surname = $result->surname;
        $phone = $result->phone;
        $hour = $result->hour;
        $minute = $result->minute;
        $date = $result->date;
        $hash = $deleteHash;
        $note = $result->note;      
        $deleteReason = null;
        $addMessageBox = 'N';
        $source = 'Klient z nabídky z čekací listiny';
        
        $type = 'new'; //poslat emaily, které se mají poslat při nové rezervaci
        
        include("emailResSend.php");

        $messageBox->addText("Rezervace byla v pořádku vytvořena, potvrzovací e&#8209;mail byl odeslán.");
        $_SESSION["messageBox"] = $messageBox;        
        header("Location: index.php");
        die();
   }
}