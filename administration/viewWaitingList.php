<?php
$pageTitle = "FL - čekací listina";

require_once "checkLogin.php";
require_once "../header.php";

$alsoPassed = isset($_GET["alsoPassed"]) ? true : false;
if ($alsoPassed) {
    $dateFrom = '1970-01-01';
} else {
    $dateFrom = (new DateTime())->format("Y-m-d");
}

// rezervace, které mají příznak o zájmu klienta o dřívější termín
$query = "  SELECT
                r.id,
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                r.date,
                SUBSTRING(DAYNAME(r.date),1,3) as denTydne,
                CAST(CONCAT(r.hour, ':', r.minute) as time) as hour,
                r.minute,
                s.name AS service,
                r.service as serviceId,
                r.note COLLATE utf8_general_ci as note,
                SUBSTRING(r.internalNote, 1, 200) COLLATE utf8_general_ci as internalNote,
                IFNULL((SELECT 
                    CASE 
                        WHEN fyzBezLehatka = 'Y' THEN 'ANO'
                        WHEN fyzBezLehatka = 'N' THEN 'NE'
                        ELSE '-'
                    END
                 FROM clients c
                    
                 WHERE
                        r.email = c.email OR
                        r.phone = c.phone OR                        
                        r.client = c.id
                 LIMIT 1
                ),'-') as fyzBezLehatka,
                a.displayName,                
                r.personnel,
                r.source,                
                r.creationTimestamp
                
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE 
                r.active = 1 AND
                r.date >= :dateFrom AND
                r.sooner = 1
            ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $dateFrom, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="container-fluid" id="administrace-rezervaci">    
    <?php include "menu.php" ?>

    <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            Přehled všech rezervací se zájmem o dřívější termín            
        </h2>                        
        
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-lg-1 col-lg-offset-11">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassed" <?= $alsoPassed ? "checked" : "" ?>> I uplynulé
                    </label>
                </div>
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
        
        <!-- tabulka s přehledem rezervací s příznakem zájmu klienta o dřívější termín -->
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-hover table-striped" id="prehled-allSoonerReservations" style="horizontal-align: middle">
                    <thead>
                        <tr>                                                        
                            <th data-priority="1" class="text-center" style="vertical-align: middle;">Datum</th>
                            <th data-priority="5" class="text-center" style="vertical-align: middle;">Den</th>                            
                            <th data-priority="6" class="text-center" style="vertical-align: middle;">Hod</th>                            
                            <th data-priority="2" class="text-center" style="vertical-align: middle;">Jméno</th>
                            <th data-priority="3" class="text-center" style="vertical-align: middle;">Příjmení</th>
                            <th data-priority="7" class="text-center" style="vertical-align: middle;">Pracovník</th>
                            <th data-priority="8" class="text-center" style="vertical-align: middle;">Fyzio bez lehátka</th>
                            <th data-priority="9" class="text-center" style="vertical-align: middle;">Služba</th>
                            <th data-priority="10" class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th data-priority="11" class="text-center" style="vertical-align: middle;">Email</th>
                            <th data-priority="12" class="text-center" style="vertical-align: middle;">Poznámka</th>
                            <th data-priority="13" class="text-center" style="vertical-align: middle;">Interní poznámka</th>
                            <th data-priority="14" class="text-center" style="vertical-align: middle;">Zdroj</th>   
                            <th data-priority="15" class="text-center" style="vertical-align: middle;">Vytvořeno</th>
                            <th data-priority="4" class="text-center" style="vertical-align: middle;">Akce</th>                            
                            <th data-priority="16" class="text-center" style="vertical-align: middle;">ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $result): ?>
                                <tr data-id="<?= $result->id ?>" data-date="<?= $result->date ?>" data-terapist="<?= $result->personnel ?>"  data-service="<?= $result->serviceId ?>" data-dateto="<?= $result->date ?>" data-action="RES">
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j. n. Y") ?></td>                                    
                                    <td class="text-center"><?= $result->denTydne ?></td>
                                    <td class="text-left"><?= $result->hour ?></td>
                                    <td class="text-left"><b><?= $result->name ?></b></td>
                                    <td class="text-left"><b><?= $result->surname ?></b></td>
                                    <td class="text-left"><?= $result->displayName ?></td>   
                                    <td class="text-center"><?= $result->fyzBezLehatka ?></td>   
                                    <td class="text-left"><?= $result->service ?></td>                                    
                                    <td class="text-left"><?= $result->phone ?>
                                        <?php if ( !empty($result->phone) ): ?>  
                                            <a href="#" data-type="QRphone" title="Ukázat QR kód pro telefonní číslo">
                                            <span class="glyphicon glyphicon-phone"></span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-left"><?= $result->email ?></td>
                                    <td class="text-left"><?= $result->note ?></td>
                                    <td class="text-left"><?= $result->internalNote ?></td>
                                    <td class="text-left"><?= $result->source ?></td>
                                    <td class="text-left"><?= $result->creationTimestamp ?></td>                                    
                                    <td class="text-center">
                                        <a href="#" data-type="viewReservation">
                                        <span class="glyphicon glyphicon-step-backward" title="Zobrazit rezervaci terapeuta na celodenním přehledu"></span></a>
                                        <a href="#" data-type="freeSlots">
                                        <span class="glyphicon glyphicon-arrow-right" title="Zobrazit volné sloty pro přesun rezervace na jiný termín"></span></a>
                                    </td>
                                    <td class="text-left"><?= $result->id ?></td>                                    
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="15" class="text-center">Nenalezeny žádné rezervace se zájmem klienta o dřívější termín.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>        
    </div> 
    
    <!-- tabulka s přehledem klientů, kteří čekají na rezervaci -->
    <?php
    // waiting list požadavků, které ještě nebyly vyřešeny
        $query = "  SELECT
                wl.id,
                wl.client,
                AES_DECRYPT(wl.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(wl.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(wl.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(wl.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                wl.date,                                                
                s.name AS service,
                wl.service as serviceId,
                wl.note COLLATE utf8_general_ci as note,                
                a.displayName,
                wl.validto,
                wl.personnel,
                wl.solved
                
            FROM waitinglist AS wl
            LEFT JOIN services AS s ON s.id = wl.service
            LEFT JOIN adminLogin AS a ON a.id = wl.personnel
            WHERE                 
                wl.validto >= :dateFrom AND
                wl.solved = 0
            ORDER BY wl.date DESC, wl.id DESC
            ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $dateFrom, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
    
    <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            Přehled klientů na čekací listě pro rezervaci
        </h2>                        
    
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12 text-center">
                <div class="form-group">
                    <button class="btn btn-primary" type="button" id="addwaitinglist" data-toggle="modal" data-target="#zadat-waitinglist-modal">Přidat klienta na čekací listinu</button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-hover table-striped" id="waitingList_2" style="horizontal-align: middle">
                    <thead>
                        <tr>                                                        
                            <th class="text-center" style="vertical-align: middle;">Zadáno</th>                                                
                            <th class="text-center" style="vertical-align: middle;">Jméno</th>
                            <th class="text-center" style="vertical-align: middle;">Příjmení</th>
                            <th class="text-center" style="vertical-align: middle;">Terapeut</th>
                            <th class="text-center" style="vertical-align: middle;">Služba</th>
                            <th class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th class="text-center" style="vertical-align: middle;">Email</th>
                            <th class="text-center" style="vertical-align: middle;">Poznámka</th>
                            <th class="text-center" style="vertical-align: middle;">Vyřešeno</th>
                            <th class="text-center" style="vertical-align: middle;">Platnost do</th>
                            <th class="text-center" style="vertical-align: middle;">Akce</th>
                            <th class="text-center" style="vertical-align: middle;">ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $result): ?>
                                <tr data-id="<?= $result->id ?>" data-client = "<?= $result->client ?>" data-name = "<?= $result->name ?>" data-surname = "<?= $result->surname ?>" data-phone = "<?= $result->phone ?>" data-email= "<?= $result->email ?>" data-service = "<?= $result->serviceId ?>" data-person = "<?= $result->personnel ?>" data-action="WL">
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j. n. Y") ?></td>                                                                        
                                    <td data-field="name">
                                        <span data-role="content"><b><?= nl2br(htmlentities($result->name)) ?></b></span>                                                                        
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>
                                    <td data-field="surname">
                                        <span data-role="content"><b><?= nl2br(htmlentities($result->surname)) ?></b></span>
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>
                                    <td class="text-left"><?= $result->displayName ?></td>
                                    <td class="text-left"><?= $result->service ?></td>                                    
                                    
                                    <td data-field="phone">
                                        <a href="tel:<?= htmlentities($result->phone) ?>"><span data-role="content"><?= htmlentities($result->phone) ?></span></a>                                                                         
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                        <?php if ( !empty($result->phone) ): ?>  
                                        <a href="#" data-type="QRphone" title="Ukázat QR kód pro telefonní číslo">
                                        <span class="glyphicon glyphicon-phone"></span>
                                        </a>
                                    <?php endif; ?>
                                    </td>
                                    <td data-field="email">
                                        <span data-role="content"><?= htmlentities($result->email) ?></span></a> 
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>
                                    <td data-field="note">
                                        <span data-role="content"><?= nl2br(htmlentities($result->note)) ?></span>
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>
                                    <td class="text-center" style="vertical-align: middle;"><input type="checkbox" name="solved" <?= $result->solved === "1" ? "checked" : "" ?>></td>
                                    
                                    <td data-field="validto">
                                        <span data-role="content"> <?= (new DateTime($result->validto))->format("j. n. Y") ?></span>
                                        <a href="#" style="float: right;" data-role="editField"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>
                                    
                                    <td class="text-center">    
                                        <a href="#" data-type="freeSlots">
                                        <span class="glyphicon glyphicon-arrow-right" title="Zobrazit volné sloty pro rezervaci"></span></a>
                                    </td>
                                    <td class="text-left"><?= $result->id ?></td>                                    
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="11" class="text-center">Nenalezeni žádní klienti na čekací listině.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- tabulka s přehledem klientů, kteří čekali na rezervaci a uplynul termín platnosti požadavku-->
    <?php
        $query = "  SELECT
                wl.id,
                AES_DECRYPT(wl.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(wl.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(wl.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(wl.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                wl.date,                                                
                s.name AS service,
                wl.note COLLATE utf8_general_ci as note,                
                a.displayName,
                wl.validto,
                wl.personnel,
                
                wl.solved
                
            FROM waitinglist AS wl
            LEFT JOIN services AS s ON s.id = wl.service
            LEFT JOIN adminLogin AS a ON a.id = wl.personnel
            WHERE                 
                wl.validto < :dateFrom AND
                wl.solved = 0
            ORDER BY validto DESC
            ";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $dateFrom, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
    
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12 text-center">
            <div class="form-group">
                <button class="btn btn-primary" type="button" id="addwaitinglistNoValid">Zobrazit nesplněné požadavky po termínu platnosti</button>
            </div>
        </div>
    </div>
    
    <div class="col-lg-10 col-lg-offset-1" data-novalid ="true">
        <h2 class="text-center">
            Přehled klientů na čekací listě pro rezervaci, kterým prošla platnost požadavku a nedošlo k řešení
        </h2>                                    

        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-hover table-striped" id="waitingListNoValid" style="horizontal-align: middle">
                    <thead>
                        <tr>                                                        
                            <th class="text-center" style="vertical-align: middle;">Zadáno</th>                                                
                            <th class="text-center" style="vertical-align: middle;">Jméno</th>
                            <th class="text-center" style="vertical-align: middle;">Příjmení</th>
                            <th class="text-center" style="vertical-align: middle;">Terapeut</th>
                            <th class="text-center" style="vertical-align: middle;">Služba</th>
                            <th class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th class="text-center" style="vertical-align: middle;">Email</th>
                            <th class="text-center" style="vertical-align: middle;">Poznámka</th>
                            <th class="text-center" style="vertical-align: middle;">Vyřešeno</th>
                            <th class="text-center" style="vertical-align: middle;">Platnost do</th>                            
                            <th class="text-center" style="vertical-align: middle;">ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $result): ?>
                                <tr data-id="<?= $result->id ?>">                                    
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j. n. Y") ?></td>
                                    <td class="text-center"><?= $result->name ?></td>
                                    <td class="text-center"><?= $result->surname ?></td>
                                    <td class="text-center"><?= $result->displayName ?></td>
                                    <td class="text-left"><?= $result->displayName ?></td>
                                    <td class="text-left"><?= $result->service ?></td>                                    
                                    <td class="text-left"><?= $result->phone ?></td>   
                                    <td class="text-left"><?= $result->email ?></td>                                    
                                    <td class="text-left"><?= nl2br(htmlentities($result->note)) ?></td>
                                    <td class="text-center" style="vertical-align: middle;"><input type="checkbox" name="solved" <?= $result->solved === "1" ? "checked" : "" ?>></td>
                                    <td class="text-center" data-order="<?= $result->validto ?>"><?= (new DateTime($result->validto))->format("j. n. Y") ?></td>                                    
                                    <td class="text-left"><?= $result->id ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="11" class="text-center">Nenalezeny žádní klienti na čekací listině.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- kompletní seznam klientů z databáze pro modální dialog nového požadavku na čekací listinu -->
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

<!-- modální dialog pro zadání požadavku na čekací listinu -->
<div class="modal fade" tabindex="-1" role="dialog" id="zadat-waitinglist-modal">
    <div class="modal-dialog" role="document">
        <form action="addWaitingList" method="post" id="waitingListForm">
            <input type="hidden" name="waitingListId" id="waitingListId" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="myModalLabel">Zadání nového požadavku na čekací listinu</h3>
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
                                <label for="name">Jméno: </label>
                                <input class="form-control" type="text" name="name" id="name" value="">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="surname">Příjmení: </label>
                                <input class="form-control" type="text" name="surname" id="surname" value="">
                            </div>
                        </div>
                    </div>
                    <div class="row" style="padding-top: 0px; padding-bottom: 0px;">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="email">E-mailová adresa: </label>
                                <input class="form-control" type="email" name="email" id="email" value="">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Telefonní číslo: </label>
                                <input class="form-control" type="tel" name="phone" id="phone" value="">
                            </div>
                        </div>                        
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="terapist">Preferovaný terapeut: </label>
                                <select class="form-control" name="terapist" id="terapist">
                                    <?php
                                        $query = "  SELECT
                                                        al.id,
                                                        al.displayName
                                                    FROM adminLogin AS al
                                                    
                                                    WHERE al.active= 1 AND al.indiv =1
                                                    ORDER BY al.displayName";
                                        $stmt = $dbh->prepare($query);                                        
                                        $stmt->execute();
                                        $resultsUsers = $stmt->fetchAll(PDO::FETCH_OBJ);
                                    ?>
                                    <?php foreach ($resultsUsers as $resultsUser): ?>
                                    <option value="<?= $resultsUser->id ?>"><?= $resultsUser->displayName ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
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
                                                    GROUP BY s.name
                                                    ORDER BY s.name";
                                        $stmt = $dbh->prepare($query);                                        
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
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="validto">Plastnost požadavku do</label>
                                <input type="text" class="form-control" id="validto" name="validto" value="" placeholder="" required>
                                <input type="hidden" id="validto-formatted" name="validto-formatted" value="">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="note">Indikativní popis obtíží (interní poznámka): </label>
                                <textarea class="form-control" name="note" id="note"></textarea>
                            </div>
                        </div>
                    </div>
                                        
                    <input type="hidden" id="client" name="client" value="">                    
                </div>   
		
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                    <button type="submit" class="btn btn-primary" name="submit">Uložit</button>                    
                </div>
            </div>
        </form>
    </div>
</div>

<!-- modální dialog pro volné termíny-->
<div class="modal fade" tabindex="-1" role="dialog" id="modal-freeSlots">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Přehled volných termínů</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-freeslots-client">
                <input type="hidden" id="modal-freeslots-name">
                <input type="hidden" id="modal-freeslots-surname">
                <input type="hidden" id="modal-freeslots-phone">
                <input type="hidden" id="modal-freeslots-email">
                <input type="hidden" id="modal-freeslots-service">
                <input type="hidden" id="modal-freeslots-person">
                
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

<script>
    $(document).ready(function () {    
        var tablefreeslots;
        $("div[data-novalid='true']").hide();
        
        $.fn.modal.Constructor.prototype.enforceFocus = function () {};
        
        
        
        $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();        
        
        $("#addwaitinglist").click(function() {
            //checkLogin();			           
            
            $("#waitingListId").val("");
            $("#name").val("");
            $("#surname").val("");
            $("#phone").val("");
            $("#email").val("");
            $("#validto").datepicker("setDate", null);            
            $("#terapist").val("").trigger("change");
            $("#service").val("").trigger("change");
            $("#note").val("");
        });	
        
        var prehledAllSonnerReservations;
        if ($("#prehled-allSoonerReservations td").length > 1) {
            prehledAllSonnerReservations = $("#prehled-allSoonerReservations").DataTable({
                "ordering": false,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                },
                "responsive": true,
                columnDefs: [                                                                        
                    {"width": "6%", "targets": 0},
                    {"width": "3%", "targets": 1},
                    {"width": "4%", "targets": 2},
                    {"width": "5%", "targets": 3},
                    {"width": "5%", "targets": 4},
                    {"width": "5%", "targets": 5},
                    {"width": "5%", "targets": 6},
                    {"width": "5%", "targets": 7},
                    {"width": "6%", "targets": 8},
                    {"width": "6%", "targets": 9},
                    {"width": "4%", "targets": 10},
                    {"width": "7%", "targets": 11},
                    {"width": "6%", "targets": 12},
                    {"width": "5%", "targets": 13},
                    {"width": "3%", "targets": 14},
                    {"width": "3%", "targets": 15},
                ],
                "fixedHeader": false,
                "pageLength": 10
            });
        };
        
        var waitingList;
        if ($("#waitingList_2 td").length > 1) {
            waitingList = $("#waitingList_2").DataTable({
                "order": [[0, "desc"]],
                "ordering": false,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                },
                "responsive": true,                
                "fixedHeader": false,
                "pageLength": 50
            });
        };                
        
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
            dropdownParent: $("#zadat-waitinglist-modal")
        });                
       
        $('#search').parent().find('.select2-container').css('width', '');
        $('#search').parent().find('.select2-search__field').attr("size", "100%").css('width', '100%');
        
        $('#zadat-waitinglist-modal').on('show.bs.modal', function (event) {            			
            //checkLogin();
            var button = $(event.relatedTarget);
            button.one('focus', function (event) {
                $(this).blur();
            });
            //$("#search").select2('open');            
        });
        
        $("#search").on('select2:select', function (e) {
            element = $(e.params.data.element);

            $("#zadat-waitinglist-modal #name").val(element.attr("data-name"));
            $("#zadat-waitinglist-modal #client").val(element.attr("data-id"));
            $("#zadat-waitinglist-modal #surname").val(element.attr("data-surname"));
            $("#zadat-waitinglist-modal #phone").val(element.attr("data-phone"));
            $("#zadat-waitinglist-modal #email").val(element.attr("data-email"));
        });

        $("#search").on('select2:unselect', function (e) {
            $("#zadat-waitinglist-modal #name").val("");
            $("#zadat-waitinglist-modal #client").val("");
            $("#zadat-waitinglist-modal #surname").val("");
            $("#zadat-waitinglist-modal #phone").val("");
            $("#zadat-waitinglist-modal #email").val("");
        });

        $("body").on("click", "#notFound", function() {
            $("#zadat-waitinglist-modal #name").val("");
            $("#zadat-waitinglist-modal #client").val("");
            $("#zadat-waitinglist-modal #surname").val("");
            $("#zadat-waitinglist-modal #phone").val("");
            $("#zadat-waitinglist-modal #email").val("");

            $("#search").select2("close");
            $("#name").focus();
        });

        $("#zadat-waitinglist-modal button[name='submit']").click(function(event) {
            if ($("#zadat-waitinglist-modal #client").val() === "") {
                if( !confirm('Zadáváte klienta, který bude ve Fyziolandu poprvé, jste si jisti, že chcete pokračovat?\n\nPokud se jedná o klienta, který již u nás byl, najděte ho prosím pomocí vyhledávacího pole a poté teprve založte rezervaci.')) {
                    event.preventDefault();
                }
            }
        });
                    
        $("#zadat-waitinglist-modal input[name='phone']").mask("+420 999 999 999");        
        $("#validto").datepicker({
            altField: "#validto-formatted",
            altFormat: "yy-mm-dd",
            dayNames: ["neděle", "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota"],
            dayNamesMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
            firstDay: 1,
            dateFormat: "d. m. yy",
            monthNames: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"]
        });               
        
        $("#waitingList_2").on("change", "input[name='solved']", function() {
            var val = $(this).prop("checked") ? 1 : 0;
            var id = $(this).closest("tr").attr("data-id");
            var td = $(this).closest("td");

            $.ajax({
                url: "setSolved.php",
                method: "post",
                dataType: "text",
                data: { "solved": val, "id": id },
                success: function() {
                    var span = $("<br><span class='label label-success'>Uloženo</span>");
                    td.append(span);
                    span.fadeOut(1500, function(){ $(this).remove(); });
                    location.reload();
                }
            });             
        });                
        
        $("#waitingList_2").on("click", "td a[data-role='editField']", function(event) {           
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
        
        $("#waitingList_2").on("blur", "td input[type='text']", function() {
            var text = $(this).val();
            var span = $("<span data-role='content'></span>");

            span.text(text);
            $(this).replaceWith(span);
        });

        $("#waitingList_2").on("change", "td input", function() {           
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

            $.ajax({
                "url": "waitinglistEdit.php",
                "method": "post",
                "data": { "id": id, "field": field, "value": value },
                "success": function(response) {
                    //console.log(response);
                    td.addClass("success");
                    setTimeout(function() { td.removeClass("success"); }, 1000);
                }
            });           
        });

        $("#prehled-allSoonerReservations").on("click", "a[data-type='viewReservation']", function(event) {						            
            var tr = $(this).closest("tr");            
            var terapist = tr.attr("data-terapist");
            var date = tr.attr("data-date");            
            location.href = 'viewReservations.php?date=' + date + '&user='+terapist;
        });
        
        $("#prehled-allSoonerReservations").on("click", "a[data-type='freeSlots']", function(event) {						            
            // volné termíny s datem menším než datum rezervace
            var tr = $(this).closest("tr");            
            var idres = tr.attr("data-id");
            var service = tr.attr("data-service");
            var dateTo = tr.attr("data-dateto");
            var akce = tr.attr("data-action");
                        
            $.ajax({
                url: "getFreeSlots.php?_=" + new Date().getTime(),
                method: "get",
                data: {"id" : idres, "service" : service, "dateTo" : dateTo, "action" : akce},
                dataType: "json",
                success: function(response) {
                    if (tablefreeslots) {
                        tablefreeslots.destroy();
                        tablefreeslots = undefined;
                    }
                    
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
                        var td = $("<td colspan='5' class='text-center'></td>");
                        td.html("Nebyly nalezeny žádné volné termíny s datem menším než je datum zvolené rezervace");
                        tr.append(td);
                        $("#tablefreeslots tbody").append(tr);
                    }
                    
                    if (response.length > 0) {
                    
                        tablefreeslots = $("#tablefreeslots").DataTable({
                            "ordering": false,
                            "language": {
                                "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                            },
                            "responsive": false,
                            columnDefs: [                                                                        
                                {"width": "60%", "targets": 0},
                                {"width": "10%", "targets": 1},
                                {"width": "10%", "targets": 2},
                                {"width": "10%", "targets": 3},
                                {"width": "10%", "targets": 4},

                            ],
                            "fixedHeader": true,
                            "pageLength": 50
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
        
        $("#waitingList_2").on("click", "a[data-type='freeSlots']", function(event) {						            
            // všechny volné termíny
            var tr = $(this).closest("tr");            
            var idwl = tr.attr("data-id");
            var client = tr.attr("data-client");
            var name = tr.attr("data-name");
            var surname = tr.attr("data-surname");
            var phone = tr.attr("data-phone");
            var email = tr.attr("data-email");
            var person = tr.attr("data-person");            
            var service = tr.attr("data-service");           
            var akce = tr.attr("data-action");
                                       
            $("#modal-freeslots-client").val(client);
            $("#modal-freeslots-name").val(name);
            $("#modal-freeslots-surname").val(surname);
            $("#modal-freeslots-phone").val(phone);
            $("#modal-freeslots-email").val(email);            
            $("#modal-freeslots-person").val(person);
            $("#modal-freeslots-service").val(service);                        
            
                       
            $.ajax({
                url: "getFreeSlots.php?_=" + new Date().getTime(),
                method: "get",
                data: {"id" : idwl, "service" : service, "dateTo" : null, "action" : akce},
                dataType: "json",
                success: function(response) {
                    if (tablefreeslots) {
                        tablefreeslots.destroy();
                        tablefreeslots = undefined;
                    }
                        
                    $("#tablefreeslots tbody tr").remove();
                    if (response.length > 0) {
                        $.each(response, function(i, obj) {                                                    
                            var tr = $('<tr data-freeslots-hour= "'+ response[i].hourFrom + '" data-freeslots-date = "'+ response[i].dateformatted + '" data-freeslots-terapist = "'+ response[i].terapist + '"></tr>');
                            
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
                        var td = $("<td colspan='5' class='text-center'></td>");
                        td.html("Nebyly nalezeny žádné volné termíny s datem menším než je datum zvolené rezervace");
                        tr.append(td);
                        $("#tablefreeslots tbody").append(tr);
                    }
                    
                    if (response.length > 0) { 
                        
                        tablefreeslots = $("#tablefreeslots").DataTable({                            
                            "ordering": false,
                            "language": {
                                "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                            },
                            "responsive": false,                            
                            "fixedHeader": true,
                            "pageLength": 50
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

        $("#tablefreeslots").on("click", "a[data-type='modalakce']", function(event) {						            
            // volné termíny s datem menším než datum rezervace                                    
            var akce =  $(this).attr("data-action");                                                      
            
            if (akce == 'RES') {
                var id =  $(this).attr("data-id");
                var freeslotid =  $(this).attr("data-freeslotid");
                $.ajax({
                    url: "reservationShift.php",
                    method: "post",
                    dataType: "text",
                    data: { "id": id, "freeslotid": freeslotid },
                    success: function() {
                         $("#modal-freeSlots").modal("hide");
                         location.reload();
                    },
                    error: function(xhr, msg) {
                            alert("nastala chyba: " + msg + " . Neprovádějte rezervace pomocí tohoto systému a kontaktujte správce.");
                    }
                });
            }
            if (akce == 'WL') {              
                //jdeme udělat rezervaci klienta z waiting listu
                //info o klientovi a službě uložené v hidden proměnných na modálním dialogu (zapsáno při kliku na freeslots z tabulky waitinglist)
                var client = $("#modal-freeslots-client").val();
                var name = $("#modal-freeslots-name").val();
                var surname = $("#modal-freeslots-surname").val();
                var phone = $("#modal-freeslots-phone").val();
                var email = $("#modal-freeslots-email").val();
                var person = $("#modal-freeslots-person").val();
                var service = $("#modal-freeslots-service").val();
                
                //info o času datu rezervace uložené na tabulce volných slotů (na každém řádku) na modálním dialogu
                var tr = $(this).closest("tr");                
                var hour = tr.attr("data-freeslots-hour");
                var date = tr.attr("data-freeslots-date");
                // terapeut vybraný na modálním okně s volnými sloty (nahoře je proměnná person, kterou zatím nevyužívám - je to preferovaný terapeut na tabulce waitinglist
                var terapist = tr.attr("data-freeslots-terapist");                
                
                // id položky WL je uvedeno na ikoně se spuštěním rezervace
                var wlid =  $(this).attr("data-id");
                
                
                $.ajax({
                    url: "rezervaceAction.php",
                    method: "post",
                    dataType: "text",
                    data: { "date": date,
                            "hour": hour,
                            "minute": '0',
                            "person": terapist,
                            "client": client,
                            "name" : name,
                            "surname" : surname,
                            "email": email,
                            "phone": phone,
                            "service": service,
                            "note": null,
                            "alert-type": 'email',
                            "personalDetailsAgreement": '1',
                            "send_email": '1',
                            "submit": true,
                            "wlid": wlid
                          },
            
                    success: function() {
                         $("#modal-freeSlots").modal("hide");
                         location.reload();
                         //nastavit hodnotu vyřešeno - dodělat

                    },
                    error: function(xhr, msg) {
                            alert("nastala chyba: " + msg + " . Neprovádějte rezervace pomocí tohoto systému a kontaktujte správce.");
                    }
                });               
            }
            
         });  

        $("#alsoPassed").change(function() {
            if ($(this).is(":checked")) {
                document.location = "viewWaitingList?alsoPassed";
            } else {
                document.location = "viewWaitingList";
            }
        });       
        
        $("#addwaitinglistNoValid").click(function() {            
            $("div[data-novalid='true']").show();	
            
            if ($("#waitingListNoValid td").length > 1) {
                waitingListNoValid = $("#waitingListNoValid").DataTable({
                    "order": [[1, "desc"]],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                    },
                    "responsive": true,
                    columnDefs: [                                                                        
                        {"width": "7%", "targets": 0},
                        {"width": "7%", "targets": 1},
                        {"width": "8%", "targets": 2},
                        {"width": "5%", "targets": 3},
                        {"width": "5%", "targets": 4},
                        {"width": "10%", "targets": 5},
                        {"width": "10%", "targets": 6},
                        {"width": "23%", "targets": 7},
                        {"width": "5%", "targets": 8},
                        {"width": "10%", "targets": 9},
                        {"width": "10%", "targets": 10},                                    
                    ],
                    "fixedHeader": false,
                    "pageLength": 50
                });
            };                              
        });
        
        $("#waitingList_2").on("click", "a[data-type='QRphone']", function (event) {                                    
            event.preventDefault();                                
            var id = $(this).closest("tr").data("id");
            var akce = $(this).closest("tr").data("action");             
             
            $.ajax({
                url: "getQRphone.php",
                method: "post",
                dataType: "json",
                data: {"id": id, "action" : akce},
                success: function (response) {
                    $("#imgQRphone").attr("src", "data:image/png;base64," + response.img);
                    $("#qrPhone").html(response.phone);
                    $("#qrPhoneClient").html(response.client);                        

                    $("#zobrazit-qrphone-modal").modal("show");                                                                        
                }
            });
        });
        
        $("#prehled-allSoonerReservations").on("click", "a[data-type='QRphone']", function (event) {                                    
            event.preventDefault();                                
            var id = $(this).closest("tr").data("id");
            var akce = $(this).closest("tr").data("action");
           

            $.ajax({
                url: "getQRphone.php",
                method: "post",
                dataType: "json",
                data: {"id": id, "action" : akce},
                success: function (response) {
                    $("#imgQRphone").attr("src", "data:image/png;base64," + response.img);
                    $("#qrPhone").html(response.phone);
                    $("#qrPhoneClient").html(response.client);                        

                    $("#zobrazit-qrphone-modal").modal("show");                                                                        
                }
            });
        });
        
        $.widget.bridge("tlp", $.ui.tooltip); // ošetření konfliktu s widgetem tooltip() v Bootstrap, používáme ten z jQuery UI
    });
</script>
</body>
</html>