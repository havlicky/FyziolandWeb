<?php

$pageTitle = "FL - Rezervace dle terapeuta";

require_once "checkLogin.php";

if ($resultAdminUser->isTerapist !== "1" && $resultAdminUser->isSuperAdmin !== "1") {
    header("Location: viewGroupReservations");
}

//přidáno
if (isset($_GET["user"])) {    
    $selectedUserCookie = intval($_GET["user"]);  
    
    $cookieExpiry = time() + (23 - intval(date("H"))) * 3600 + (59 - intval(date("i"))) * 60 + (59 - intval(date("s")));
    setcookie("selectedUser", intval($_GET["user"]), $cookieExpiry, "/", "fyzioland.cz", TRUE, TRUE);
} else {
    $selectedUserCookie = intval($_COOKIE["selectedUser"]);
}

require_once "../header.php";


if (isset($_GET["date"])) {
    if (!$date = (new DateTime)->createFromFormat("Y-m-d", $_GET["date"])) {
        $date = (new DateTime());
    }
} else {
    $date = (new DateTime()); 
}
$today = new DateTime();
$days = array(1 => "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle");  

//zjištění id přihlášeného uživatele pro určení uživatele, který smazal rezervaci v případě kliku na popelnici
$loggedUser = ($_COOKIE["loginName"]);
echo "<script>console.log('$loggedUser');</script>";

$query = "  SELECT id FROM adminLogin WHERE login = :loginName";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":loginName", $loggedUser, PDO::PARAM_STR);
$stmt->execute();
$resultUser = $stmt->fetch(PDO::FETCH_OBJ);

if ( intval($resultAdminUser->isSuperAdmin) === 1 && !empty($selectedUserCookie)) {
    $selectedUser = $selectedUserCookie;
} else {
    // pokud jsem přihlášený já, nastaví to selecteduser Katku
    if ($resultAdminUser->id == 5) {
        $selectedUser = 8;
    } else {
            $selectedUser = $resultAdminUser->id;            
    }
}

