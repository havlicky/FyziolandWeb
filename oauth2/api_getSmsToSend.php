<?php

// include our OAuth2 Server object
require_once __DIR__.'/server.php';

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $server->getResponse()->send();
    die;
}

require_once "../php/class.settings.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

if ($_GET["action"] === "getSmsToSend") {
    $query = "  
        SELECT
            l.id,
            l.message as note,
            AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
            CONCAT(               
               DATE_FORMAT(l.WLdate, '%d.%m.%Y'), ' ', DATE_FORMAT(l.WLtime, '%H:%i'), ' ', a.shortcut, ' ',
               AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'), ' ', SUBSTRING(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'),1, 1), '. ', AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "')
            ) as logText,
            (
                SELECT
                    COUNT(lWL.id)
                FROM logWL lWL
                WHERE 
                    lWL.actionTimestamp IS NULL AND
                    (lWL.WLdate>curdate() OR IF(lWL.WLdate = curdate(), lWL.WLtime> curtime()+ INTERVAL 4 HOUR, FALSE)) AND
                    !EXISTS(SELECT r.id FROM reservations r WHERE r.active = 1 AND r.personnel = lWL.therapist AND r.date = lWL.WLdate AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = lWL.WLtime) AND
                    EXISTS(
                        SELECT id
                        FROM personAvailabilityTimetable pat
                        WHERE
                            pat.date = l.WLdate AND            
                            pat.time = l.WLtime AND 
                            pat.person = l.therapist
                    )
            ) remainsToSend
            
        FROM logWL l
        JOIN clients c ON c.id = l.client
        JOIN adminLogin a ON a.id = l.therapist
        LEFT JOIN clientWLparam p ON p.client = l.client

        WHERE 
            
            l.actionTimestamp IS NULL AND            
            (l.WLdate>curdate() OR IF(l.WLdate = curdate(), l.WLtime> curtime()+ INTERVAL 4 HOUR, FALSE)) AND
            
            -- terapeut má stále volné políčko (neexistuje rezervace pro daného terapeuta na daný den a čas)
            !EXISTS(SELECT r.id FROM reservations r WHERE r.active = 1 AND r.personnel = l.therapist AND r.date = l.WLdate AND CAST(CONCAT(r.hour, ':', r.minute) AS TIME) = l.WLtime) AND
            
            -- neposlal jsem stejnému klientovi SMS v kratším intervalu než 10 minut
            !EXISTS(
                SELECT logWL.id 
                FROM logWL 
                LEFT JOIN clients ON clients.id = logWL.client 
                WHERE 
                    c.phone = clients.phone AND 
                    logWL.actionTimestamp IS NOT NULL AND
                    TIMESTAMPDIFF(MINUTE, logWL.actionTimestamp, CURRENT_TIMESTAMP())<10
                ORDER BY logWL.actionTimestamp DESC
                LIMIT 1
            ) AND
            
            -- terapeut má stále zelené políčko na daný den a čas
            EXISTS(
                SELECT id
                FROM personAvailabilityTimetable pat
                WHERE
                    pat.date = l.WLdate AND            
                    pat.time = l.WLtime AND 
                    pat.person = l.therapist
            ) AND
            
            (
                -- když poslední SMS byla na neurgentního klienta a předposlední na urgentního klienta, čekáme deset minut; (způsobí, že se pošlou SMS všem urgentním a jednomu neurgentnímu a pak se čeká 10 minut)
                -- stejných 10 minut musí být nastaveno i na posílání na stejné číslo -- jinak by to způsobilo to, že to pošle urgentní z jednoho termínu (a pokud jsou ti urgentní i na jiném termínu, tak by je to pak přeskočilo a začalo posílat neurgentní na tom dalším termínu a to nechci, takže když jsou časy stejné tak to současně umožní poslat na stejná čísla znovu ty urgentní na novém termínu - takže to nejdřív postupně naposílá všechny termíny urgentní a pak teprve neurgentní
                -- jinak posíláme dál; správně bych měl zjistit, zda je aktuální na neurgentního a předchozí na urgentníno a pak čekat, ale musel bych zesložitit dotaz (v podstatě ho tady celý zopakovat, abych zjistil, co se chystám poslat)
                 IF(
                    (SELECT par.urgent FROM logWL LEFT JOIN clientWLparam par ON par.client = logWL.client WHERE logWL.service = par.service AND logWL.actionTimestamp IS NOT NULL ORDER BY logWL.actionTimestamp DESC LIMIT 1) = 0 AND
                    (SELECT par.urgent FROM logWL LEFT JOIN clientWLparam par ON par.client = logWL.client WHERE logWL.service = par.service AND logWL.actionTimestamp IS NOT NULL ORDER BY logWL.actionTimestamp DESC LIMIT 1, 1) = 1 AND
                    (SELECT TIMESTAMPDIFF(MINUTE, logWL.actionTimestamp, CURRENT_TIMESTAMP()) FROM logWL WHERE actionTimestamp IS NOT NULL ORDER BY actionTimestamp DESC LIMIT 1)<10,
                    FALSE,
                    TRUE
                )                                     
            )
        ORDER BY l.WLdate ASC, l.WLtime ASC, p.urgent DESC
        LIMIT 1";
    
	$stmt = $dbh->prepare($query);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_OBJ);
	
	if ($result === false) {
		$result = new stdClass;
	}
    
    echo json_encode($result);
}