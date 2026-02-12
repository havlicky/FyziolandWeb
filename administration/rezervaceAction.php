<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
$messageBox = new MessageBox();

session_start();

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}


if (isset($_POST["submit"])) {
    // povinný souhlas se zpracováním osobních údajů
    if ($_POST["personalDetailsAgreement"] !== "1") {
        $messageBox->addText("Pro založení rezervace je třeba vyjádřit souhlas se zpracováním osobních údajů.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: viewReservations");
        die();
    }
    
    // pole musí být povinně vyplněna
    if ( empty($_POST["name"]) || empty($_POST["surname"])) {
        $messageBox->addText("Pole <b>jméno</b> a <b>příjmení</b> musí být povinně vyplněny. Vytvořte prosím rezervaci znovu, tentokrát s vyplněním všech povinných polí.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: viewReservations");
        die();
    }
     
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
    $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
    $stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_INT);
    $stmt->bindParam(":minute", $_POST["minute"], PDO::PARAM_INT);
    $stmt->bindParam(":personnel", $_POST["person"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (intval($result["count"]) === 1) {
        $messageBox->addText("Na tento čas již existuje jiná rezervace. Vyberte prosím jiný termín.");
        $messageBox->setClass("alert-danger");
        
        $_SESSION["messageBox"] = $messageBox;
        header("Location: viewReservations");
        die();
    }
    
    // pokud kontroly projdou, může se rezervace uložit
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
                    CONCAT(:displayName, ' (res)'),
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
    $stmt->bindParam(":alert", $_POST["alert-type"], PDO::PARAM_STR);
    $stmt->bindValue(":personalDetailsAgreement", 1, PDO::PARAM_INT);
    $stmt->bindParam(":displayName", $resultAdminUser->displayName, PDO::PARAM_STR);
    
    $deleteHash = bin2hex(openssl_random_pseudo_bytes(20));
    $stmt->bindParam(":deleteHash", $deleteHash, PDO::PARAM_STR);

    $stmt->bindValue(":creationTimestamp", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $stmt->bindParam(":IPaddress", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
    
    //zjištění informací, které ještě potřebuji do emailů
    if ($stmt->execute()) {
        $messageBox->addText("Rezervace byla v pořádku vytvořena.<br> ");
                            
        //************************************
        //NOVÝ ZPŮSOB POSÍLÁNÍ EMAILŮ - START
        //************************************       
                
        $therapist = $_POST["person"];
        $service = $_POST["service"];
        $send_email_to_Client = $_POST["send_email"];
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
        $addMessageBox = 'Y';
        $source = $resultAdminUser->displayName . ' (res)';
        
        $type = 'new'; //poslat emaily, které se mají poslat při nové rezervaci
        
        include("../emailResSend.php");
        
        //************************************
        //NOVÝ ZPŮSOB POSÍLÁNÍ EMAILŮ - KONEC
        //************************************   
         
    } else {
        $messageBox->addText("Rezervaci se nepodařilo vytvořit. Zkuste to prosím znovu.");
        $messageBox->setClass("alert-danger");
    }

    //Pokud se jedná o nového klienta, je vložen do tabulky clients; pokud klient už exituje, pak jsou jeho údaje aktualizovány
    if (empty($_POST["client"])) {
        $query = "  INSERT INTO clients (
                        id,
                        name,
                        surname,
                        email,
                        phone,
                        lastEditDate
                    ) VALUES (
                        UUID(),
                        AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                        AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                        NOW()
                    )";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
        $stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
        $stmt->bindValue(":email", empty($_POST["email"]) ? NULL : $_POST["email"], PDO::PARAM_STR);
        $stmt->bindValue(":phone", empty($_POST["phone"]) ? NULL : $_POST["phone"], PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $query = "  UPDATE clients
                    SET
                        name = AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                        surname = AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),
                        email = AES_ENCRYPT(:email, '" . Settings::$mySqlAESpassword . "'),
                        phone = AES_ENCRYPT(:phone, '" . Settings::$mySqlAESpassword . "'),
                        lastEditDate = NOW()
                    WHERE id = :id";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
        $stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
        $stmt->bindValue(":email", empty($_POST["email"]) ? NULL : $_POST["email"], PDO::PARAM_STR);
        $stmt->bindValue(":phone", empty($_POST["phone"]) ? NULL : $_POST["phone"], PDO::PARAM_STR);
        $stmt->bindParam(":id", $_POST["client"], PDO::PARAM_STR);
        $stmt->execute();
                        
    }
}

$_SESSION["messageBox"] = $messageBox;
if (isset ($_POST["backTo"])) {
    //znamená, že php bylo spuštěno ze stránky viewReservations
    header("Location: viewReservations.php?date=" . $_POST["backTo"]);
    die();
} 

if (isset ($_POST["wlid"])) {  
    //znamená, že php bylo puštěno ze stránky watinglist a je potřeba dát dané položce waiting listu solved = 1
    $query = "  UPDATE waitinglist SET solved = 1 WHERE id = :wlid";
    $stmt = $dbh->prepare($query);    
    $stmt->bindParam(":wlid", $_POST["wlid"], PDO::PARAM_STR);
    $stmt->execute();
}