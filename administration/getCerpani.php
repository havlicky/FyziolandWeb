<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "  SELECT DISTINCT               
                CONCAT(
                    DAY(visits.date), 
                    '. ', 
                    MONTH(visits.date), 
                    '. ', 
                    YEAR(visits.date)                    
                ) AS date,
                CONCAT (AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') , ' ', AES_DECRYPT(c.name,'" . Settings::$mySqlAESpassword . "')) as client,                                	
                ge.title,
                visits.prepaid,                
                al.displayName,
                (
                    IFNULL 
                    ((SELECT 
                        deposits.amount			
                        FROM deposits WHERE deposits.Id = visits.depositId AND deposits.paymentDate<=visits.date),0)					
                                 - 
                    IF(visits.depositId IS NULL, 
                        (SELECT IFNULL (SUM(v.prepaid),0) 
                            FROM visits as v 
                            WHERE 
                                    (v.client = visits.client AND v.depositId IS NULL AND v.date <= visits.date)
                        ),
                        (SELECT IFNULL (SUM(v.prepaid),0) 
                            FROM visits as v 
                            WHERE 
                                (v.depositId =visits.depositId AND v.date <= visits.date)
                        ))
                    ) AS Credit,	                    
                    visits.id	
                FROM visits 

            LEFT JOIN clients c on c.id = visits.client
            LEFT JOIN deposits on c.id = deposits.clientId
            LEFT JOIN groupExcercises ge on ge.id = visits.ge            
            LEFT JOIN adminLogin al on al.id = ge.instructor           

            WHERE 
                visits.depositId= :id                
            ORDER BY visits.date    ";
$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":id", $_POST["id"], PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
