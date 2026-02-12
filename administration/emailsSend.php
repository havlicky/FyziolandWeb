<?php

set_time_limit(60 * 30);  // 30 minut

require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";

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

$query = "  SELECT
                e.id,
                e.subject,
                e.body
            FROM emails AS e
            WHERE
                ( dateToSend IS NULL OR dateToSend <= NOW() ) AND
                ( state = 'K odeslání' OR state = 'Probíhá odesílání' ) AND
                dateSent IS NULL";
$stmt = $dbh->prepare($query);
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_OBJ);


$mail = new PHPMailer\PHPMailer\PHPmailer();
$mail->Host = "localhost";
$mail->SMTPKeepAlive = true;

$mail->CharSet = "utf-8";
$mail->IsHTML(true);

$mail->SetFrom("info@fyzioland.cz", "Fyzioland");
$mail->AddReplyTo("info@fyzioland.cz", "Fyzioland");
$mail->Sender = "info@fyzioland.cz";

foreach ((array)$emails as $email) {
    $query = "UPDATE emails SET state = 'Probíhá odesílání' WHERE id = :id AND state <> 'Probíhá odesílání'";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $email->id, PDO::PARAM_INT);
    $stmt->execute();
    
    
    $mail->Subject = $email->subject;
    
    $query = "  SELECT
                    er.id AS emailSendingId,
                    AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
                    AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                    AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email
                FROM emailsRecipients AS er
                LEFT JOIN clients AS c ON c.id = er.recipient
                WHERE
                    er.emailId = :emailId AND
                    dateSent IS NULL
                GROUP BY c.email
                LIMIT 30";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":emailId", $email->id, PDO::PARAM_INT);
    $stmt->execute();
    $recipients = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    foreach ((array)$recipients as $recipient) {
        $mail->ClearAddresses();
        $mail->AddAddress(trim($recipient->email), $recipient->name . " " . $recipient->surname);
        $mail->clearCustomHeaders();
        $mail->Body = $email->body;
        
        if (mb_strpos($mail->Body, "@@cancelMailingLink@@") !== FALSE) {
            $hash = bin2hex(openssl_random_pseudo_bytes(24));

            $cancelMailingLink = "https://fyzioland.cz/mailing/cancel.php?email=" . urlencode($recipient->email) . "&hash={$hash}&uid={$recipient->emailSendingId}";
            $mail->Body = str_replace("@@cancelMailingLink@@", $cancelMailingLink, $mail->Body);

            $mail->AddCustomHeader("List-Unsubscribe", "<$cancelMailingLink>");
        } else {
            $hash = NULL;
        }
        
        $mail->Send();
        
        $query = "  UPDATE emailsRecipients 
                    SET
                        emailAddress = AES_ENCRYPT(:emailAddress, '" . Settings::$mySqlAESpassword . "'),
                        dateSent = NOW(),
                        hash = :hash
                    WHERE id = :emailSendingId";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":emailAddress", $recipient->email, PDO::PARAM_STR);
        $stmt->bindParam(":emailSendingId", $recipient->emailSendingId, PDO::PARAM_INT);
        $stmt->bindParam(":hash", $hash, PDO::PARAM_STR);
        $stmt->execute();
    }
    
    $query = "  UPDATE emails
                SET
                    state = 'Odesláno',
                    dateSent = NOW()
                WHERE
                    (SELECT COUNT(id) FROM emailsRecipients WHERE emailId = emails.id AND dateSent IS NULL) = 0 AND
                    id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $email->id, PDO::PARAM_INT);
    $stmt->execute();
}