$query = "  SELECT               
                r.id,                
                r.client,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                r.date,
                r.hour,
                r.minute,
                r.service as serviceId,
                s.name AS service,               
                r.note,
                r.internalNote,
                IF (r.sooner = 1, 'checked', 'NULL') as sooner,
                r.creationTimestamp,
                r.source,
                r.smsIdentification,
                IF(r.code = 0, NULL, r.code) as code, 
                r.deleteHash,
                CASE WHEN (SELECT count(id) FROM clients WHERE AES_DECRYPT(clients.email, '" . Settings::$mySqlAESpassword . "') = AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "')) >= 1 THEN 1 ELSE 0 END AS isInClients
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            WHERE 
                r.active = 1 AND
                r.date = :date AND
                r.personnel = :personnel
            ORDER BY r.date, r.hour, r.minute";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":date", $date->format("Y-m-d"), PDO::PARAM_STR);
$stmt->bindValue(":personnel", $selectedUser, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

?>
        
        <?php include "menu.php" ?>

        <div class="container-fluid" id="administrace-rezervaci" style="margin-top: 10px;">
            <div class="col-lg-10 col-lg-offset-1">
                <div class="row">
                    <div class="col-md-6 rezervace-prehled-rezervaci">
                        <h2>
                            Přehled rezervací 
                            <input class="form-control text-center" style="display: inline-block; width: auto; font-size: 20px;" name="date" value="<?= $days[$date->format("N")] ?>, <?= $date->format("j. n. Y") ?>">
                            <input class="form-control" type="hidden" name="dateFormatted" id="dateFormatted" value="<?= $date->format("Y-m-d") ?>">
                        </h2>
                    </div>
                    <div class="col-md-6 rezervace-vyber-terapeuta">
                        <h2>
                            Výběr terapeuta 
                            <select name="terapist" id="terapist" class="form-control" style="display: inline-block; width: auto;">
                                <?php
                                    if (intval($resultAdminUser->isSuperAdmin) === 1) {
                                        $query = "  SELECT
                                                        a.id,
                                                        a.displayName
                                                    FROM adminLogin AS a
                                                    WHERE
                                                        a.active = 1 AND
                                                        EXISTS (SELECT id FROM relationPersonService WHERE person = a.id)
                                                    ORDER BY a.displayName";
                                        $stmt = $dbh->prepare($query);
                                    } else {
                                        $query = "  SELECT
                                                        a.id,
                                                        a.displayName
                                                    FROM adminLogin AS a
                                                    WHERE
                                                        a.active = 1 AND
                                                        a.login = :login AND
                                                        EXISTS (SELECT id FROM relationPersonService WHERE person = a.id)
                                                    ORDER BY a.displayName";
                                        $stmt = $dbh->prepare($query);
                                        $stmt->bindParam(":login", $_COOKIE["loginName"], PDO::PARAM_STR);
                                    }
                                    $stmt->execute();
                                    $resultsUsers = $stmt->fetchAll(PDO::FETCH_OBJ);
                                    
                                ?>
                                <?php foreach ($resultsUsers as $user): ?>
                                <option value="<?= $user->id ?>" <?= $user->id == $selectedUser ? "selected" : "" ?>><?= $user->displayName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </h2>
                    </div>
                </div>

                <?php
                    if (isset($_SESSION["messageBox"])) {
                ?>
                <div class="alert <?= $_SESSION["messageBox"]->getClass() ?> in fade text-center">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <?= $_SESSION["messageBox"]->getText() ?>
                </div>
                <?php
                    }
                    unset($_SESSION["messageBox"]);
                ?>

                <div class="row" id='rezervace-navigace'>
                    <div class="col-xs-4 text-left">
                        <a href="viewReservations.php?date=<?= (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P1D"))->format("Y-m-d"); ?>">
                            <h4>
                                <span class="glyphicon glyphicon-chevron-left"></span>
                                Předchozí den
                            </h4>
                        </a>
                    </div>
                    <div class="col-xs-4 text-center">
                        <?php
                            if ($today->format("Y-m-d") !== $date->format("Y-m-d")) {
                        ?>
                        <a href="viewReservations.php">
                            <h4>
                                Dnešní den
                            </h4>
                        </a>
                        <?php
                            }
                        ?>
                    </div>
                    <div class="col-xs-4 text-right">
                        <a href="viewReservations.php?date=<?= (new DateTime($date->format("Y-m-d")))->add(new DateInterval("P1D"))->format("Y-m-d"); ?>">
                            <h4>
                                Následující den
                                <span class="glyphicon glyphicon-chevron-right"></span>
                            </h4>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <table class="table table-bordered table-hover table-striped" id="prehled-rezervaci">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 4%; vertical-align: middle;">Rezervace</th>
                                <th class="text-center" style="width: 2%; vertical-align: middle;">Slot</th>
                                <th style="width: 7%; vertical-align: middle;">Jméno</th> 
                                <th style="width: 8%; vertical-align: middle;">Příjmení</th> 
                                <th style="width: 12%; vertical-align: middle;">E-mailová adresa</th> 
                                <th style="width: 8%; vertical-align: middle;">Telefon</th> 
                                <th style="width: 8%; vertical-align: middle;">Služba</th> 
                                <th style="width: 13%; vertical-align: middle;">Poznámka od klienta</th> 
                                <th style="width: 14%; vertical-align: middle;">Interní poznámka</th> 
                                <th style="width: 3%; vertical-align: middle;">Chce dřív</th>                                 
                                <th style="width: 3%; vertical-align: middle;">Kód</th>                                                                                                
                                <th style="width: 10%;" class="text-center" style="vertical-align: middle;">Akce</th>
                                <th class="text-center" style="width: 8%; vertical-align: middle;">Vytvořeno</th>                                
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                for ($i = 6; $i <= 22; $i++) {
                                    //if (in_array($i, Settings::$notAllowedTimes)) continue; // pauza na oběd

                                    if (count($results) > 0) {
                                        foreach ($results as $resultPart) {
                                            if (intval($resultPart->hour) === $i) {
                                                $result = $resultPart;
                                                break;
                                            }
                                            $result = null;
                                        }
                                    } else {
                                        $result = null;
                                    }

                                    if (!is_null($result)) {
                            ?>
                            <tr data-date="<?= $date->format("Y-m-d") ?>" data-date-formatted="<?= $date->format("j. n. Y") ?>" data-time="<?= str_pad($i, 2, "0", STR_PAD_LEFT) . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT) ?>" data-id="<?= $result->id ?>" data-client="<?= $result->client?>" data-name="<?= $result->name ?>" data-surname="<?= $result->surname ?>" data-service="<?= $result->serviceId?>" data-email="<?= $result->email ?>" data-phone="<?= $result->phone ?>" data-action="RES">
                                <td class="text-center" data-sort="<?= $i ?>"><?= htmlentities($result->hour . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT)) ?></td>
                                <td data-type="slot-toggle" style="text-align: center; vertical-align: middle;"></td>
                                <td data-field="name">
                                    <span data-role="content"><?= nl2br(htmlentities($result->name)) ?></span>
                                    <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                </td>
                                <td data-field="surname">
                                    <span data-role="content"><?= nl2br(htmlentities($result->surname)) ?></span>
                                    <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                </td>
                                
                                <?php if ($resultAdminUser->seeContactDetails === "1"): ?>
                                    <td data-field="email">
                                        <a href="mailto:<?= htmlentities($result->email) ?>"><span data-role="content"><?= htmlentities($result->email) ?></span></a>                                     
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>
                                <?php endif; ?>
                                <?php if ($resultAdminUser->seeContactDetails === "0"): ?>
                                    <td data-field="email">
                                        
                                    </td>
                                <?php endif; ?>
                                
                                <?php if ($resultAdminUser->seeContactDetails === "1"): ?>
                                    <td data-field="phone">
                                        <a href="tel:<?= htmlentities($result->phone) ?>"><span data-role="content"><?= htmlentities($result->phone) ?></span></a>                                                                         
                                        <?php if ( !empty($result->phone) ): ?>                                    
                                            <?php if (empty($result->smsIdentification)): ?>
                                            <input type="checkbox" name="sms" title="Označit rezervaci pro odeslání SMS">
                                            <?php else: ?>
                                            <span class="glyphicon glyphicon-phone" aria-hidden="true" title="" name="smsDetailIcon"></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                        <?php if ( !empty($result->phone) ): ?>  
                                            <a href="#" data-type="QRphone" title="Ukázat QR kód pro telefonní číslo">
                                            <span class="glyphicon glyphicon-phone"></span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <?php if ($resultAdminUser->seeContactDetails === "0"): ?>
                                    <td data-field="phone">
                                        
                                    </td>
                                <?php endif; ?>
                                                                
                                <td>
                                    <select name="service" id= "<?='service' . $i?>" class="form-control" style="display: inline-block; width: auto;">
                                        <?php                                        
                                            
                                            $query = "  SELECT
                                                            s.id,
                                                            s.name
                                                        FROM services AS s                                                        
                                                        WHERE s.id IN (SELECT service from relationPersonService WHERE relationPersonService.person = :user)
                                                        ORDER BY s.order";
                                            $stmt = $dbh->prepare($query);                                            
                                            $stmt->bindParam(":user", $selectedUser, PDO::PARAM_INT);
                                            $stmt->execute();
                                            $resultsServices = $stmt->fetchAll(PDO::FETCH_OBJ);

                                        ?>
                                        <?php foreach ($resultsServices as $service): ?>
                                            <option value="<?= $service->id ?>" <?= $service->id == $result->serviceId ? "selected" : "" ?>><?= $service->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>                                                                                                
                                                                
                                <td data-field="note">
                                    <span data-role="content"><?= nl2br(htmlentities($result->note)) ?></span>
                                    <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                </td>
                                <td data-field="internalNote">
                                    <span data-role="content"><?= nl2br(htmlentities($result->internalNote)) ?></span>
                                    <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                </td>                                                                                           
                                <td data-field="sooner" class="text-center">
                                    <span data-role="content"><input type="checkbox" name="sooner" title="Klient má zájem o kontaktování, pokud se uvolní termín dříve" <?=$result->sooner?>> </span>
                                </td>                                                                
                                <td data-field="code">                                                                            
                                    <span data-role="content"><?= ($result->code) ?></span>                                   
                                    <a href="#" style="float: right;" data-role="editField">
                                    <span class="glyphicon glyphicon-pencil"></span></a>
                                </td>  
                                
                                <td class="text-center">
                                    <a href="sendReservation.php?id=<?= $result->id ?>&amp;returnTo=<?= $date->format("Y-m-d") ?>" name="sendResEmail" title="Zaslat emailem potvrzení rezervace">
                                        <span class="glyphicon glyphicon-envelope"></span>
                                    </a> 
                                    <a href="deleteReservation_quest.php?id=<?= $result->id ?>&amp;source=1&amp;returnTo=<?= $date->format("Y-m-d") ?>&amp;user=<?= $resultUser->id ?>" name="delete" title="Smazat rezervaci bez zápisu na čekací listinu">
                                        <span class="glyphicon glyphicon-trash"></span>                                    
                                    </a>                                    
                                    <a href="#" data-type="QRsms" title="Ukázat QR kód pro SMS">
                                        <span class="glyphicon glyphicon-phone"></span>                                        
                                    </a>
                                    <a href="#" data-type="movetowl" title="Smazat rezervaci a současně provést záznam na čekací listinu">
                                        <span class="glyphicon glyphicon-bell"></span>
                                    </a>                                                                        
                                    <?php if ($result->serviceId === "10" || $result->serviceId === "12"): ?>
                                    <a href="dotaznikReadOnly.php?hash=<?= $result->deleteHash ?>&amp;returnTo=<?= $date->format("Y-m-d") ?>" name="viewEntryQuest" title="Zobrazit vstupní/kontrolní dotazník" target="_blank">  
                                        <span class="glyphicon glyphicon-list-alt"></span>
                                    </a>
                                    <?php endif; ?>
                                    <a href="#" data-type="freeSlots" title="Přesunout rezervaci na jiný termín">
                                        <span class="glyphicon glyphicon-arrow-right"></span>
                                    </a>
                                    <a href="#" data-type="relatedRes" title="Zobrazit další rezervace klienta">
                                        <span class="glyphicon glyphicon-th-list"></span>
                                    </a>                                    
                                    <!--
                                    <a href="deleteReservation.php?id=<?= $result->id ?>&amp;source=1&amp;returnTo=<?= $date->format("Y-m-d") ?>&amp;user=<?= $resultUser->id ?>" name="delete" title="Smazat rezervaci bez zápisu na čekací listinu">
                                        <span class="glyphicon glyphicon-trash"></span>                                    
                                    </a>
                                    -->
                                </td>
                                
                                <td style="color: #999999" class="text-center"> <?= 'ID: '. $result->id . '; ' . $result->source . '<br>'. (new DateTime($result->creationTimestamp))->format("j. n. Y H:i") ?></td>  
                            </tr>
                            <?php
                                    } else {
                                        if ($i == 10 || $i == 11 || $i == 12) {
                                            $minuteFrom = "15";
                                        } else {
                                            $minuteFrom = "00";
                                        }
                            ?>
                            <tr data-date="<?= $date->format("Y-m-d") ?>" data-date-formatted="<?= $date->format("j. n. Y") ?>" data-time="<?= str_pad($i, 2, "0", STR_PAD_LEFT) . ":" . $minuteFrom ?>">
                                <?php if ($resultAdminUser->makeReservations === "1"): ?>
                                    <td class="text-center" data-sort="<?= $i ?>"><a href="#" name="modalLaunch" data-toggle="modal" data-target="#rezervace-modal" data-hour="<?= $i ?>" data-minute="<?= $minuteFrom ?>"><?= htmlentities($i . ":" . $minuteFrom) ?></a></td>
                                <?php endif; ?>
                                <?php if ($resultAdminUser->makeReservations  === "0"): ?>
                                    <td class="text-center" data-sort="<?= $i ?>"> <?= $i ?>:<?= $minuteFrom ?></td>
                                <?php endif; ?>
                                <td data-type="slot-toggle"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td style="color: #999999" class="text-center"></td>
                                <td class="text-center"></td>
                            </tr>
                            <?php
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-lg-1 text-center" style="margin-top: 10px;">
                        <h4><b>Přesměrování</b></h4>
                    </div>
                    <div class="col-lg-2" style="margin-top: 10px;">
                        <button class="btn btn-light"  style="width: 250px;" type="button" id="buttonReservationsMonth"><b>Měsíční Ergo</b> všichni</button>
                    </div>
                    <div class="col-lg-2" style="margin-top: 10px;">
                        <button class="btn btn-light"  style="width: 250px;" type="button" id="buttonReservationsWeek"><b>Týdenní</b> přehled rezervací</button>
                    </div>
                    <div class="col-lg-2" style="margin-top: 10px;">
                        <button class="btn btn-light" style="width: 250px;" type="button" id="buttonReservationsErgoAll"><b>Denní</b> přehled <b>Ego</b> všichni</button>
                    </div>
                    <div class="col-lg-2" style="margin-top: 10px;">
                        <button class="btn btn-light" style="width: 250px;" type="button" id="buttonReservationsAll"><b>Denní</b> přehled <b>všichni</b></button>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-lg-12 text-center" style="margin-top: 20px;">
                        <h4>Odesílání SMS</h4>
                    </div>
                </div>
                                
                <?php if ($resultAdminUser->isSuperAdmin === "1"): ?>
                <div class="row">
                    <div class="col-lg-4" style="margin-bottom: 5px;">
                        <label>Výběr příjemců</label><br>
                        <button class="btn btn-success btn-block" type="button" id="buttonMarkAll">Označit všechny rezervace v tento den</button>
                        <button class="btn btn-success btn-block" type="button" id="buttonMarkNone">Neoznačit žádnou rezervaci</button>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="smsText">Text zprávy</label>
                            <select class="form-control" name="smsText" id="smsText">
                                <?php
                                    $query = "SELECT id, text FROM smsText WHERE purpose = 'i' ORDER BY id";
                                    $stmt = $dbh->prepare($query);
                                    $stmt->execute();
                                    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
                                    foreach ($results as $result) {
                                ?>
                                <option value="<?= $result->id ?>"><?= htmlentities($result->text) ?></option>
                                <?php
                                    }
                                ?>
                                <option value="">Napsat vlastní text</option>
                            </select>
                            <div id="smsCustomTextContainer" class="hidden text-right">
                                <textarea class="form-control" name="smsCustomText" id="smsCustomText"></textarea>
                                <span id="smsCustomTextCounter">0</span>/<span id="smsCustomTextLimit">160</span>, počet zpráv: 
                                <span id="smsCustomTextMessageCount">1</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <label>Výběr akce</label><br>
                        <button class="btn btn-success btn-block" type="button" id="buttonSendSMS">Odeslat SMS zprávy</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            
            <?php
                $query = "  SELECT
                                id,
                                AES_DECRYPT(name, '" . Settings::$mySqlAESpassword . "') AS name,
                                CAST(AES_DECRYPT(surname, '" . Settings::$mySqlAESpassword . "') AS char(100)) COLLATE utf8_czech_ci AS surname,
                                AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') AS email,
                                AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') AS phone
                            FROM clients
                            ORDER BY surname, name";
                $stmt = $dbh->prepare($query);
                $stmt->execute();
                $clients = $stmt->fetchAll(PDO::FETCH_OBJ);
            ?>
            
            <div class="modal fade"  id="rezervace-modal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <form action="rezervaceAction" method="post" id="reservationsForm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Zadání rezervace</h4>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="search">Vyhledat klienta:</label>
                                            <select class="form-control" id="search">
                                                <option></option>
                                                <?php
                                                    foreach ($clients as $client) {
                                                ?>
                                                <option value="<?= $client->id ?>" data-id="<?= $client->id ?>" data-email="<?= $client->email ?>" data-name="<?= $client->name ?>" data-surname="<?= $client->surname ?>" data-phone="<?= $client->phone ?>"><?= $client->surname . ", " . $client->name ?></option>
                                                <?php
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dateAndTimeOfReservation">Datum a čas rezervace: </label>
                                            <input class="form-control" type="text" id="dateAndTimeOfReservation" value="" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="service">Zvolená služba: </label>
                                            <select class="form-control" name="service" id="service">
                                                <?php
                                                    $query = "  SELECT
                                                                    s.id,
                                                                    s.name
                                                                FROM relationPersonService AS rps
                                                                LEFT JOIN services AS s ON s.id = rps.service
                                                                WHERE rps.person = :person
                                                                ORDER BY rps.orderRank";
                                                    $stmt = $dbh->prepare($query);
                                                    $stmt->bindParam(":person", $selectedUser, PDO::PARAM_INT);
                                                    $stmt->execute();
                                                    $resultsServices = $stmt->fetchAll(PDO::FETCH_OBJ);
                                                ?>
                                                <?php foreach ($resultsServices as $resultService): ?>
                                                <option value="<?= $resultService->id ?>"><?= $resultService->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Jméno: </label>
                                            <input class="form-control" type="text" name="name" id="name" value="" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="surname">Příjmení: </label>
                                            <input class="form-control" type="text" name="surname" id="surname" value="" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="padding-top: 0px; padding-bottom: 0px;">
                                    <div class="col-sm-9">
                                        <div class="form-group">
                                            <label for="email">E-mailová adresa: </label>
                                            <input class="form-control" type="email" name="email" id="email" value="">
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label for="email">Zaslat e-mail: </label>
                                            <select class="form-control" name="send_email">
                                                <option value="1">Ano</option>
                                                <option value="0">Ne</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="phone">Telefonní číslo: </label>
                                            <input class="form-control" type="tel" name="phone" id="phone" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="alert-type">Zaslání zdarma upozornění dva dny před nadcházející terapií: </label>
                                            <select class="form-control" name="alert-type" id="alert-type">
                                                <option value="email">E-mailem</option>
                                                <!--
                                                <option value="sms">SMS zprávou na mobilní telefon</option>
                                                <option value="both">SMS zprávou na mobilní telefon i E-mailem</option>
                                                -->
                                                <option value="none">Nezasílat</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="note">Indikativní popis obtíží: </label>
                                            <textarea class="form-control" name="note" id="note"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="checkbox small">
                                            <label>
                                                <input type="checkbox" name="personalDetailsAgreement" value="1" checked required> V souladu s&nbsp;Nařízením Evropského parlamentu a&nbsp;Rady (EU) 2016/679 (GDPR) v&nbsp;platném znění souhlasím se zpracováváním osobních údajů správcem Fyzioland s.r.o.
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="date" name="date" value="">
                                <input type="hidden" id="hour" name="hour" value="">
                                <input type="hidden" id="minute" name="minute" value="">
                                <input type="hidden" id="client" name="client" value="">
                                <input type="hidden" id="person" name="person" value="<?= $selectedUser ?>">
                                <input type="hidden" id="backTo" name="backTo" value="<?= $date->format("Y-m-d") ?>">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít bez uložení</button>
                                <button type="submit" class="btn btn-primary" name="submit">Odeslat rezervaci</button>
                            </div>
                        </div><!-- /.modal-content -->
                    </form>
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->    
            
            <!-- modální dialog pro zobrazení QR kódu pro načtení telefonního čísla-->
            <div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-qrphone-modal">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h3 class="modal-title">QR kód pro načtení telefonního čísla</h3>
                        </div>
                        <div class="modal-body text-center">
                            <img src="" alt="QR kód pro načtení telefonního čísla" id="imgQRphone" width="200" />
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-center">Klient</th>
                                        <th class="text-center">Tel. číslo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td id="qrPhoneClient" class="text-center">Klient</td>
                                        <td id="qrPhone" class="text-center">Telefonní číslo</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- modální dialog pro zobrazení QR kódu pro načtení individuální SMS-->
            <div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-qrsms-modal">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h3 class="modal-title">QR kód pro načtení individuální SMS</h3>
                        </div>
                        <div class="modal-body text-center">
                            <img src="" alt="QR kód pro načtení individuální SMS" id="imgQRsms" width="200" />
                            <table class="table">
                                <thead>
                                    <tr>                                        
                                        <th style="width: 15%" class="text-center">Klient</th>
                                        <th style="width: 15%" class="text-center">Tel. číslo</th>
                                        <th style="width: 70%"class="text-center">Text SMS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td id="qrSMSClient" class="text-center">Klient</td>
                                        <td id="qrSMSPhone" class="text-center">Telefonní číslo</td>
                                        <td id="qrSMSFinalText" class="text-left">Text SMS</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- modální dialog pro volné termíny-->
            <div class="modal fade" tabindex="-1" role="dialog" id="modal-freeSlots">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>                            
                            <h4 class="modal-title">Přehled VŠECH volných termínů dané služby</h4>                                                      
                        </div>
                                                                        
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" id="tablefreeslots" style="width: 100%">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="vertical-align: middle;">Pracovník</th>
                                            <th class="text-center" style="vertical-align: middle;">Datum</th>                            
                                            <th class="text-center" style="vertical-align: middle;">Den týdne</th>                                
                                            <th class="text-center" style="vertical-align: middle;">Čas</th>
                                            <th class="text-center" style="vertical-align: middle;">Přesunout</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-footer">                            
                            <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- modální dialog pro zobrazení všech budoucích rezervací klienta-->
            <div class="modal fade" tabindex="-1" role="dialog" id="modal-relatedRes">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Přehled budoucích rezervací klienta</h4>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" id="tablerelatedres" style="width: 100%">
                                    <thead>
                                        <tr>                                            
                                            <th class="text-center" style="vertical-align: middle;">Datum</th>                            
                                            <th class="text-center" style="vertical-align: middle;">Den týdne</th>                                
                                            <th class="text-center" style="vertical-align: middle;">Čas</th>
                                            <th class="text-center" style="vertical-align: middle;">Jméno</th>
                                            <th class="text-center" style="vertical-align: middle;">Příjmení</th>                                                                                        
                                            <th class="text-center" style="vertical-align: middle;">Služba</th>
                                            <th class="text-center" style="vertical-align: middle;">Terapeut</th>                                                                                                                                    
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-footer">                            
                            <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function() {                                                           
                    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
                    
                    $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();
                    
                    
                    <?php
                        $query = "  SELECT DISTINCT
                                        YEAR(date) as year,
                                        MONTH(date) as month,
                                        DAY(date) as day
                                    FROM reservations AS r1
                                    WHERE
                                        active = 1 AND
                                        (SELECT COUNT(id) FROM reservations AS r2 WHERE r1.date = r2.date) = 11 AND
                                        personnel = :person
                                    ORDER BY DAY(date)";
                        $stmt = $dbh->prepare($query);
                        $stmt->bindParam(":person", $selectedUser, PDO::PARAM_INT);
                        $stmt->execute();
                        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

                        $dates = array();
                        foreach ((array)$results as $result) {
                            $dates[] = $result->year . $result->month . $result->day;
                        }
                        $jsString = "'" . implode("', '", $dates) . "'";
                    ?>

                    var dates = [<?= $jsString ?>];

                    $("input[name='date']").datepicker({
                        altField: "#dateFormatted",
                        altFormat: "yy-mm-dd",
                        dayNames: ["neděle", "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota"],
                        dayNamesMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
                        firstDay: 1,
                        dateFormat: "DD, d. m. yy",
                        monthNames: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"],
                        beforeShowDay: function(d) {
                            dateString = d.getFullYear().toString() + (d.getMonth() + 1).toString() + d.getDate().toString();
                            if (dates.indexOf(dateString) === -1) {
                                return [true, "decorated", ""];
                            } else {
                                return [true, "", ""];
                            }
                        }
                    });

                    $("input[name='date']").change(function() {
                        window.location = "viewReservations.php?date=" + $("#dateFormatted").val();
                    });

                    $("#prehled-rezervaci").on("click", "a[name='delete']", function(event) {                        
                        if ( confirm('Přejete si klientovi odeslat e-mail s notifikací o zrušené rezervaci?') ) {
                            var newHref = $(this).attr("href") + "&sendEmail=true";
                            $(this).attr("href", newHref);
                        }                          
                    }); 
                                        
                    $("#prehled-rezervaci").on("click", "a[name='sendResEmail']", function(event) {
                        if ( !confirm('Skutečně si přejete odeslat klientovi email s potvrzením rezervace (a vstupní dotazníkem jedná-li se o vstupní vyšetření)?') ) {
                            event.preventDefault();
                        }
                    });
                    
                    var prehledRezervaci;
                    if ($("#prehled-rezervaci td").length > 1) {
                        prehledRezervaci = $("#prehled-rezervaci").DataTable({
                            "order": [[ 0, "asc" ]],
                            "ordering": false,
                            "dom": 'ft',
                            "paging": false,
                            "language": {
                                "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                            },
                            "responsive": true,
                            columnDefs: [
                                { responsivePriority: 1, targets: 0 },
                                { responsivePriority: 2, targets: 1 },                                
                                { orderable: false, targets: 1 }
                            ]
                        });
                    }
                    
                    prehledRezervaci.on( 'responsive-display', function ( e, datatable, row, showHide, update ) {
                        var id = datatable.row(row.index()).nodes().to$().attr("data-id");
                        $("ul[data-dtr-index='" + row.index() + "']").closest("tr").attr("data-id", id);
                    });
                    
                    function formatOption (item) {
                        item = $(item.element);
                        return $("<div>" + item.text() + "<br><small>" + item.attr("data-email") + "</small></div>");  
                    };

                    $("#search").select2({
                        templateResult: formatOption,
                        placeholder: "Začněte psát příjmení klienta",
                        language: {
                            noResults: function () {
                                return $('<a href="#" id="notFound">Nenalezeny žádné výsledky, založit nového klienta</a>');
                            }
                        },
                        allowClear: true,
                        dropdownParent: $("#rezervace-modal")
                    });
                    
                    $("#search").on('select2:select', function (e) {
                        element = $(e.params.data.element);
                        
                        $("#rezervace-modal #name").val(element.attr("data-name"));
                        $("#rezervace-modal #client").val(element.attr("data-id"));
                        $("#rezervace-modal #surname").val(element.attr("data-surname"));
                        $("#rezervace-modal #phone").val(element.attr("data-phone"));
                        $("#rezervace-modal #email").val(element.attr("data-email"));
                    });
                    
                    $("#search").on('select2:unselect', function (e) {
                        $("#rezervace-modal #name").val("");
                        $("#rezervace-modal #client").val("");
                        $("#rezervace-modal #surname").val("");
                        $("#rezervace-modal #phone").val("");
                        $("#rezervace-modal #email").val("");
                    });
                    
                    $("body").on("click", "#notFound", function() {
                        $("#rezervace-modal #name").val("");
                        $("#rezervace-modal #client").val("");
                        $("#rezervace-modal #surname").val("");
                        $("#rezervace-modal #phone").val("");
                        $("#rezervace-modal #email").val("");
                        
                        
                        $("#search").select2("close");
                        $("#name").focus();
                    });
                    
                    $("a[name='modalLaunch']").on("click", function() {
                        tr = $(this).closest("tr");
                        
                        var date = tr.attr("data-date");
                        var dateFormatted = tr.attr("data-date-formatted");
                        var hour = $(this).attr("data-hour");
                        var minute = $(this).attr("data-minute");
                        var timeSpan = $(this).html();

                        $("#dateAndTimeOfReservation").val(dateFormatted + ", " + timeSpan);
                        $("#date").val(date);
                        $("#hour").val(hour);
                        $("#minute").val(minute);
                    });
                    
                    $('.modal').on('shown.bs.modal', function (event) {
                        var button = $(event.relatedTarget);
                        button.one('focus', function (event) {
                            $(this).blur();
                        });
                        
                        $("#search").select2('open');
                    });
                    
                    $("#rezervace-modal button[name='submit']").click(function(event) {
                        if ($("#rezervace-modal #client").val() === "") {
                            if( !confirm('Zadáváte klienta, který do Fyziolandu přichází poprvé, jste si jisti, že chcete pokračovat?\n\nPokud se jedná o klienta, který již u nás byl, najděte ho prosím pomocí vyhledávacího pole a poté teprve založte rezervaci.')) {
                                event.preventDefault();
                            }
                        }
                    });
                    
                    $("#rezervace-modal input[name='phone']").mask("+420 999 999 999");
                    
                    $("#terapist").change(function() {  
                        
                        var targetDate = $("#dateFormatted").val();
                        var selectedUser = $(this).val();
                        
                        document.location = "viewReservations.php?date=" + targetDate + "&user=" + selectedUser;
                    });                                       
                    
                    function getSlots() {
                        $.ajax({
                            url: "getSlots.php",
                            data: { "person": $("#terapist").val(), "dateFrom": $("#dateFormatted").val(), "dateTo": $("#dateFormatted").val() },
                            method: "post",
                            dataType: "json",
                            success: function(dataSet) {
                                $("td[data-type='slot-toggle']").removeClass("green").addClass("grey").attr("title", "Nedostupný slot");

                                $.each(dataSet, function(i, obj) {
                                    var date = obj.date;
                                    var time = obj.time;

                                    var td = $("#prehled-rezervaci").find("tbody tr[data-time='" + time + "']").find("td[data-type='slot-toggle']");
                                    td.removeClass("grey").addClass("green");
                                    td.attr("title", "Dostupný slot");
                                });
                            }
                        });
                    }
                    getSlots();
                    
                    <?php if ($resultAdminUser->isSuperAdmin === "1"  or $resultAdminUser->isSuperAdmin !=="1"): ?>
                    $("#prehled-rezervaci tbody tr td[data-type='slot-toggle']").click(function() {
                        var person = $("select[name='terapist']").val();
                        var date = $(this).closest("tr").attr("data-date");
                        var time = $(this).closest("tr").attr("data-time");

                        $.ajax({
                            url: "changeSlotAvailability.php",
                            data: { "person": person, "date": date, "time": time },
                            method: "post",
                            dataType: "text",
                            success: function(response) {
                                if (response === "1") {
                                    getSlots();
                                } else {
                                    alert("Došlo k chybě, kontaktujte vývojáře :-)");
                                }
                            }
                        });
                    });
                    <?php endif; ?>
                        
                    $("#buttonMarkAll").click(function() {
                        $("input[name='sms']").prop("checked", true);
                    });
                    
                    $("#buttonMarkNone").click(function() {
                        $("input[name='sms']").removeProp("checked");
                    });
                    
                    $("#smsText").change(function() {
                        if ($(this).val() === "") {
                            $("#smsCustomTextContainer").removeClass("hidden");
                            
                             $("#smsCustomTextCounter").html("0");
                            $("#smsCustomTextLimit").text(160);
                            
                            $("#smsCustomText").val("").focus();
                        } else {
                            $("#smsCustomTextContainer").addClass("hidden");
                        }
                    });
                    
                    $("#smsCustomText").on("input", function() {
                        var txt = $(this).val();
                        var txtLength = txt.length;
                        var char;
                        var diacritics = false;
                        var charsInMessage = 160;
                        
                        for (var i = 0; i < txtLength; i++) {
                            char = txt.charAt(i);
                            
                            if ( parseInt(char.charCodeAt(0)) > 127 ) {
                                diacritics = true;
                                break;
                            }
                        }
                        
                        $("#smsCustomTextCounter").html((diacritics ? "<span style='color: red'>Diakritika!!</span> " : "") + txtLength);
                        if (diacritics) {
                            charsInMessage = 70;
                        } else {
                            charsInMessage = 160;
                        }
                        $("#smsCustomTextLimit").text(charsInMessage);
                        
                        $("#smsCustomTextMessageCount").text(Math.floor((txtLength-1) / charsInMessage) + 1);
                    });
                    
                    $("#buttonSendSMS").click(function() {
                        $(this).off("click");
                        $(this).text("Probíhá odesílání, vyčkejte...");
                        
                        var recipients = [];
                        $("input[name='sms']").each(function() {
                            if ( $(this).is(':checked') ) {
                                recipients.push($(this).closest("tr").attr("data-id"));
                            }
                        });
                        
                        $.ajax({
                            url: "sendSMS.php",
                            method: "post",
                            dataType: "text",
                            data: { "table": "reservations", "predefinedMessage":  $("#smsText").val(), "customText": $("#smsCustomText").val(), "recipients": recipients },
                            success: function() {
                                location.reload();
                            }
                        });
                    });
                   
                    $.widget.bridge("tlp", $.ui.tooltip); // ošetření konfliktu s widgetem tooltip() v Bootstrap, používáme ten z jQuery UI
                    $("span[name='smsDetailIcon']").tlp({
                        content: function(callback) {
                            $.post("getSMSinfo.php", { "table": "reservations", "id": $(this).closest("tr").attr("data-id") }, function(response) { callback(response); }, "html");
                        }
                    });
                                        
                    $("tbody").on("change", "select", function() {
                        var newService = $(this).val();
                        var id = $(this).closest("tr").attr("data-id");
                        var date = $("input[name='dateFormatted']").val();  
                        var terapist = $("select[name='terapist']").val();                                                  
                        
                        //alert('id: ' + id);
                        //alert('NewService: ' + newService);
                        //alert('Date: ' + date);
                         //alert('Terapist: ' + terapist);
                        
                        $.ajax({
                            url: "updateService.php",
                            method: "post",
                            dataType: "text",
                            data: {"id": id, "newService": newService},
                            success: function (response) {                                
                                location.href = 'viewReservations.php?date=' + date + '&user='+terapist;
                            }
                        });                                                
                    });
                    
                    $("#prehled-rezervaci").on("click", "td a[data-role='editField']", function(event) {
                        event.preventDefault();
                        
                        var td = $(this).closest("td");
                        var prevSpan = td.find("span[data-role='content']").first();
                        
                        prevSpan.parent("a").removeAttr("href");                        
                        
                        if (td.attr("data-field") !== "mailing") {                            
                            var input = $("<input>");
                            input.attr("value", prevSpan.text());
                            input.attr("class", "form-control");
                            input.attr("style", "width: 80%;");
                            input.attr("type", "text");
                        }
                        
                        prevSpan.replaceWith(input);                        
                        input.focus().select();
                    });
                    
                    $("#prehled-rezervaci").on("blur", "td input[type='text']", function() {
                        var text = $(this).val();
                        var span = $("<span data-role='content'></span>");
                        
                        span.text(text);
                        $(this).replaceWith(span);
                    });
                    
                    $("#prehled-rezervaci").on("change", "td input", function() {
                        var inputType = $(this).attr("type");
                        var td = $(this).closest("td");
                        var field = td.attr("data-field");
                        var id = $(this).closest("tr").attr("data-id");
                        var value;
                                                                        
                        if (inputType === "text") {
                            value = $(this).val();
                        } else if (inputType === "checkbox") {
                            value = ($(this).prop( "checked" ) ? 1 : 0);
                        }
                                                                  
                        //console.log(field + ": " + value);
                        
                        $.ajax({
                            "url": "reservationsEdit.php",
                            "method": "post",
                            "data": { "id": id, "field": field, "value": value },
                            "success": function(response) {
                                //console.log(response);
                                td.addClass("success");
                                setTimeout(function() { td.removeClass("success"); }, 1000);
                            }
                        });
                    });
                    
                    $("#prehled-rezervaci").on("click", "a[data-type='QRphone']", function (event) {                        
                        event.preventDefault();                                
                        var id = $(this).closest("tr").data("id");
                        var action = $(this).closest("tr").data("action");
                        
                        $.ajax({
                            url: "getQRphone.php",
                            method: "post",
                            dataType: "json",
                            data: {"id": id, "action": action},
                            success: function (response) {
                                $("#imgQRphone").attr("src", "data:image/png;base64," + response.img);
                                $("#qrPhone").html(response.phone);
                                $("#qrPhoneClient").html(response.client);                        
                                
                                $("#zobrazit-qrphone-modal").modal("show");                                                                        
                            }
                        });
                    });                                       
                    
                    $("#prehled-rezervaci").on("click", "a[data-type='freeSlots']", function(event) {						            
                    // volné termíny s datem menším než datum rezervace 
                    var tr = $(this).closest("tr");            
                    var idres = tr.attr("data-id");
                    var service = tr.attr("data-service");
                    var akce = tr.attr("data-action");

                    $("#tablefreeslots tbody tr").remove();

                    $.ajax({
                        url: "getFreeSlots.php?_=" + new Date().getTime(),
                        method: "get",
                        data: {"id" : idres, "service" : service, "dateTo" : null, "action" : akce},
                        dataType: "json",
                        success: function(response) {
                            $("#tablefreeslots tbody tr").remove();
                            if (response.length > 0) {
                                $.each(response, function(i, obj) {
                                    var tr = $("<tr></tr>");
                                    $.each(obj, function(key, value) {
                                        if (key !== "terapist" & key !== "dateformatted" & key !== "hourFrom") {
                                            var td = $("<td></td>");
                                            if (key !== "displayName") {
                                                td.css("text-align", "center");
                                            }                                           
                                            td.html(value);
                                            tr.append(td);
                                        }
                                    });
                                    $("#tablefreeslots tbody").append(tr);
                                });                        
                            } else {
                                var tr = $("<tr></tr>");
                                var td = $("<td colspan='11' class='text-center'></td>");
                                td.html("Nebyly nalezeny žádné volné termíny s datem menším než je datum zvolené rezervace");
                                tr.append(td);
                                $("#tablefreeslots tbody").append(tr);
                            }

                            var tablefreeslots;
                            if ($("#tablefreeslots td").length > 1) {                       
                                tablefreeslots = $("#tablefreeslots").DataTable({                                    
                                    "ordering": false,
                                    "language": {
                                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                                    },
                                    "responsive": false,
                                    columnDefs: [                                                                        
                                        {"width": "60%", "targets": 0},
                                        {"width": "20%", "targets": 1},
                                        {"width": "10%", "targets": 2},
                                        {"width": "7%", "targets": 3},
                                        {"width": "3%", "targets": 4},

                                    ],
                                    "fixedHeader": true,
                                    "pageLength": 25
                                });
                            };
                            $("#modal-freeSlots").modal("show");
                            },
                            beforeSend: function() {
                                var tr = $("<tr></tr>");
                                var td = $("<td colspan='11'></td>");
                                td.html("Probíhá načítání záznamů.");
                                tr.append(td);
                                $("#tablefreeslots tbody").append(tr);
                            }

                        });
                    });
                    
                    $("#prehled-rezervaci").on("click", "a[data-type='relatedRes']", function(event) {						                                
                    var tr = $(this).closest("tr");            
                    var idres = tr.attr("data-id");    
                    var datum = tr.attr("data-date"); 
                    var email = tr.attr("data-email"); 
                    var phone = tr.attr("data-phone"); 
                    var client = tr.attr("data-client"); 

                    $("#tablerealtedres tbody tr").remove();

                    $.ajax({
                        url: "getRelatedRes.php?_=" + new Date().getTime(),
                        method: "post",
                        data: {"id" : idres, "date" : datum, "email" : email, "phone" : phone, "client" : client},
                        dataType: "json",
                        success: function(response) {
                            $("#tablerelatedres tbody tr").remove();
                            if (response.length > 0) {
                                $.each(response, function(i, obj) {
                                    var tr = $("<tr></tr>");
                                    $.each(obj, function(key, value) {                                       
                                        var td = $("<td></td>");
                                        if (key !== "displayName") {
                                            td.css("text-align", "center");
                                        }                                           
                                        td.html(value);
                                        tr.append(td);                                        
                                    });
                                    $("#tablerelatedres tbody").append(tr);
                                });                        
                            } else {
                                var tr = $("<tr></tr>");
                                var td = $("<td colspan='11' class='text-center'></td>");
                                td.html("Nebyly nalezeny žádné budoucí rezervace klienta");
                                tr.append(td);
                                $("#tablerelatedres tbody").append(tr);
                            }
                           
                            $("#modal-relatedRes").modal("show");
                            },
                            beforeSend: function() {
                                var tr = $("<tr></tr>");
                                var td = $("<td colspan='11'></td>");
                                td.html("Probíhá načítání záznamů.");
                                tr.append(td);
                                $("#tablerelatedres tbody").append(tr);
                            }

                        });
                    });
                    
                    $("#tablefreeslots").on("click", "a[data-type='modalakce']", function(event) {						            
                        // všechny volné termíny na danou službu                      
                        var resid =  $(this).attr("data-id");
                        var freeslotid =  $(this).attr("data-freeslotid");                        
                        var odkaz;
                        
                        
                        if ( confirm('Přejete si klientovi odeslat e-mail s notifikací o přesunu rezervace?') ) {                            
                            odkaz = 'reservationShift.php?sendEmail=true';
                            //location.href = 'deleteReservation.php?id=' + id + '&returnTo=' + date + '&sendEmail=true';
                            //alert(odkaz);                            
                            } else {                            
                                odkaz = 'reservationShift.php';
                                //alert(odkaz);                            
                        }
                        
                        
                        $.ajax({
                            url: odkaz,
                            method: "post",
                            dataType: "text",
                            data: { "id": resid, "freeslotid": freeslotid },
                            success: function() {
                                 $("#modal-freeSlots").modal("hide");
                                 location.reload();
                            },
                            error: function(xhr, msg) {
                                    alert("nastala chyba: " + msg + " . Neprovádějte rezervace pomocí tohoto systému a kontaktujte správce.");
                            }
                        });            
                     }); 
                    
                    $("#prehled-rezervaci").on("click", "a[data-type='QRsms']", function (event) {                                               
                        event.preventDefault();                                
                        var id = $(this).closest("tr").data("id");                                        
                        
                        $.ajax({
                            url: "getQRsmsIndiv.php",
                            method: "post",
                            dataType: "json",
                            data: {"id": id,  "predefinedMessage":  $("#smsText").val(), "customText": $("#smsCustomText").val()},
                            success: function (response) {
                                $("#imgQRsms").attr("src", "data:image/png;base64," + response.img);
                                $("#qrSMSClient").html(response.client);
                                $("#qrSMSPhone").html(response.phone);
                                $("#qrSMSFinalText").html(response.smsText);                        
                                
                                $("#zobrazit-qrsms-modal").modal("show");                                                                
                            }
                        });
                    });
                                      
                    $("#prehled-rezervaci").on("click", "a[data-type='movetowl']", function (event) {                                               
                        if (confirm('Skutečně si přejete smazat zvolenou rezervaci a zapsat klienta na čekací listinu?') ) {
                            event.preventDefault();                                
                            var id = $(this).closest("tr").data("id");
                            var date = $(this).closest("tr").data("date");
                            var dateformatted =  $(this).closest("tr").data("date-formatted");


                            //zápis na waiting list
                            var client = $(this).closest("tr").data("client");
                            var name = $(this).closest("tr").data("name");
                            var surname = $(this).closest("tr").data("surname");
                            var phone = $(this).closest("tr").data("phone");
                            var email = $(this).closest("tr").data("email");
                            var service = $(this).closest("tr").data("service");
                            var terapist = <?= $selectedUser?>;
                            var note = 'Vytvořeno ze zrušené rezervace ze dne ' + dateformatted;                                                        

                            $.ajax({
                                url: "addWaitingList.php",
                                method: "post",
                                dataType: "text",
                                data: { 
                                        "terapist": terapist,
                                        "client": client,
                                        "name": name,
                                        "surname": surname,
                                        "email": email,
                                        "phone": phone,
                                        "service": service,
                                        "note": null,                                    
                                        "submit": true,
                                        "wlid": id,
                                        "note": note,
                                        "validto-formatted": '2022-12-31',
                                        "resid": id
                                      },

                                success: function() {                                 
                                    window.scroll({top: 0, left: 0});                                                                        
                                    if ( confirm('Přejete si klientovi odeslat e-mail s notifikací o zrušené rezervaci?') ) {
                                        //alert('deleteReservation.php?id=' + id + '&returnTo=' + date + '&sendEmail=true');
                                        location.href = 'deleteReservation.php?id=' + id + '&returnTo=' + date + '&sendEmail=true';
                                    } else {
                                        //alert('deleteReservation.php?id=' + id + '&returnTo=' + date);
                                        location.href = 'deleteReservation.php?id=' + id + '&returnTo=' + date;
                                    }
                                },
                                error: function(xhr, msg) {
                                        alert("nastala chyba: " + msg + " . Neprovádějte přesouvání rezervací na čekací listinu a kontaktujte správce.");
                                }
                            }); 
                        } 
                    });
                    $("#buttonReservationsMonth").on("click", function(event) {                                                                                                        
                        var date = $("input[name='dateFormatted']").val();                          
                        location.href = 'viewReservationsMonth.php?date=' + date;
                    }); 
                    $("#buttonReservationsWeek").on("click", function(event) {                                                                                                        
                        var date = $("input[name='dateFormatted']").val();  
                        var terapist = $("select[name='terapist']").val();                          
                        location.href = 'viewReservationsWeek.php?date=' + date + '&user='+terapist;
                    }); 
                    $("#buttonReservationsErgoAll").on("click", function(event) {                                                                                                        
                        var date = $("input[name='dateFormatted']").val();
                        location.href = 'viewReservationsAllTherapistsERGO.php?date=' + date;
                    });
                    $("#buttonReservationsAll").on("click", function(event) {                                                                                                        
                        var date = $("input[name='dateFormatted']").val();
                        location.href = 'viewReservationsAllTherapists.php?date=' + date;
                    });
                });
            </script>
        </div>
    </body>
</html>