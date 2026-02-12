<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

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
// zjištění údajů o klientovi z původní zrušené rezervace
$query = "  SELECT
                id as idres,
                name,
                surname,                
                city,
                zip,
                street,
                birthday,
                birthnumber,
                sex,
                LRname,
                LRsurname,
                LRemail,
                LRphoneFather,
                LRphoneMother,
                LRcity,
                LRstreet,
                LRzip,
                insuranceCompany,
                attName,
                attExt,
                recomCheck,
                recomSaved,
                entryNote,
                qfinished                
            FROM reservations 
            
            WHERE
                deleteHash=:hash
          ";
$stmt = $dbh->prepare($query);                                            
$stmt->bindParam(":hash", $_POST["originalHash"], PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
   
//Update nové aktivní rezervace   
$query = "  UPDATE reservations SET
            
            name = :name,
            surname = :surname,
            city = :city,
            zip = :zip,
            street = :street,
            birthday = :birthday,
            birthnumber = :birthnumber,
            sex = :sex,
            LRname = :LRname,
            LRsurname = :LRsurname,
            LRemail = :LRemail,
            LRphoneFather = :LRphoneFather,
            LRphoneMother = :LRphoneMother,
            LRcity = :LRcity,
            LRstreet = :LRstreet,
            LRzip = :LRzip,
            insuranceCompany = :insuranceCompany,
            attName = :attName,
            attExt = :attExt,
            recomCheck = :recomCheck,
            recomSaved = 0,
            entryNote = :entryNote,
            qfinished = :qfinished
        WHERE id = :id         
      ";
$stmt = $dbh->prepare($query);                                            
$stmt->bindParam(":id", $_POST["newResId"], PDO::PARAM_INT);
$stmt->bindParam(":name", $result->name, PDO::PARAM_STR);
$stmt->bindParam(":surname", $result->surname, PDO::PARAM_STR);
$stmt->bindParam(":city", $result->city, PDO::PARAM_STR);
$stmt->bindParam(":zip", $result->zip, PDO::PARAM_STR);
$stmt->bindParam(":street", $result->street, PDO::PARAM_STR);
$stmt->bindParam(":birthday", $result->birthday, PDO::PARAM_STR);
$stmt->bindParam(":birthnumber", $result->birthnumber, PDO::PARAM_STR);
$stmt->bindParam(":sex", $result->sex, PDO::PARAM_STR);
$stmt->bindParam(":LRname", $result->LRname, PDO::PARAM_STR);
$stmt->bindParam(":LRsurname", $result->LRsurname, PDO::PARAM_STR);
$stmt->bindParam(":LRemail", $result->LRemail, PDO::PARAM_STR);
$stmt->bindParam(":LRphoneFather", $result->LRphoneFather, PDO::PARAM_STR);
$stmt->bindParam(":LRphoneMother", $result->LRphoneMother, PDO::PARAM_STR);
$stmt->bindParam(":LRcity", $result->LRcity, PDO::PARAM_STR);
$stmt->bindParam(":LRstreet", $result->LRstreet, PDO::PARAM_STR);
$stmt->bindParam(":LRzip", $result->LRzip, PDO::PARAM_STR);
$stmt->bindParam(":insuranceCompany", $result->insuranceCompany, PDO::PARAM_INT);
$stmt->bindParam(":attName", $result->attName, PDO::PARAM_STR);
$stmt->bindParam(":attExt", $result->attExt, PDO::PARAM_INT);
$stmt->bindParam(":recomCheck", $result->recomCheck, PDO::PARAM_INT);
$stmt->bindParam(":entryNote", $result->entryNote, PDO::PARAM_STR);
$stmt->bindParam(":qfinished", $result->qfinished, PDO::PARAM_STR);
if ($stmt->execute()) {
    echo "Údaje klienta přeneseny";
} else {
    echo "Chyba při kopírování údajů o klientovi: ";
    printf("Error: %s.\n", $stmt->error);
} 
    
//kopie otázek k nové rezervaci     
$query = "
 INSERT INTO questionnaire (
    idres,
    idq,
    ans,
    note
    )
 SELECT 
    :idresnew,
    idq,
    ans,
    note 
 FROM questionnaire
 WHERE
    idres = :idresold";

$stmt = $dbh->prepare($query);
$stmt->bindParam(":idresold", $result->idres, PDO::PARAM_INT);
$stmt->bindParam(":idresnew", $_POST["newResId"], PDO::PARAM_INT);
if ($stmt->execute()) {
    echo "Otázky dotazníku zkopírovány";
} else {
    echo "CHYBA při kopírování ozázek dotazníku: ";
    printf("Error: %s.\n", $stmt->error);
} 

 