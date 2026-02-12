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

$query = "  SELECT
                id as idres,
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
                service
            FROM reservations 
            
            WHERE
                deleteHash=:hash
          ";
    $stmt = $dbh->prepare($query);                                            
    $stmt->bindParam(":hash", $_POST["originalHash"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    $result->clientDataInReservation= new stdClass();
    
    //u vstupního vyšetření mus být uvedeny údaje o klientovi (pro kontrolní vyšetření to neplatí)
    if ($result->service == 10 &&
        $result->city == null &&
        $result->zip == null  &&
        $result->street == null  &&
        $result->birthday == null  &&
        $result->birthnumber == null  &&
        $result->sex == null  &&
        $result->LRname == null  && 
        $result->LRsurname == null  && 
        $result->LRemail == null  && 
        $result->LRphoneFather == null  && 
        $result->LRphoneMother == null  && 
        $result->LRcity == null  && 
        $result->LRstreet == null  && 
        $result->insuranceCompany == null  && 
        $result->attName == '' && 
        $result->attExt == 0  &&
        $result->recomCheck == 0  && 
        $result->recomSaved == 0  && 
        $result->entryNote == '' 
            ) {
        $result->clientDataInReservation = 'OK';
    } else {
        $result->clientDataInReservation = 'OK';
    }
     

    //zjistím již všechny uložené odpovědi na otázky
    $query = "SELECT COUNT(idq) as pocetVyplnenychOtazek FROM questionnaire where idres = :idres";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":idres", $result->idres, PDO::PARAM_INT);
    $stmt->execute();
    $resultOtazky = $stmt->fetch(PDO::FETCH_OBJ);	 
    
    $result->pocetVyplnenychOtazek= new stdClass();
    $result->pocetVyplnenychOtazek = $resultOtazky->pocetVyplnenychOtazek;
    
echo json_encode($result);
