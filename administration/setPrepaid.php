<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

//odškrtnutí checkboxu
if ($_POST["akce"] =='0') {
    $stmt = $dbh->prepare("DELETE FROM visits WHERE client = :client AND ge = :ge");
    $stmt->bindParam(":ge", $_POST["ge"], PDO::PARAM_INT);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
}

//zaškrtnutí checkboxu
if ($_POST["akce"] =='1') {
    
    //zjištění ID depozita, které bude čerpáno
    $query = 
            "SELECT 		
                MIN(deposits.id) as minDepId
            FROM deposits 

            LEFT JOIN clients as C on C.id = deposits.clientId
            LEFT JOIN relationdepositsallowedclients as rdac ON deposits.id = rdac.depositId			

            WHERE
                deposits.paymentDate<= CURDATE() AND
                rdac.clientId = :client AND				

                (deposits.amount							
                         - 
                         (SELECT 
                          IFNULL(SUM(visits.prepaid),0) 
                          FROM visits          
                          WHERE 
                          visits.depositId = deposits.id)
            )>0";
    $stmt = $dbh->prepare($query);        
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);           
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    //zjištění, zda se jedná o semetral course
    $query2 = "SELECT 		
                    semestralCourse
                FROM groupExcercises                    
                WHERE id = :id";
    $stmt = $dbh->prepare($query2);
    $stmt->bindParam(":id", $_POST["ge"], PDO::PARAM_INT);
    $stmt->execute();
    $semCourse = $stmt->fetch(PDO::FETCH_OBJ);
    
    // dotažení  ceny cvičení v závislosti na tom, zda se jedná o semetrální kurz nebo ne      
    
    if ($semCourse->semestralCourse == null) {       
        $query2 = " SELECT 		
                        price,
                        date
                    FROM groupExcercises                    
                    WHERE id = :id";
    } else {
        $query2 = " SELECT 		
                        indivPrice as price,
                        (SELECT date FROM groupExcercises WHERE id = :id) as date
                    FROM semestralCourses
               
                    WHERE id = (SELECT semestralCourse from groupExcercises WHERE id = :id)";
    }
       
    $stmt = $dbh->prepare($query2);
    $stmt->bindParam(":id", $_POST["ge"], PDO::PARAM_INT);
    $stmt->execute();
    $resultForVisit = $stmt->fetch(PDO::FETCH_OBJ);

    $query = "INSERT INTO visits (client, date, depositId, ge, prepaid, lastEditDate) VALUES (:client, :date, :depositId, :ge, :prepaid, NOW())";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->bindParam(":date", $resultForVisit->date, PDO::PARAM_STR);
    $stmt->bindParam(":depositId", $result->minDepId, PDO::PARAM_INT);  
    $stmt->bindParam(":ge", $_POST["ge"], PDO::PARAM_INT);
    $stmt->bindParam(":prepaid", $resultForVisit->price, PDO::PARAM_INT);          
    $stmt->execute();
}
