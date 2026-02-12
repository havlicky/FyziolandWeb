<?php

// skript na manuálníě spouštění - aktualizace zelených slotů klientů na základě počtu rezervací v historii u klientů, kteří mají typ slotů jako "automat"
//před spuštěním smazat manuálně tabulku clientAvailabilityTimetable

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";
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

echo(' Start');

//parametry:
$Threshold_pocetRezervaci = 0; //větší než tato hodnota se ve slotu zazelení
$PocatekRezervaci = '2022-12-31';   // od tohoto data+1 se zjišťuje počet rezervací pro zazelenění slotu

//smaže data z tabulky se sloty klienty, kteří mají nastavený automat
/*$query = "  DELETE clientAvailabilityTimetable FROM clientAvailabilityTimetable cat LEFT JOIN clients c ON c.id = cat.client WHERE c.updateType = 'auto'";
$stmt = $dbh->prepare($query);
$stmt->execute();


echo(' Smazáni slotů klientů, kteří mají nastavený automat');
*/


// výběr všech klientů pro aktualizaci slotů, kteří mají mít automat a měli v poslední době alespn jednu rezerevaci na ergo
    // - tím je zajištěno, že nastavuji sloty jen ergo klientům (protože 'auto' získají všichni noví založení klienti - nevím u nich v tu chvíli, zda budou ergo nebo fyzio)
$query = "  SELECT 
                id,
                email, 
                phone 
            FROM clients c
            WHERE 
                updateType = 'auto' AND
                (SELECT COUNT(id) from reservations res WHERE 
                        (res.client = c.id OR 
                        res.email = c.email OR
                        res.phone = c. phone) AND
                        (res.service = 10 OR
                         res.service = 14 OR
                         res.service = 2 OR
                         res.service = 13 OR
                         res.service = 12 OR
                         res.service = 20) AND
                         res.date>:pocatekrezervaci AND
                         res.active = 1
                        )>0       
        
        ";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":pocatekrezervaci", $PocatekRezervaci, PDO::PARAM_STR);
$stmt->execute();
$resultsClients = $stmt->fetchAll(PDO::FETCH_OBJ);

echo(' Vybráni klienti pro aktualizaci (auto a měli ergo rezervaci).');

foreach ($resultsClients as $client) {
     
    //pro všechny dny v týdnu a časy projede algoritmus na zazelenění slotu
    for ($dayOfWeek = 0; $dayOfWeek <= 6; $dayOfWeek++) {
        for ($hour = Settings::$timeFrom; $hour <= Settings::$timeTo; $hour++) {
            $hourFrom = $hour;                                
            if ($hourFrom == 10 || $hourFrom == 11 || $hourFrom == 12) {
                $minuteFrom = 15;
            } else {
                $minuteFrom = 0;
            }                     
            $timeFrom = str_pad($hour, 2, "0", STR_PAD_LEFT) .':'. str_pad($minuteFrom, 2, "0", STR_PAD_LEFT);
                        
            echo(' Id client: '. $client->id);                        
            echo(' Den v týdnu: '.$dayOfWeek);
            //echo(' Hodina: '. $hour);            
            //echo(' Hour From: '. $hourFrom);
            //echo(' Minute From: '. $minuteFrom);
            echo(' Time From: '. $timeFrom);
            
            $query = "  SELECT COUNT(id) as pocetRezervaciNaDanyDenAcas from reservations res 
                            WHERE 
                             (res.client = :client OR 
                             res.email = :email OR
                             res.phone = :phone) AND
                                                        
                            (res.hour = :hourFrom AND res.minute = :minuteFrom) AND
                            res.date>:pocatekrezervaci AND
                            WEEKDAY(res.date) = :dayOfWeek AND
                            res.active = 1;
                                ";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":client", $client->id, PDO::PARAM_STR);
            $stmt->bindParam(":email", $client->email, PDO::PARAM_STR);
            $stmt->bindParam(":phone", $client->phone, PDO::PARAM_STR);
            $stmt->bindParam(":pocatekrezervaci", $PocatekRezervaci, PDO::PARAM_STR);
            $stmt->bindParam(":hourFrom", $hourFrom, PDO::PARAM_INT);
            $stmt->bindParam(":minuteFrom", $minuteFrom, PDO::PARAM_INT);            
            $stmt->bindParam(":dayOfWeek", $dayOfWeek, PDO::PARAM_INT);            
            $stmt->execute();            
            $resultsNumRes = $stmt->fetch(PDO::FETCH_OBJ);

            //Dotaz na počet rezervací pouze dětské ergo (výše je použito počet všech rezervací)
            
            /*
             SELECT COUNT(id) as pocetRezervaciNaDanyDenAcas from reservations res 
                                        WHERE 
                                         (res.client = :client OR 
                                         res.email = :email OR
                                         res.phone = :phone) AND

                                        (res.service = 10 OR
                                         res.service = 14 OR
                                         res.service = 2 OR
                                         res.service = 13 OR
                                         res.service = 12 OR
                                         res.service = 20) AND
                                        (res.hour = :hourFrom AND res.minute = :minuteFrom) AND
                                        res.date>:pocatekrezervaci AND
                                        WEEKDAY(res.date) = :dayOfWeek AND
                                        res.active = 1;
             */
            
            echo(' Pocet nalezených rezervací '. $resultsNumRes->pocetRezervaciNaDanyDenAcas);            
            
            if($resultsNumRes->pocetRezervaciNaDanyDenAcas > $Threshold_pocetRezervaci){
                //zapne klientovi slot
                echo('Zápis do clientAvailabilityTimetable: ANO');
                $query = "  INSERT INTO clientAvailabilityTimetable (
                                client,
                                dayOfWeek,
                                time
                            ) VALUES (
                                :client,
                                :dayOfWeek,
                                :time
                            )";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":client", $client->id, PDO::PARAM_STR);
                $stmt->bindParam(":dayOfWeek", $dayOfWeek, PDO::PARAM_INT);
                $stmt->bindParam(":time", $timeFrom, PDO::PARAM_STR);
                $stmt->execute();
                
                $query = "UPDATE clients SET lastSlotsUpdate= NOW() WHERE id= :client";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":client", $client->id, PDO::PARAM_STR);                                
                $stmt->execute();
            } else {
                echo('Zápis do clientAvailabilityTimetable: NE');
            }        
        }            
    }
}
    
