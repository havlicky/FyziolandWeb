<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

session_start();

// smaže sloty v období, které chci znovu nastavit
// bacha musím se posunout o 7 dní

$dateFromDestination = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P0D"));
$dateFromDestination =  $dateFromDestination->format("Y-m-d"); 
echo("Od: ");
echo($dateFromDestination);

$dateToDestination = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P6D"));
$dateToDestination =  $dateToDestination->format("Y-m-d"); 
echo(" Do: ");
echo($dateToDestination);

//smazání zelených políček v požadovaném období
$query = "DELETE FROM personAvailabilityTimetable WHERE (person = :person OR :person IS NULL) AND date BETWEEN :dateFromDestination AND :dateToDestination ";    
$stmt = $dbh->prepare($query);
$stmt->bindValue(":person", empty($_POST["therapist"]) ? NULL : $_POST["therapist"], PDO::PARAM_INT);
$stmt->bindParam(":dateFromDestination", $dateFromDestination, PDO::PARAM_STR);
$stmt->bindParam(":dateToDestination", $dateToDestination, PDO::PARAM_STR);    
if ($stmt->execute()) {
    echo " Smazáno; ";
} else {
    echo "0";
    printf("Error: %s.\n", $stmt->error);
}

//smazání zakázaných služeb v požadovaném období
$query = "  DELETE FROM patBanServices pbs
            LEFT JOIN personAvailabilityTimetable pat ON pat.id = pbs.patId
            
            WHERE (pat.person = :person OR :person IS NULL) AND pat.date BETWEEN :dateFromDestination AND :dateToDestination ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":person", empty($_POST["therapist"]) ? NULL : $_POST["therapist"], PDO::PARAM_INT);
$stmt->bindParam(":dateFromDestination", $dateFromDestination, PDO::PARAM_STR);
$stmt->bindParam(":dateToDestination", $dateToDestination, PDO::PARAM_STR);    
$stmt->execute();

$refWeekStart = (new DateTime($_POST["refWeekStart"]));
$refWeekStart = $refWeekStart->format("Y-m-d"); 

//zjištění posunu oproti RefDate
$query = "SELECT DATEDIFF(:dateFromDestination, :refWeekStart) as shift";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":dateFromDestination", $dateFromDestination, PDO::PARAM_STR);
$stmt->bindParam(":refWeekStart", $refWeekStart, PDO::PARAM_STR);    
$stmt->execute();
$shift = $stmt->fetch(PDO::FETCH_OBJ);         

echo("Shift: ");
echo($shift->shift);

//nastavení slotů (zelených políček - obecně pro terapeuty dle zvoleného ref. týdne
$query = "  
    INSERT INTO personAvailabilityTimetable (
        person,
        date,
        time,
        web
    ) SELECT 
        person,
        DATE_ADD(date, INTERVAL :shift DAY),
        time,
        web        
    FROM personAvailabilityTimetable
    WHERE 
      date BETWEEN :refWeekStart AND DATE_ADD(:refWeekStart, INTERVAL 6 DAY) AND
      (person = :person OR :person IS NULL)
      ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":person", empty($_POST["therapist"]) ? NULL : $_POST["therapist"], PDO::PARAM_INT);
$stmt->bindParam(":refWeekStart", $refWeekStart, PDO::PARAM_STR);    
$stmt->bindParam(":shift", $shift->shift, PDO::PARAM_INT);
if ($stmt->execute()) {
    echo " Sloty nastaveny";
} else {
    echo " Chyba: ";
    printf("Error: %s.\n", $stmt->error);
}    
// *****************************************************************
// cylky pro vložení zakázaných služeb do tabulky 'patBanServices'
// *****************************************************************

echo " Zjištění, pro které všechny terapeuty řeštm tuto úlohu";
$query = " 
    SELECT DISTINCT
        person as id          
    FROM personAvailabilityTimetable
    WHERE 
      date BETWEEN :refWeekStart AND DATE_ADD(:refWeekStart, INTERVAL 6 DAY) AND
      (person = :person OR :person IS NULL)
      ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":person", empty($_POST["therapist"]) ? NULL : $_POST["therapist"], PDO::PARAM_INT);
$stmt->bindParam(":refWeekStart", $refWeekStart, PDO::PARAM_STR);    
$stmt->bindParam(":shift", $shift->shift, PDO::PARAM_INT);
if ($stmt->execute()) {
    $therapists = $stmt->fetchAll(PDO::FETCH_OBJ);  
    echo " Terapeuti zjištěni";
} else {
    echo " Chyba: ";
    printf("Error: %s.\n", $stmt->error);
}    
//1. cyklus -- jedu přes všechny terapeuty postupně    
foreach ($therapists as $therapist) {
    
    //zjistím všechny dny v týdnu, časy a zakázané služby pro daného terapeuta
    $query = "  
        SELECT
            service,            
            dayOfWeek,
            time
        FROM patsbTemplates
        WHERE
            person = :person";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":person", $therapist->id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo " Načtena šablona zakázaných služeb pro terapeuta ";
        $banServices = $stmt->fetchAll(PDO::FETCH_OBJ);
    } else {
        echo " Chyba: ";
        printf("Error: %s.\n", $stmt->error);
    }   
    
    //zjistím všechna zelená políčka pro daného terapeuta, které jsem nyní nově vytvořil
    $query = "
        SELECT 
            id,
            DAYOFWEEK(date) as dayOfWeek,
            time
        FROM personAvailabilityTimetable
        WHERE 
          date BETWEEN :dateFromDestination AND :dateToDestination AND
          (person = :person)
          ";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":person", $therapist->id, PDO::PARAM_INT);
    $stmt->bindParam(":dateFromDestination", $dateFromDestination, PDO::PARAM_STR);
    $stmt->bindParam(":dateToDestination", $dateToDestination, PDO::PARAM_STR);
    if ($stmt->execute()) {
        $personPats = $stmt->fetchAll(PDO::FETCH_OBJ);  
        echo " Zjištění všech zelených okének pro terapeuta";
    } else {
        echo " Chyba: ";
        printf("Error: %s.\n", $stmt->error);
    }

    //2. cyklus - jedu přes všechna zelená políčka daného terapeuta
    foreach ($personPats as $personPat) {
        
        //3. cyklus - u každého zeleného políčka projedu všechny dny v týdnu, časy ze šablony zakázaných služeb
        foreach ($banServices as $banService) {
            // pokud najdu shodu zeleného políčka s časem a dnem v týdnu ze šablony zakázaných služeb, vložím zakázanou službu
            if ($banService->time == $personPat->time && $banService->dayOfWeek == $personPat->dayOfWeek) { 
                echo ('Shoda - pokus o zápis zakázané služby');
                $query = "  INSERT INTO patBanServices (
                                patId,
                                serviceId                        
                            ) VALUES (
                                :patId,
                                :serviceId                       
                            )";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":patId",$personPat->id, PDO::PARAM_INT);        
                $stmt->bindParam(":serviceId",$banService->service, PDO::PARAM_INT);                                                                
                if ($stmt->execute()) {
                    echo " Vložena zakázaná služba ";
                } else {
                    echo "0";
                    printf("Error: %s.\n", $stmt->error);
                }
            }
        }
    }
}    
