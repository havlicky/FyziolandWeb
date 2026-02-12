<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";
require_once "../php/class.settings.php";

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

$pocetCyklu = 0;

if($_POST["person"] == 'allErgoTherapists') {
    // věšichni terapeuti do jednoho řetězce pro jednotlivé datumy a časy

dalsiKolo:    
    
    $pocetCyklu = $pocetCyklu + 1;
    
    if ($pocetCyklu > 2) {                        
        echo('algortimus se zacyklil a je ukončen');
        die();
    }
    
    //echo($_POST["date"]);
    //echo($_POST["hour"]);
    //echo($_POST["minute"]);
    //echo($_POST["poradi"]);
    
    if(intval($_POST["poradi"]) >=0) {        
        $query = "  SELECT
                        r.id as resid,                    
                        r.client as client,
                        r.hour,
                        r.minute,
                        r.date,
                        :poradi as poradi
                    FROM reservations r
                    LEFT JOIN adminLogin al ON al.id=r.personnel
                    LEFT JOIN services s ON s.id = r.service
                    WHERE                    
                        r.date =:date AND
                        r.minute = :minute AND
                        r.hour = :hour AND
                        s.active = 1 AND
                        r.active = 1
                    ORDER BY r.date, r.hour, al.orderRank
                    LIMIT :poradi, 1                 
                    ";
        $stmt = $dbh->prepare($query);    
        $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
        $stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_STR);    
        $stmt->bindParam(":minute", $_POST["minute"], PDO::PARAM_STR);    
        $stmt->bindParam(":poradi", $_POST["poradi"], PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetch(PDO::FETCH_OBJ);         

    }
    // pokud rezervace existuje, ale nemá id klienta - znamená to, že klient si dělal rezervaci na webu sám a musím dohledat v tabulce klientů jeho id podle emailu nebo tel. čísla
    if($results->resid >0 && $results->client == '') {        
        //echo('hledám id klienta v tabulce clients');
        
        $resId = $results->resid;          
        $hour = $results->hour;
        $minute = $results->minute;
        $date = $results->date;
        $poradi = $results->poradi;
        
        $query = "  
                    SELECT
                        c.id                
                    FROM clients c                    
                    WHERE                                            
                        ((SELECT AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') FROM reservations WHERE id=:resid) = AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') OR
                         (SELECT AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') FROM reservations WHERE id=:resid) = AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "')
                        )
                    LIMIT 1
                ";
        $stmt = $dbh->prepare($query);    
        $stmt->bindParam("resid", $resId, PDO::PARAM_INT);
        $stmt->execute();
        $client = $stmt->fetch(PDO::FETCH_OBJ);
        
        //pokud nebyl nalezen id klienta (tzn. klient udělal rezervaci na webu a zatím není v ordinaci - tj. přijde poprvé, nelze ho vybrat v comboboxu na spravaslotuklient a proto jdu na dalšího předchozího klienta)
        if ($client->id != null){
            $results->client = $client->id;  
        }
        
        //potřebuji ajaxem vrátit tyto hodnoty pro správné určení nextres
        $results->hour = $hour;
        $results->minute = $minute;
        $results->date = $date;
        $results->poradi = $poradi;
               
        goto zaver;        
    }    
    
    //další rezervace v daném dni a v čase v pořadí už neexistuje - je potřeba se posunout na předchozí čas v daném dni nebo předcházející den
        
    if ($results == null) {        
        $query = "  SELECT
                        r.date,
                        r.hour,
                        r.minute
                    FROM reservations r
                    LEFT JOIN adminLogin al ON al.id = r.personnel
                    LEFT JOIN services s ON s.id = r.service
                    WHERE                    
                        ((r.date = :date AND r.hour < :hour) OR r.date < :date) AND                                            
                        r.active = 1
                    ORDER BY r.date DESC, r.hour DESC, al.orderRank ASC 
                    LIMIT 1                 
                    ";
        $stmt = $dbh->prepare($query);    
        $stmt->bindParam(":date", $_POST["date"], PDO::PARAM_STR);
        $stmt->bindParam(":hour", $_POST["hour"], PDO::PARAM_STR);        
        $stmt->execute();
        $results_3 = $stmt->fetch(PDO::FETCH_OBJ);
        
        $query = "  SELECT
                        COUNT(r.id) as pocet
                    FROM reservations r                                        
                    WHERE                    
                        r.date = :date AND r.hour = :hour AND                        
                        r.active = 1                                                     
                    ";
        $stmt = $dbh->prepare($query);    
        $stmt->bindParam(":date", $results_3->date, PDO::PARAM_STR);
        $stmt->bindParam(":hour", $results_3->hour, PDO::PARAM_STR);        
        $stmt->execute();
        $results_4 = $stmt->fetch(PDO::FETCH_OBJ);
        
        //echo('posun na předchozí rezervaci');
        $_POST["date"] =  $results_3->date;
        $_POST["hour"] =  $results_3->hour;
        $_POST["minute"] =  $results_3->minute;
        $_POST["poradi"] =  $results_4->pocet-1;
        
        /*
        echo($_POST["date"]);
        echo($_POST["hour"]);
        echo($_POST["minute"]);
        */
        
         if ($results_3 == null) {  
             //echo("nenalezena další rezervace");
             die();
         }
        
        goto dalsiKolo;
    }
     
      
    
} else if($_POST["person"] =='clientOnly'){
    
    
    
} else if($_POST["person"] =='freeSlotsOnly'){
    
    
    
} else {
    
}

zaver:
echo json_encode($results);