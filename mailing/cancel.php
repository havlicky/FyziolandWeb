<?php
include "../header.php";

$query = "  UPDATE emailsRecipients
            SET cancelMailingTimestamp = NOW()
            WHERE
                id = :id AND
                AES_DECRYPT(emailAddress, '" . Settings::$mySqlAESpassword . "') = :email AND
                hash = :hash";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":id", $_GET["uid"], PDO::PARAM_INT);
$stmt->bindValue(":email", urldecode($_GET["email"]), PDO::PARAM_STR);
$stmt->bindParam(":hash", $_GET["hash"], PDO::PARAM_STR);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $msg = "Děkujeme, Vaše e-mailová adresa byla úspěšně vyřazena z databáze.";
    
    $query = "  UPDATE clients AS c
                LEFT JOIN emailsRecipients AS er ON er.recipient = c.id
                SET
                    c.mailing = 0,
                    c.clientsDeregisterFromMailing = NOW()
                WHERE
                    er.id = :emailSendingId";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":emailSendingId", $_GET["uid"], PDO::PARAM_INT);
    $stmt->execute();
} else {
    $msg = "Litujeme, ale příkaz nebylo možné dokončit. Kontaktujte nás prosím telefonicky nebo e-mailem.";
}

?>

<div class="container-fluid">
           
    <div class="row" id="stranka-404">
        <div class="col-md-12" id="title-logo">
            <a href="/" title="Na hlavní stránku">
                <img src="<?= $absolutePath ?>img/titulni-logo.png" title="Fyzioland">
            </a>
        </div>
        <?= $msg ?><br>
        <div class="underline"></div>
    </div>
    

<?php
include "../footer.php";
?>