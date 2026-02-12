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


$query = "  SELECT
                id,
                IF(noteWL NOT LIKE '%TBD%',
                    (CONCAT
                        ('<b>Obecná poznámka z rezervací:</b> ',                        
                        noteWL, 
                        '<br><br>', '<b>Týdenní poznámky z rezervací:</b> <br>',
                        IFNULL(
                            (SELECT GROUP_CONCAT(CONCAT(DAY(firstDayOfWeek),'.',MONTH(firstDayOfWeek),'.',YEAR(firstDayOfWeek),'.',note) ORDER BY firstDayOfWeek ASC SEPARATOR '<br>')
                            FROM weeknotes
                            WHERE 
                                client = clients.id AND
                                firstDayOfWeek > CURRENT_TIMESTAMP() - INTERVAL 7 DAY
                            ORDER BY client)
                            ,
                            ' žádné'),
                        '<br>'
                    )                                             
                ),
                (CONCAT
                     ('<b><FONT COLOR=red>Obecná poznámka z rezervací:<FONT COLOR=black></b> ',
                      noteWL,
                      '<br><br>', '<b>Týdenní poznámky z rezervací:</b> <br>',
                        IFNULL(
                            (SELECT GROUP_CONCAT(CONCAT(DAY(firstDayOfWeek),'.',MONTH(firstDayOfWeek),'.',YEAR(firstDayOfWeek),'.',note) ORDER BY firstDayOfWeek ASC SEPARATOR '<br>')
                            FROM weeknotes
                            WHERE 
                                client = clients.id AND
                                firstDayOfWeek > CURRENT_TIMESTAMP() - INTERVAL 7 DAY
                            ORDER BY client),
                            'žádné'),                            
                        '<br>'
                    )
                )
                
                ) as resnotes
                                        
            FROM clients 
            WHERE noteWL IS NOT NULL AND id = :id
            

            ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":id", $_POST["id"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchALL(PDO::FETCH_OBJ);
    
echo json_encode($results);
