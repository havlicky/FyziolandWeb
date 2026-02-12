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

$query = "  SELECT
                ge.id,
                ge.title,
                ge.date,                
                IF(ge.semestralCourse IS NULL, ge.price, (SELECT indivPrice FROM semestralCourses WHERE id = (SELECT semestralCourse from groupExcercises WHERE id = :id))) AS price
            FROM groupExcercises AS ge            
            WHERE
                ge.id=:id";                
            
$stmt = $dbh->prepare($query);
$stmt->bindParam(":id", $_POST["id"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

$iban = "CZ2920100000002401198774";
$note =  'Skupinové cvičení - ' . $result->title . ' dne ' . $result->date;
$amount = $result->price; 
$symbol = '111' . $result->id; 

$template = "SPD*1.0*ACC:%s*AM:%0.2f*CC:CZK*MSG:%s*X-VS:%s*X-SS:1";

$data = sprintf($template, $iban, $amount, $note, $symbol);

ob_start();
QRCode::png($data, null);
$imageString = base64_encode( ob_get_contents() );
ob_end_clean();

//echo $imageString;
echo json_encode(array(
	"img" => $imageString,
	"iban" => $iban,
	"amount" => $amount,
	"symbol" => $symbol
));