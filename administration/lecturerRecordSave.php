<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";

$query = "  UPDATE groupExcercises
         SET
             cash = :cash,
             qr = :qr,
             benefit = :benefit,
             free = :free,
             lectorNote = :note,
             recorded = 1
        WHERE
        id = :id" ;
$stmt = $dbh->prepare($query);
$stmt->bindParam(":cash", $_POST["cash"], PDO::PARAM_INT);
$stmt->bindParam(":qr", $_POST["qr"], PDO::PARAM_INT);
$stmt->bindParam(":benefit", $_POST["benefit"], PDO::PARAM_INT);
$stmt->bindParam(":free", $_POST["free"], PDO::PARAM_INT);
$stmt->bindParam(":note", $_POST["note"], PDO::PARAM_STR);
$stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
$stmt->execute();

//pokud neukládám já, zaslat notifikační email
if ($resultAdminUser->id !='5') {

    //dotažení údajů pro notifikační email
    $query = "  SELECT                                 
                    ge.date,
                    ge.title,
                    ge.timeFrom,
                    ge.cash,
                    ge.QR,
                    ge.benefit,
                    ge.free,
                    (SELECT COUNT(id) FROM visits v WHERE v.ge = ge.id) as prepaid,
                    (SELECT COUNT(id) FROM attendance a WHERE a.ucast = 1 AND a.ge = ge.id) as regular,
                    lectorNote
                FROM groupExcercises ge

                WHERE id = :id           
                ";
    $stmt = $dbh->prepare($query);                                    
    $stmt->bindValue(":id", $_POST["id"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    //zaslání notifikace  emailem o uložení údajů o skupinovém cvičení
    $mail = new PHPMailer\PHPMailer\PHPmailer();
    $mail->Host = "localhost";
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "utf-8";
    $mail->IsHTML(true);

    $mail->SetFrom("rezervace@fyzioland.cz", "Rezervace, Fyzioland");
    $mail->AddAddress("jiri.havlicky@fyzioland.cz", "Jiří Havlický");
    $mail->Subject = "Fyzioland - záznam lektora vyplněn";

    $mail->Body = "<html><head></head><body style='padding: 10px;'>";
    $mail->Body .= "Vážená kolegyně, vážený kolego,<br><br>";
    $mail->Body .= "rádi bychom Vás informovali, že byl vyplněn následující záznam lektora skupinového cvičení:<br><br>";
    $mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Název cvičení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->title . "</td></tr>";            
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->date . " " . $result->timeFrom . "</td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Kódy</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->cash . '/' .$result->QR . '/' . $result->benefit . '/' .$result->free. '/' . $result->prepaid. '/' . $result->regular ."</td></tr>";
    $mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Poznámka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $result->lectorNote . "</td></tr>";

    $mail->Body .= "</table>";
    $mail->Body .= "<br>";
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

    $mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");
    $mail->Send();
}