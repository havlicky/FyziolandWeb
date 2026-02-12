<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";
include('phpqrcode/qrlib.php');
    
/*

http://phpqrcode.sourceforge.net/examples
https://qr-platba.cz/pro-vyvojare/specifikace-formatu/

Example
SPD*1.0*ACC:CZ2806000000000168540115*AM:450.00*CC:CZK*MSG:PLATBA ZA ZBOZI*X-VS:1234567890

*/

if ($_POST["action"] == 'RES'){
    $query = "  SELECT
                    CONCAT (AES_DECRYPT(surname, '" . Settings::$mySqlAESpassword . "'), ', ', AES_DECRYPT(name, '" . Settings::$mySqlAESpassword . "')) AS client,
                    AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') as phone            
                FROM reservations 
                WHERE
                    id=:id";                

    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    $data = 'tel:'. $result->phone;

    ob_start();
        QRCode::png($data, null);
        $imageString = base64_encode( ob_get_contents() );
    ob_end_clean();
}

if ($_POST["action"] == 'WL'){
    $query = "  SELECT
                    CONCAT (AES_DECRYPT(surname, '" . Settings::$mySqlAESpassword . "'), ', ', AES_DECRYPT(name, '" . Settings::$mySqlAESpassword . "')) AS client,
                    AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') as phone            
                FROM waitinglist 
                WHERE
                    id=:id";                

    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    $data = 'tel:'. $result->phone;

    ob_start();
        QRCode::png($data, null);
        $imageString = base64_encode( ob_get_contents() );
    ob_end_clean();
}

echo json_encode(array(
	"img" => $imageString,
	"phone" => $result->phone,
	"client" => $result->client	
));