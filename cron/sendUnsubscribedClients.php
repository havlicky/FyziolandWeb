<?php

require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";

if ( $_GET["hash"] !== "sendFromCron" ) {
    echo "Tento skript je spouštěn pouze automaticky.";
    die();
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

$targetDate = (new DateTime())->sub(new DateInterval("P7D"))->format("Y-m-d");

$query = "  SELECT
                AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email,
                c.clientsDeregisterFromMailing
            FROM clients AS c
            WHERE
                c.clientsDeregisterFromMailing IS NOT NULL AND
                c.clientsDeregisterFromMailing >= :targetDate
            ORDER BY
                AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'),
                AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":targetDate", $targetDate, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

if (count($results) === 0) {
    echo "Žádné e-maily k odeslání.";
    die();
}

$mail = new PHPMailer\PHPMailer\PHPmailer();
$mail->Host = "localhost";
$mail->SMTPKeepAlive = true;
$mail->CharSet = "utf-8";
$mail->IsHTML(true);
$mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");
$mail->addAddress("rezervace@fyzioland.cz", "Rezervace, Fyzioland s.r.o.");

$mail->Subject = "Přehled odhlášených klientů z mailingu za poslední týden";

$mail->Body = "<table><thead><tr>";
$mail->Body .= "<th>Příjmení a jméno</th>";
$mail->Body .= "<th>E-mailová adresa</th>";
$mail->Body .= "<th>Datum odhlášení</th>";
$mail->Body .= "</tr></thead>";
$mail->Body .= "<tbody>";

foreach ($results as $result) {
    
    $mail->Body .= "<tr>";
    $mail->Body .= "<td>{$result->surname}" . (empty($result->name) ? "" : ", {$result->name}") . "</td>";
    $mail->Body .= "<td>{$result->email}</td>";
    $mail->Body .= "<td>" . (new DateTime($result->clientsDeregisterFromMailing))->format("j. n. Y G:i") . "</td>";
    $mail->Body .= "</tr>";

}

$mail->Body .= "</tbody></table>";
$mail->Send();