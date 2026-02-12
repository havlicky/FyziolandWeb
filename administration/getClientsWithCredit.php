<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

$query = "  SELECT                                 
                c.id as clientId,
                CONCAT(AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'), ', ', AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')) AS client,
                (
                    (SELECT 
                        IFNULL (SUM(deposits.amount),0) 
                        FROM deposits WHERE deposits.Id IN 
                                (SELECT relationdepositsallowedclients.depositId from relationdepositsallowedclients 
                                 WHERE relationdepositsallowedclients.clientId = c.id) AND deposits.paymentDate <= (SELECT date FROM groupExcercises WHERE id = :ge)) 
                                 - 
                    (SELECT 
                        IFNULL (SUM(visits.prepaid),0) 
                        FROM visits 
                                WHERE 
                                (
                                visits.depositId IN 
                                        (SELECT relationdepositsallowedclients.depositId from relationdepositsallowedclients 
                                         WHERE relationdepositsallowedclients.clientId = c.id) OR
                                        (visits.client = c.id AND visits.prepaid>0 AND depositId IS NULL)
                                        )

                                AND 

                                (visits.date < (SELECT date FROM groupExcercises WHERE id = :ge))
                    )					
                ) AS creditPred,
                IF((SELECT COUNT(client) FROM visits WHERE visits.ge = :ge AND visits.client = c.id) > 0, 'checked', NULL) as ucast,
                IF((SELECT COUNT(client) FROM visits WHERE  visits.ge = :ge AND client = c.id)>0,
                    (
                        (SELECT 
                            IFNULL (SUM(deposits.amount),0) 
                            FROM deposits WHERE deposits.Id IN 
                                    (SELECT relationdepositsallowedclients.depositId from relationdepositsallowedclients 
                                     WHERE relationdepositsallowedclients.clientId = c.id) AND deposits.paymentDate <= (SELECT date FROM groupExcercises WHERE id = :ge)) 
                                     - 
                        (SELECT 
                            IFNULL (SUM(visits.prepaid),0) 
                            FROM visits 
                                    WHERE 
                                    (
                                    visits.depositId IN 
                                            (SELECT relationdepositsallowedclients.depositId from relationdepositsallowedclients 
                                             WHERE relationdepositsallowedclients.clientId = c.id) OR
                                            (visits.client = c.id AND visits.prepaid>0 AND depositId IS NULL)
                                            )

                                    AND 

                                    (visits.date <= (SELECT date FROM groupExcercises WHERE id = :ge))
                        )					
                    )
                , '') as creditPo
                                
            FROM clients c 
            
            LEFT JOIN visits v ON v.client = c.id            
            
            WHERE
                (
                    (SELECT 
                        IFNULL (SUM(deposits.amount),0) 
                        FROM deposits WHERE deposits.Id IN 
                                (SELECT relationdepositsallowedclients.depositId from relationdepositsallowedclients 
                                 WHERE relationdepositsallowedclients.clientId = c.id) AND deposits.paymentDate <= (SELECT date FROM groupExcercises WHERE id = :ge)) 
                                 - 
                    (SELECT 
                        IFNULL (SUM(visits.prepaid),0) 
                        FROM visits 
                                WHERE 
                                (
                                visits.depositId IN 
                                        (SELECT relationdepositsallowedclients.depositId from relationdepositsallowedclients 
                                         WHERE relationdepositsallowedclients.clientId = c.id) OR
                                        (visits.client = c.id AND visits.prepaid>0 AND depositId IS NULL)
                                        )

                                AND 

                                (visits.date < (SELECT date FROM groupExcercises WHERE id = :ge))
                    )					
                ) >0
            GROUP BY c.id
            ORDER BY AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')";
$stmt = $dbh->prepare($query);                                    
$stmt->bindValue(":ge", $_POST["ge"], PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

echo json_encode($results);
