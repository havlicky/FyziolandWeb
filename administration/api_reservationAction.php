<?php

// spouští se pokud dělám rezervace z admin na webu ale z nového WL
// nemohl jsem použít api, které volá ordinace, protože tam je kontrola autentizace, kterou neprojdu

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
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
               
if ($_POST["resid"]>0 and $_POST["delconf"]==="true") {
    //tady se zneaktivní existující rezervace
    //echo "Chci smazat rezervaci";                
    $query = "  
        UPDATE reservations
            SET 
                active = 0,
                deleteUser = :user,
                deleteReason = :deletereason,
                deleteTimeStamp = NOW()
            WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_POST["resid"], PDO::PARAM_INT);
    $stmt->bindParam(":user", $_POST["user"], PDO::PARAM_INT);
    $stmt->bindParam(":deletereason", $_POST["deletereason"], PDO::PARAM_STR);
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
                adminLogin.displayName AS therapist,
                adminLogin.email AS therapistEmail,
                adminLogin.cancelReservationAlerts,
                services.name AS service,
                r.service as serviceId,
                r.personnel as therapistId,
                a.displayName as deleteUser,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as time
            FROM reservations AS r
            LEFT JOIN adminLogin ON adminLogin.id = r.personnel
            LEFT JOIN adminLogin a ON a.id = r.deleteUser
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
        $stmt->bindParam(":person", $result->therapistId, PDO::PARAM_INT);
        $stmt->execute();                

        //***************************************************
        //ODESLÁNÍ EMAILŮ V SOUVISLOSTI SE ZRUŠENOU REZERVACÍ
        //***************************************************       
                
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
        $deleteReason = $_POST["deletereason"];        
        $addMessageBox = 'N';
        $deleteSource = $result->deleteUser . ' (res-WL)';
        
        $type = 'cancelGeneral'; //poslat emaily, které se mají poslat při zrušení rezervace z administrátorského rozhraní
        include("../emailResSend.php");    
    } else {/*echo "Rezervaci se nepodařilo odstranit";*/}
} else {
    //tady se ukládá nová rezervace     
    // nejprve kontrola, že na zvolený čas ještě rezervace žádná neexistuje
    echo "Jdu vytvořit rezervaci. ";

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
        echo "Na tento čas již rezervace existuje";                       
        die();
    }

    // pokud kontroly projdou, může se rezervace uložit
    echo "Kontrola, že je slot volný. ";
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
                    CONCAT(:displayName, ' (res-WL)'),
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
    $stmt->bindParam(":displayName", $_POST["displayName"], PDO::PARAM_STR);

    $deleteHash = bin2hex(openssl_random_pseudo_bytes(20));
    $stmt->bindParam(":deleteHash", $deleteHash, PDO::PARAM_STR);

    $stmt->bindValue(":creationTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $stmt->bindParam(":IPaddress", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);

    if ($stmt->execute()) {
         echo "Rezervace vytvořena. ";
        $query = "  SELECT
                        (SELECT displayName FROM adminLogin WHERE id = :therapist) AS therapist,
                        (SELECT newReservationAlerts FROM adminLogin WHERE id = :therapist) AS therapistEmailYN,
                        (SELECT name FROM services WHERE id = :service) AS service,
                        (SELECT id FROM services WHERE id = :service) AS serviceId";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":therapist", $_POST["person"], PDO::PARAM_INT);
        $stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
        $stmt->execute();
        $resultDetail = $stmt->fetch(PDO::FETCH_OBJ);

        //poslání informačního emailu terapeutovi bez tel. čísla a emailu
        echo "Posílám info emaily";
        
        $therapist = $_POST["person"];
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
        $source = $_POST["displayName"] . ' (res-WL)';
        
        $type = 'new'; //poslat emaily, které se mají poslat při nové rezervaci        
        include("../emailResSend.php");        
    } else {echo "Rezervace NEBYLA vytvořena";}        
}