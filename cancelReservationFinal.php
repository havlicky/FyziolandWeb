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

if (empty($_POST["email"]) || empty($_POST["hsh"])) {
    $messageBox->addText("Požadovaná stránka není samostatně přístupná, využijte prosím odkaz v e-mailu.");
    $messageBox->setClass("alert-danger");

    $_SESSION["messageBox"] = $messageBox;
    header("Location: rezervace");
    die();
} else {
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
                    r.personnel as therapistId,
                    r.service as serviceId,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as time
                FROM reservations AS r
                LEFT JOIN adminLogin ON adminLogin.id = r.personnel
                LEFT JOIN services ON services.id = r.service
                WHERE
                    AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email AND
                    r.deleteHash = :deleteHash AND
                    r.active = 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":email", urldecode($_POST["email"]), PDO::PARAM_STR);
    $stmt->bindParam(":deleteHash", $_POST["hsh"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (count($results) === 0) {
        $messageBox->addText("V databázi se nenachází žádná rezervace odpovídající zadaným parametrům.");
        $messageBox->setClass("alert-danger");

        $_SESSION["messageBox"] = $messageBox;
        header("Location: index");
        die();
    }
    
    $result = $results[0];
    $reservationCancelDeadline = (new DateTime())->createFromFormat("Y-m-d H:i", $result->date . " " . $result->hour . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT))->sub(new DateInterval("P2D"))->format("Y-m-d H:i");
    $now = (new DateTime())->format("Y-m-d H:i");
    
    //info pro mailing
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
    $hash = $_POST["hsh"];
    $note = $result->note;      
    $deleteReason = $_POST["deletereason"];
    $deleteSource = 'Klient odkazem z emailu';
    $addMessageBox = 'N';
    
    if ($reservationCancelDeadline < $now) {
        $messageBox->addText("Vaše rezervace byla zrušena. Platební instrukci na úhradu storno poplatku Vám zašleme e-mailem. Děkujeme za pochopení a úhradu.");
        $messageBox->setClass("alert-success");                            
        $type = 'cancelStorno'; //poslat emaily, které se mají poslat při zrušení rezervace se stornem
        include("emailResSend.php");                
    } else {                
        $messageBox->addText("Vaše rezervace byla bezplatně zrušena. Těšíme se na Vás v jiném termínu.");
        $messageBox->setClass("alert-success");
        $type = 'cancelFree'; //poslat emaily, které se mají poslat při zrušení rezervace bez storna
        include("emailResSend.php");    
    }
    
    //deleteUser = 33 ->uživatel v tabulace AdminLogin, který se jmenuje klient          
    $query = "  UPDATE reservations
                SET
                    active = 0,
                    deleteTimeStamp = :deleteTimestamp,
                    deleteUser = 33,
                    deleteReason = :deleteReason,
                    deleteSource = :deleteSource
                WHERE
                    id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $result->id, PDO::PARAM_INT);
    $stmt->bindValue(":deleteTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $stmt->bindValue(":deleteReason", $_POST["deletereason"], PDO::PARAM_STR);
    $stmt->bindValue(":deleteSource", $deleteSource, PDO::PARAM_STR);
    $stmt->execute();

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
    
    // email JH, pokud klient již nemá žádné navazující rezervace    
    $query = "  SELECT COUNT(id) as pocetRezervaci FROM reservations r                
                WHERE
                    r.date>= curdate() AND
                    r.active = 1 AND                
                    CONVERT(AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') USING 'utf8')  LIKE :email
                ORDER BY r.date, r.hour ASC                        
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":email", $result->email, PDO::PARAM_STR);    
    $stmt->execute();
    $resultReservations = $stmt->fetch(PDO::FETCH_OBJ);

    if($resultReservations->pocetRezervaci == 0 ) {
        $type = 'cancellastres'; //poslat emaily, které se mají poslat při zrušení poslední rezervace
        include("emailResSend.php");    
    }        
    
    $_SESSION["messageBox"] = $messageBox;
    header("Location: index");
    die();
}
