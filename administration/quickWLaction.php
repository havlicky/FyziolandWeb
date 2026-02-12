<?php

require_once "../php/class.settings.php";
require_once "checkLogin.php";

session_start();

if($_POST["type"]=='allWeek'){
    
    //smaže sloty v období, které chci znovu nastavit
    $query = "  DELETE FROM clientAvailabilityWL WHERE client = :client AND date BETWEEN :dateFrom AND :dateTo ";    
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);    
    if ($stmt->execute()) {
                     echo "Smazáno allWeek";
                 } else {
                     echo "0";
                     printf("Error: %s.\n", $stmt->error);
                 }  
    
    if ($_POST["deleteOnly"] == 'N') {
        //projede všechny dny v týdnu a všechny hodiny a nastaví WL
        for ($i = 0; $i < 7; $i++) {
            for ($hour = Settings::$timeFrom; $hour <= Settings::$timeTo; $hour++) {
                $hourFrom = $hour;
                if ($hourFrom == 10 || $hourFrom == 11 || $hourFrom == 12) {
                    $minuteFrom = 15;
                } else {
                    $minuteFrom = 0;
                }
                $timeFrom = $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT);              
                $time =  date("H:i", strtotime($timeFrom));             

                $query = "  
                    INSERT INTO clientAvailabilityWL (
                        client,
                        date,
                        time,
                        user,
                        lastEditDate
                       

                    ) VALUES (
                        :client,
                        :date,
                        :time,
                        :user,
                        NOW()
                        
                    )";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
                $stmt->bindParam(":date", $_POST["dateFrom"], PDO::PARAM_STR);
                $stmt->bindParam(":time", $time, PDO::PARAM_STR);
                $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
               

                if ($stmt->execute()) {
                    echo "Vloženo";
                } else {
                    echo "0";
                    printf("Error: %s.\n", $stmt->error);
                }  
            }
            $_POST["dateFrom"] = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P1D"));
            $_POST["dateFrom"] =  $_POST["dateFrom"]->format("Y-m-d");     
        }
    }

    
} else if($_POST["type"]=='allForenoon'){
    
    //smaže sloty v období, které chci znovu nastavit
    $query = "  DELETE FROM clientAvailabilityWL WHERE
                    client = :client AND 
                    date BETWEEN :dateFrom AND :dateTo AND
                    (time = '7:00' OR
                     time = '8:00' OR
                     time = '9:00' OR
                     time = '10:15' OR
                     time = '11:15'
                     ) 
                   ";    
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);    
    if ($stmt->execute()) {
        echo "Smazáno allForenoon";
    } else {
        echo "0";
        printf("Error: %s.\n", $stmt->error);
    }  
    
    if ($_POST["deleteOnly"] == 'N') {
        //projede všechny dny v týdnu a všechny hodiny a nastaví WL
        for ($i = 0; $i < 7; $i++) {
            for ($hour = Settings::$timeFrom; $hour < 12; $hour++) {
                $hourFrom = $hour;
                if ($hourFrom == 10 || $hourFrom == 11 || $hourFrom == 12) {
                    $minuteFrom = 15;
                } else {
                    $minuteFrom = 0;
                }
                $timeFrom = $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT);              
                $time =  date("H:i", strtotime($timeFrom));             

                $query = "  
                    INSERT INTO clientAvailabilityWL (
                        client,
                        date,
                        time,
                        user,
                        lastEditDate

                    ) VALUES (
                        :client,
                        :date,
                        :time,
                        :user,
                        NOW()
                    )";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
                $stmt->bindParam(":date", $_POST["dateFrom"], PDO::PARAM_STR);
                $stmt->bindParam(":time", $time, PDO::PARAM_STR);
                $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    echo "Vloženo";
                } else {
                    echo "0";
                    printf("Error: %s.\n", $stmt->error);
                }  
            }
            $_POST["dateFrom"] = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P1D"));
            $_POST["dateFrom"] =  $_POST["dateFrom"]->format("Y-m-d");     
        }
    }
} else if($_POST["type"]=='allAfternoon'){
    
    //smaže sloty v období, které chci znovu nastavit
    $query = "  DELETE FROM clientAvailabilityWL WHERE
                    client = :client AND 
                    date BETWEEN :dateFrom AND :dateTo AND
                    (time = '13:00' OR
                     time = '14:00' OR
                     time = '15:00' OR
                     time = '16:00' OR
                     time = '17:00' OR
                     time = '18:00' OR
                     time = '19:00' OR
                     time = '20:00'
                     ) 
                   ";    
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);    
    if ($stmt->execute()) {
        echo "Smazáno allAfternoon";
    } else {
        echo "0";
        printf("Error: %s.\n", $stmt->error);
    }  
    
    if ($_POST["deleteOnly"] == 'N') {
        //projede všechny dny v týdnu a všechny hodiny a nastaví WL
        for ($i = 0; $i < 7; $i++) {
            for ($hour = 13; $hour <= Settings::$timeTo; $hour++) {
                $hourFrom = $hour;
                if ($hourFrom == 10 || $hourFrom == 11 || $hourFrom == 12) {
                    $minuteFrom = 15;
                } else {
                    $minuteFrom = 0;
                }
                $timeFrom = $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT);              
                $time =  date("H:i", strtotime($timeFrom));             

                $query = "  
                    INSERT INTO clientAvailabilityWL (
                        client,
                        date,
                        time,
                        user,
                        lastEditDate

                    ) VALUES (
                        :client,
                        :date,
                        :time,
                        :user,
                        NOW()
                    )";
                $stmt = $dbh->prepare($query);
                $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
                $stmt->bindParam(":date", $_POST["dateFrom"], PDO::PARAM_STR);
                $stmt->bindParam(":time", $time, PDO::PARAM_STR);
                $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    echo "Vloženo";
                } else {
                    echo "0";
                    printf("Error: %s.\n", $stmt->error);
                }  
            }
            $_POST["dateFrom"] = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P1D"));
            $_POST["dateFrom"] =  $_POST["dateFrom"]->format("Y-m-d");     
        }
    }
} else if ($_POST["type"] == 'CopyToNextWeek') {    
    // smaže sloty v období, které chci znovu nastavit
    // bacha musím se posunout o 7 dní
    
    $dateFromDestination = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P7D"));
    $dateFromDestination =  $dateFromDestination->format("Y-m-d"); 
            
    $dateToDestination = (new DateTime($_POST["dateTo"]))->add(new DateInterval("P7D"));
    $dateToDestination =  $dateToDestination->format("Y-m-d"); 
    
    $query = "  DELETE FROM clientAvailabilityWL WHERE client = :client AND date BETWEEN :dateFromDestination AND :dateToDestination ";    
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFromDestination", $dateFromDestination, PDO::PARAM_STR);
    $stmt->bindParam(":dateToDestination", $dateToDestination, PDO::PARAM_STR);    
    if ($stmt->execute()) {
                     echo "Smazáno (CopyToNextWeek)";
                 } else {
                     echo "0";
                     printf("Error: %s.\n", $stmt->error);
                 }      
    //projede všechny dny v týdnu a všechny hodiny a nastaví WL v následujícím týdnu dle aktuálního týdne
     
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 7 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute(); 
} else if ($_POST["type"] == 'CopyToNext8Weeks' || $_POST["type"] == 'CopyToNext16Weeks') {    
    // smaže sloty v období, které chci znovu nastavit
    // bacha musím se posunout o 7 dní
    
    $dateFromDestination = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P7D"));
    $dateFromDestination =  $dateFromDestination->format("Y-m-d"); 
          
    if($_POST["type"] == 'CopyToNext8Weeks') {$dateToDestination = (new DateTime($_POST["dateTo"]))->add(new DateInterval("P56D"));}
    if($_POST["type"] == 'CopyToNext16Weeks') {$dateToDestination = (new DateTime($_POST["dateTo"]))->add(new DateInterval("P112D"));}
    $dateToDestination =  $dateToDestination->format("Y-m-d"); 
    
    $query = "  DELETE FROM clientAvailabilityWL WHERE client = :client AND date BETWEEN :dateFromDestination AND :dateToDestination ";    
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFromDestination", $dateFromDestination, PDO::PARAM_STR);
    $stmt->bindParam(":dateToDestination", $dateToDestination, PDO::PARAM_STR);    
    if ($stmt->execute()) {
                     echo "Smazáno (CopyToNextWeeks)";
                 } else {
                     echo "0";
                     printf("Error: %s.\n", $stmt->error);
                 }      
    //projede všechny dny v týdnu a všechny hodiny a nastaví WL v následujícím týdnu dle aktuálního týdne
     
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 7 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute(); 
    
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 14 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute(); 
    
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 21 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute();
    
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 28 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute();
    
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 35 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute();
    
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 42 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute();
    
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 49 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute();
    
    $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 56 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
    $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
    $stmt->execute();
    
    if($_POST["type"] == 'CopyToNext16Weeks') {
        $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 63 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
        $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 70 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
        $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 77 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
        $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 84 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
        $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 91 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
        $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 98 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
        $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 105 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
        $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $query = "  
        INSERT INTO clientAvailabilityWL (
            client,
            date,
            time,
            user,
            lastEditDate
        ) SELECT 
            client,
            DATE_ADD(date, INTERVAL 112 DAY) as date,
            time,
            :user,
            NOW()
          FROM clientAvailabilityWL
          WHERE 
            date BETWEEN :dateFrom AND :dateTo AND
            client = :client
            ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
        $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
        $stmt->bindParam(":dateTo", $_POST["dateTo"], PDO::PARAM_STR);
        $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);
        $stmt->execute();
    }   
    
} else if (intval($_POST["type"])>=0) {    
    //smaže sloty v období, které chci znovu nastavit
    $query = "  DELETE FROM clientAvailabilityWL 
                    WHERE
                        client = :client AND 
                        (date = DATE_ADD(:dateFrom, INTERVAL :shift DAY))
            ";
           
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":dateFrom", $_POST["dateFrom"], PDO::PARAM_STR);
    $stmt->bindParam(":shift", $_POST["type"], PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo "Smazáno den v týdnu";
    } else {
        echo "0";
        printf("Error: %s.\n", $stmt->error);
    }  
    
    if ($_POST["deleteOnly"] == 'N') {
        
        //nalezení dne v týdnu
        $_POST["dateFrom"] = (new DateTime($_POST["dateFrom"]))->add(new DateInterval("P" . $_POST["type"] . "D"));
        $_POST["dateFrom"] =  $_POST["dateFrom"]->format("Y-m-d");     
        
        //nastavení slotů
        for ($hour = Settings::$timeFrom; $hour <= Settings::$timeTo; $hour++) {
            $hourFrom = $hour;
            if ($hourFrom == 10 || $hourFrom == 11 || $hourFrom == 12) {
                $minuteFrom = 15;
            } else {
                $minuteFrom = 0;
            }
            $timeFrom = $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT);              
            $time =  date("H:i", strtotime($timeFrom));             

            $query = "  
                INSERT INTO clientAvailabilityWL (
                    client,
                    date,
                    time,
                    user,
                    lastEditDate

                ) VALUES (
                    :client,
                    :date,
                    :time,
                    :user,
                    NOW()
                )";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
            $stmt->bindParam(":date", $_POST["dateFrom"], PDO::PARAM_STR);
            $stmt->bindParam(":time", $time, PDO::PARAM_STR);
            $stmt->bindParam(":user", $resultAdminUser->id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo "Vloženo";
            } else {
                echo "0";
                printf("Error: %s.\n", $stmt->error);
            }  
        }   
    }
    
} else {
    echo('Nevybrána žádná větev; žádná akce neprovedena');
}
    

