<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
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

if (!empty($_POST["id"])) {
    $query = "  UPDATE emails SET
                    subject = :subject,
                    body = :body,
                    state = :state,
                    dateToSend = :dateToSend
                WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":subject", $_POST["subject"], \PDO::PARAM_STR);
    $stmt->bindParam(":body", $_POST["body"], \PDO::PARAM_STR);
    $stmt->bindValue(":dateToSend", empty($_POST["sendingTime"]) ? NULL : (new DateTime())->createFromFormat("d.m.Y H:i", $_POST["sendingTime"])->format("Y-m-d H:i"), \PDO::PARAM_STR);
    $stmt->bindParam(":state", $_POST["saveButton"], \PDO::PARAM_STR);
    $stmt->bindParam(":id", $_POST["id"], \PDO::PARAM_INT);
    $stmt->execute();
    
    $messageBox->addText("E-mail byl úspěšně uložen.");
    
    $emailId = intval($_POST["id"]);
} else {
    $query = "  INSERT INTO emails (
                    subject,
                    body,
                    state,
                    dateToSend
                ) VALUES (
                    :subject,
                    :body,
                    :state,
                    :dateToSend
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":subject", $_POST["subject"], \PDO::PARAM_STR);
    $stmt->bindParam(":body", $_POST["body"], \PDO::PARAM_STR);
    $stmt->bindValue(":dateToSend", empty($_POST["sendingTime"]) ? NULL : (new DateTime())->createFromFormat("d.m.Y H:i", $_POST["sendingTime"])->format("Y-m-d H:i"), \PDO::PARAM_STR);
    $stmt->bindParam(":state", $_POST["saveButton"], \PDO::PARAM_STR);
    $stmt->execute();
    
    $messageBox->addText("E-mail byl úspěšně vytvořen a uložen.");
    
    $emailId = $dbh->lastInsertId();
}

$query = "DELETE FROM emailsRecipients WHERE emailId = :emailId";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":emailId", $emailId, \PDO::PARAM_INT);
$stmt->execute();

$i=0;

foreach ((array)$_POST["recipients"] as $recipient) {
    $initialEmail = $_POST["email"][$i];
    $query = "  INSERT INTO emailsRecipients (
                    emailId,
                    initialEmail,
                    recipient
                ) VALUES (
                    :emailId,
                    AES_ENCRYPT(:initialEmail, '" . Settings::$mySqlAESpassword . "'),
                    :recipient
                )";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":emailId", $emailId, \PDO::PARAM_INT);
    $stmt->bindParam(":recipient", $recipient, \PDO::PARAM_STR);
    $stmt->bindParam(":initialEmail", $initialEmail, \PDO::PARAM_STR);
    $stmt->execute();
    $i = $i +1;
}

//zde smazat duplicity v tabulce emailsRecipients   
$query = "  DELETE t1 FROM emailsRecipients t1
            INNER JOIN emailsRecipients t2 
            WHERE 
                t1.id < t2.id AND 
                t1.initialEmail = t2.initialEmail AND
                t1.emailId = :emailId ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":emailId", $emailId, \PDO::PARAM_INT);
$stmt->execute();

//vymazání času odeslání (pokud už byl email v minulosti odeslán a já ho znovu edituji a chci ho poslat znova,tak je potřeba vymazat čas odeslání
$query = "  UPDATE emails SET                    
                    dateSent = NULL
                WHERE id = :id";
$stmt = $dbh->prepare($query);    
$stmt->bindParam(":id", $emailId, \PDO::PARAM_INT);
$stmt->execute();

$_SESSION["messageBox"] = $messageBox;
header("Location: emailsList.php");
