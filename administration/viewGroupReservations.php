<?php
$pageTitle = "FL - SKUP cvičení";

require_once "checkLogin.php";
require_once "../header.php";


$today = new DateTime();
$days = array(1 => "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle");

$alsoPassedAll = isset($_GET["alsoPassedAll"]) ? true : false;
$alsoPassed6D = isset($_GET["alsoPassed6D"]) ? true : false;
$alsoPassed3M = isset($_GET["alsoPassed3M"]) ? true : false;
$alsoPassed6M = isset($_GET["alsoPassed6M"]) ? true : false;
if ($alsoPassedAll) {
    $dateFrom = '1970-01-01';
} else {
    if ($alsoPassed6D) {
        $date = date("Y-m-d"); 
        $dateFrom =  date("Y-m-d", strtotime("$date - 6day"));
    } else {
        if ($alsoPassed3M) {
            $date = date("Y-m-d"); 
            $dateFrom =  date("Y-m-d", strtotime("$date - 3month"));
        } else if ($alsoPassed6M) {
            $date = date("Y-m-d"); 
            $dateFrom =  date("Y-m-d", strtotime("$date - 6month"));                            
        } else {
            $dateFrom = (new DateTime())->format("Y-m-d");                
        }
    }
}


$query = "  SELECT
                ge.id,
                ge.title,
                ge.date,
                SUBSTRING(DAYNAME(ge.date),1,3) as denTydne,
                ge.timeFrom,
                ge.timeTo,
                a.displayName AS instructor,
                ge.price,
                ge.capacity,
                (SELECT COUNT(groupExcercisesParticipants.id) FROM groupExcercisesParticipants LEFT JOIN attendance ON attendance.client = groupExcercisesParticipants.client AND attendance.ge =  groupExcercisesParticipants.groupExcercise WHERE groupExcercisesParticipants.groupExcercise = ge.id AND attendance.nechodi IS NULL AND attendance.omluva IS NULL) AS occupancy,
                CONCAT(SUBSTRING(ge.description,1,110), '...') as description,
                fn_groupExcercises_getUnderoccupancyEmailTime(ge.date, ge.timeFrom) AS alertTime,
                fn_groupExcercises_getLogoutDeadline(ge.date, ge.timeFrom) AS logoutDeadline,
                (SELECT CASE WHEN ge.date >= CURDATE() THEN DATEDIFF(ge.date, CURDATE()) ELSE 9999 END) AS orderNumber,
                ge.canceled,
                IFNULL(ge.cash,0) as cash,
                IFNULL(ge.qr,0) as qr,
                IFNULL(ge.benefit,0) as benefit,
                IFNULL(ge.free,0) as free,
                ge.semestralcourse,
                ge.lectorNote,
                ge.fixedInvoiceConfirmed,
                (SELECT COUNT(id) FROM visits v WHERE v.ge = ge.id) as pocetPredplatne,
                IF(ge.cash>0 OR 
                   ge.QR>0 OR 
                   ge.Benefit>0 OR 
                   ge.free>0 OR 
                   (SELECT COUNT(id) FROM visits v WHERE v.ge = ge.id) >0 OR 
                   (SELECT COUNT(id) FROM attendance WHERE attendance.ge = ge.id AND attendance.ucast IS NOT NULL)>0 OR 
                   ge.canceled = 1 OR
                   ge.fixedInvoiceConfirmed = 1 OR
                   ge.recorded = 1,
                   1, 0) as isrecorded,
                ge.fixedInvoice
            FROM groupExcercises AS ge
            LEFT JOIN adminLogin AS a ON a.id = ge.instructor
            WHERE
                (:person1 IS NULL OR ge.instructor = :person2) AND
                ge.date >= :dateFrom
            ORDER BY ge.date DESC, ge.timeFrom";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":person1", intval($resultAdminUser->isSuperAdmin) === 1 ? NULL : $resultAdminUser->id, PDO::PARAM_INT);
$stmt->bindValue(":person2", intval($resultAdminUser->isSuperAdmin) === 1 ? NULL : $resultAdminUser->id, PDO::PARAM_INT);
$stmt->bindValue(":dateFrom", $dateFrom, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
?>


<div class="container-fluid" id="administrace-rezervaci">
    
    <?php include "menu.php" ?>

    <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            Přehled rezervací na skupinová cvičení
        </h2>
        
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-lg-1 col-lg-offset-8 ">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassedAll" <?= $alsoPassedAll ? "checked" : "" ?>> Zpětně VŠE
                    </label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassed6M" <?= $alsoPassed6M ? "checked" : "" ?>> Zpětně -6M
                    </label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassed3M" <?= $alsoPassed3M ? "checked" : "" ?>> Zpětně -3M
                    </label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassed6D" <?= $alsoPassed6D ? "checked" : "" ?>> Zpětně -6D
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

        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-hover table-striped" id="prehled-rezervaci" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="text-center" style="vertical-align: middle;">Datum</th>
                            <th class="text-center" style="vertical-align: middle;">Den</th>
                            <th class="text-center" style="vertical-align: middle;">Od</th>
                            <th class="text-center" style="vertical-align: middle;">Do</th>
                            <th style="vertical-align: middle;">Název</th> 
                            <th style="vertical-align: middle;">Instruktor</th> 
                            <th class="text-center" style="vertical-align: middle;">Rezervace</th>
                            <th class="text-center" style="vertical-align: middle;">Zrušeno</th>                            
                            <th class="text-center" style="vertical-align: middle;">QR</th> 
                            <th class="text-center" style="vertical-align: middle;">Záznam lektora</th>
                            <th class="text-center" style="vertical-align: middle;">Check</th> 
                            <th class="text-center" style="vertical-align: middle;" title="Termín, do kdy se klienti mohou sami odhlašovat v rezervačním systému">Odhlášení do</th>
                            <th class="text-center" style="vertical-align: middle;" title="Čas, kdy přijde na e-mail instruktora informace o případné nenaplněnosti skupinového cvičení.">Alert (nenaplněno)</th>
                            <th class="text-center" style="vertical-align: middle;">ID</th>
                            
                            <th>Helper</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $result): ?>                                                                                                                                                 
                                <tr data-id="<?= $result->id ?>" data-id="<?= $result->id ?>"data-cash-count="<?= $result->cash ?>" data-qr-count="<?= $result->qr ?>" data-benefit-count="<?= $result->benefit ?>" data-free-count="<?= $result->free?>" data-ge-pocetPredplatne = "<?= $result->pocetPredplatne?>" data-genote="<?= $result->lectorNote?>" data-semestral-course="<?= $result->semestralcourse ?>" data-isrecorded = "<?= $result->isrecorded ?>" data-fixedinvoice = "<?= $result->fixedInvoice ?>">
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j. n. Y") ?></td>
                                    <td class="text-center"><?= $result->denTydne ?></td>
                                    <td class="text-center" data-order="<?= $result->timeFrom ?>"><?= (new DateTime())->createFromFormat("H:i:s", $result->timeFrom)->format("G:i") ?></td>
                                    <td class="text-center"><?= (new DateTime())->createFromFormat("H:i:s", $result->timeTo)->format("G:i") ?></td>
                                    <td class="text-left"><?= $result->title ?></td>
                                    <td class="text-left"><?= $result->instructor ?></td>
                                    <td class="text-center" data-order="<?= $result->occupancy ?>"><a href="#" data-type="participants-list"><?= $result->occupancy ?>/<?= $result->capacity ?></a></td>
                                    <td class="text-center" style="vertical-align: middle;"><input type="checkbox" name="groupExcerciseCanceled" <?= $result->canceled === "1" ? "checked" : "" ?>></td>
                                    <td class="text-center" ><a href="#" data-type="generateQR"><span class="glyphicon glyphicon-qrcode" title="QR kód pro okamžitou platbu"></span></a></td>
                                    <td class="text-center" ><a href="#" data-type="record"><span class="glyphicon glyphicon-list" title="Zapsat platební údaje o účastnících cvičení"></span></a></td>
                                    <td class="text-center">
                                        <?php if ($result->isrecorded == 1): ?>
                                            <span class="glyphicon glyphicon-check" title="Lekce byla zaevidována lektorem"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= (new DateTime())->createFromFormat("Y-m-d H:i:s", $result->logoutDeadline)->format("j. n. G:i") ?></td>
                                    <td class="text-center"><?= (new DateTime())->createFromFormat("Y-m-d H:i:s", $result->alertTime)->format("j. n. G:i") ?></td>
                                    <td class="text-center"><?= $result->id ?></td>
                                    <td><?= $result->orderNumber ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="11" class="text-center">Nenalezena žádná skupinová cvičení s přihlášeným uživatelem jako instruktorem.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> 

    <!-- modální dialog pro záznam lektora -->
    <div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-zaznamLektora-modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Záznam lektora o placení účastníků skupinové lekce</h3>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" id="modal-gecount-id">
                    <input type="hidden" id="modal-semestral-course">                    
                    
                    <div class="row" data-geCourse-only="true">
                        <div class="col-md-12"> 
                            <h4><b>Účast klientů na kurzu z pravidelných účastníků</b></h4>                    
                        </div>
                    </div>
                    <div class="row" data-geCourse-only="true">
                        <div class="col-md-12">                            
                            <table class="table table-bordered table-striped" id="tableGeAttendance">
                                <thead>
                                    <tr>
                                        <th style="width: 30%;">Klient</th>
                                        <th style="width: 30%;">Poznámka</th>
                                        <th style="width: 20%;" class="text-center" >Účastnil(a) se</th>
                                        <th style="width: 20%;" class="text-center" >Omluven</th>
                                        <th style="width: 20%;" class="text-center" >Nechodí(*)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center">Nebyli nalezeni žádní účastníci kurzu.</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p><small>* Nechodí se označí klient, který ještě nezačal nebo už přestal chodit na dané cvičení</small></p>
                        </div>                    
                    </div>
                    
                    <div class="row" data-geCourse-only="true">
                        <div class="col-md-12">                             
                            <hr>
                            <h4><b>ÚDAJE O MIMOŘÁDNÝCH ÚČASTNÍCÍCH</b></h4>                             
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="modal-gecashcount">Počet platících hotově</label>
                                <input type="number" class="form-control" id="modal-gecashcount" name="modal-gecashcount" value="0">                                
                            </div>
                        </div>	
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="modal-geqrcount">Počet platících QR kódem</label>
                                <input type="number" class="form-control" id="modal-geqrcount" name="modal-geqrcount" value="0">                                
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="modal-gebenefitcount">Počet platících BENEFIT</label>
                                <input type="number" class="form-control" id="modal-gebenefitcount" name="modal-gebenefitcount" value="0">                                
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="modal-gefreecount">Počet uplatněných volných vstupů</label>
                                <input type="number" class="form-control" id="modal-gefreecount" name="modal-gefreecount" value="0">                                
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12"> 
                            <h4><b>Klienti, kteří čerpali lekci z předplatného</b></h4>                    
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">                            
                            <table class="table table-bordered table-striped" id="tableGePrepaid">
                                <thead>
                                    <tr>
                                        <th style="width: 25;">Klient</th>                                    
                                        <th style="width: 25%;" class="text-center" >Kredit před</th>
                                        <th style="width: 25%;" class="text-center" >Účastnil(a) se</th>
                                        <th style="width: 25%;" class="text-center" >Kredit po</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center">Nebyli nalezeni žádní klienti s předplaceným kreditem.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                                  
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="modal-genote">Další poznámky od lektora pro Fyzioland</label>
                                <textarea class="form-control" rows="8" id="modal-genote" name="modal-genote" data-form="true" placeholder="Poznámky">  </textarea>
                            </div>
                        </div>   
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button class="btn btn-success" id="LecturerRecordSave">Potvrdit údaje a uzavřít</button>  
                        </div>
                    </div>   
                </div>  
            </div>
        </div>
    </div>
    
    <!-- modální dialog pro zobrazení QR kódu -->
    <div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-qr-modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">QR kód pro okamžitou platbu</h3>
                </div>
                <div class="modal-body text-center">
                    <img src="" alt="QR kód pro platbu" id="imgQR" width="300" />
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="text-center">Částka</th>
                                <th class="text-center">Variabilní symbol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="qrAmount" class="text-center">Částka</td>
                                <td id="qrSymbol" class="text-center">Variabilní symbol</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- modální dialog pro zobrazení seznamu účastníků -->
    <div class="modal fade" tabindex="-1" role="dialog" id="participantsListModal">
        <div class="modal-dialog modal-lg" role="document">
            
            <style>
                table, td{
                  border-collapse: collapse;
                  border: 1px solid #000;
                  padding: 10px;
                }
                .checkbox{
                  text-align: center;
               /*Centering the text in a td of the table*/
                }
              </style>
            
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Přehled přihlášených účastníků</h4>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="participantsListTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Jméno</th>
                                    <th>Příjmení</th>
                                    <th>E-mail</th>
                                    <th>Telefon</th>
                                    <th>Poznámka</th>
                                    <th>Zaplaceno</th>
                                    <th>Fa Pohoda</th>
                                    <th>Akce</th>                                                                        
                                    <th>Počet účastí</th>                                                                                                                                                                                  
                                    <th>Datum registrace</th>
                                    <th>ID klienta</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="row">
                     <div>&nbsp;&nbsp;&nbsp;&nbsp;Legenda: Účasti = účasti na všech cvičeních - pouze na daném instruktorovi - pouze na daném instruktorovi a stejném cvičení</div>
                    </div>    
                    <div class="row">
                        <div class="col-lg-12 text-right">
                            <div class="form-group form-inline">
                                <br>
                                <label for="addParticipant">Vybrat dalšího účastníka:</label>
                                <select id="addParticipant" name="addParticipant" class="form-control">
                                    <option value=""></option>
                                    <?php
                                        $query = "  SELECT
                                                        c.id,
                                                        AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
                                                        AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                                                        AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email
                                                    FROM clients AS c
                                                    ORDER BY
                                                        AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'),
                                                        AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')";
                                        $stmt = $dbh->prepare($query);
                                        $stmt->execute();
                                        $resultClients = $stmt->fetchAll(\PDO::FETCH_OBJ);
                                        foreach ((array)$resultClients as $client):
                                    ?>
                                    <option value="<?= $client->id ?>">
                                        <?= $client->surname . ", " . $client->name ?>
                                        <?= empty($client->email) ? "" : " ({$client->email})" ?>
                                    </option>
                                    <?php
                                        endforeach;
                                    ?>
                                </select>
                                <button class="btn btn-success" type="button" id="addParticipantButton">Přidat účastníka</button>
                            </div>
                        </div>
                    </div>                                        
                    <hr>
                
                    <div class="row">
                        <div class="col-lg-12 text-center">
                            <h4>Odesílání SMS</h4>
                        </div>
                    </div>

                    <?php if ($resultAdminUser->isSuperAdmin === "1"): ?>
                    <div class="row">
                        <div class="col-lg-4" style="margin-bottom: 5px;">
                            <label>Výběr příjemců</label><br>
                            <button class="btn btn-success btn-block" type="button" id="buttonMarkAll">Označit všechny rezervace</button>
                            <button class="btn btn-success btn-block" type="button" id="buttonMarkNone">Neoznačit žádnou rezervaci</button>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="smsText">Text zprávy</label>
                                <select class="form-control" name="smsText" id="smsText">
                                    <?php
                                        $query = "SELECT id, text FROM smsText WHERE purpose = 's' ORDER BY id";
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
                    <hr>
                    <div class="col-md-12">
                        <div class="form-group">
                                <label for="clientnote">Emailové adresy všech přihlášených účastníků dohromady (bez duplicit)</label>
                                <textarea class="form-control" rows="2" id="participantEmails" name="participantEmails" data-form="true" placeholder="Emailové adresy všech přihlášených klientů">  </textarea>
                        </div>
                    </div>
                    
                </div>
                                
                <div class="modal-footer">
                    <input type="hidden" id="selectedGroupExcercise" value="">
                    <input type="hidden" id="semestralCourse" value="">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
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

    <script>
        $(document).ready(function () {
            $.fn.modal.Constructor.prototype.enforceFocus = function () {};                                                                   
            $.fn.select2.defaults.set( "width", "50%" );
                                     
            $("#addParticipant").select2({
               
                placeholder: "Žádný klient nevybrán, začněte psát příjmení klienta",
                language: {
                    noResults: function () {
                        return $('<a href="#" id="notFound">Nenalezeny žádné výsledky, založit nového klienta</a>');
                    }
                },
                selectOnClose: true
            });
        
            var semestralCourse = $("#semestralCourse").val();
            
            $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();

            $("#prehled-rezervaci").on("click", "a[name='delete']", function (event) {
                if (!confirm('Skutečně si přejete smazat zvolenou rezervaci?')) {
                    event.preventDefault();
                }
            });           
            
            $("#prehled-rezervaci").on("click", "a[data-type='record']", function(event) {						
                var tr = $(this).closest("tr");
                var id = tr.attr("data-id");
                var isrecorded = tr.attr("data-isrecorded")
                var course = tr.attr("data-semestral-course");
                var fixedinvoice =  tr.attr("data-fixedinvoice");
                $("#modal-semestral-course").val(course);
                
                // speciální odbočka pro cvičení, kde evidujeme jen konání nikoliv účastníky (typicky cvičení za fixní cenu do nějaké firmy)
                if (fixedinvoice == '1' && isrecorded =='0') {
                    if (confirm("U tohoto cvičení neevidujeme účastníky, ale pouze informaci, zda se cvičení konalo. Chce potvrdit konání cvičení?") ) {
                        $.ajax({
                        url: "fixedInvoiceConfirmation.php?_=" + new Date().getTime(),
                        method: "post",                                
                        dataType: "text",
                        data: {"id": id},
                        success: function(response) {
                            location.reload();
                            },
                        error: function(xhr, msg) {
                            alert("nastala chyba: " + msg + " . Neprovádějte dále záznam lektora a kontaktujte správce.");
                            }
                        });                   
                    }   
                    return false;
                }
                                
                // speciální části modálního dialogu pro semetral course                
                if (course !='') {                                        
                    //načtení docházky klientů na semetrálních kurzech
                    $("#tableGeAttendance tbody tr").remove();
                    $.ajax({
                        url: "getClientAttendace.php?_=" + new Date().getTime(),
                        method: "post",                                
                        dataType: "json",
                        data: {"ge": id, limit: 999},
                        success: function(response) {
                            $("#tableGeAttendance tbody tr").remove();
                            if (response.length > 0) {
                                $.each(response, function(i, obj) {
                                    var tr = $("<tr></tr>");                                
                                    $.each(obj, function(key, value) {                                                                                
                                        if (key === "ucast") {
                                            var td = $('<td><input type="checkbox" id = "ucastsemestral" name="ucastsemestral" data-id="' + id + '" data-client="' + response[i].clientId + '"' + value + '></td>');
                                            tr.append(td);
                                        } 
                                        if (key === "omluva") {                                                                                                                                                                            
                                            var td = $('<td><input type="checkbox" id = "omluvasemestral" name="omluvasemestral" data-id="' + id + '" data-client="' + response[i].clientId + '"' + value + '></td>');                                        
                                            tr.append(td);
                                        }
                                        if (key === "nechodi") {                                                                                                                                                                            
                                            var td = $('<td><input type="checkbox" id = "nechodisemestral" name="nechodisemestral" data-id="' + id + '" data-client="' + response[i].clientId + '"' + value + '></td>');                                        
                                            tr.append(td);
                                        }
                                        if (key === "client" ||key === "note" ) {
                                            var td = $("<td></td>");
                                            td.html(value);
                                            tr.append(td);
                                            td.css("text-align", "left");
                                        }
                                    });
                                    $("#tableGeAttendance tbody").append(tr);
                                });
                            } else {
                                var tr = $("<tr></tr>");
                                var td = $("<td colspan='3' class='text-center'></td>");
                                td.html("Nebyli aktuálně nalezeni žádní účastníci kurzu.");
                                tr.append(td);
                                $("#tableGeAttendance tbody").append(tr);
                            }
                        },
                        beforeSend: function() {
                            var tr = $("<tr></tr>");
                            var td = $("<td colspan='3'></td>");
                            td.html("Probíhá načítání záznamů.");
                            tr.append(td);
                            $("#tableGeAttendance tbody").append(tr);
                        }
                    });  

                    
                    $("div[data-geCourse-only='true']").show();
                    
                } else {
                    $("div[data-geCourse-only='true']").hide();
                }
                
                // načtení klientů s předplaceným nenulovým kreditem včetně checkboxu, zda se účastnili
                $("#tableGePrepaid tbody tr").remove();
                $.ajax({
                    url: "getClientsWithCredit.php?_=" + new Date().getTime(),
                    method: "post",                                
                    dataType: "json",
                    data: {"ge": id, limit: 999},
                    success: function(response) {
                        $("#tableGePrepaid tbody tr").remove();
                        if (response.length > 0) {
                            $.each(response, function(i, obj) {
                                var tr = $("<tr></tr>");                                
                                $.each(obj, function(key, value) {                                    
                                    if (key === "clientId") {                                        
                                        //nic - nevypisovat do tabulky
                                    } else { 
                                        if (key === "ucast") {                                                                                                                                                                            
                                            var td = $('<td><input type="checkbox" id = "ucast" name="ucast" data-id="' + id + '" data-client="' + response[i].clientId + '"' + value + '></td>');                                        
                                            tr.append(td);
                                        } else {
                                            var td = $("<td></td>");
                                            td.html(value);
                                            tr.append(td);
                                        }
                                    }
                                    if (key === "client") {
                                        td.css("text-align", "left");
                                    }
                                });
                                $("#tableGePrepaid tbody").append(tr);
                            });
                        } else {
                            var tr = $("<tr></tr>");
                            var td = $("<td colspan='4' class='text-center'></td>");
                            td.html("Nebyli aktuálně nalezeni žádní klienti s předplaceným kreditem.");
                            tr.append(td);
                            $("#tableGePrepaid tbody").append(tr);
                        }
                    },
                    beforeSend: function() {
                        var tr = $("<tr></tr>");
                        var td = $("<td colspan='3'></td>");
                        td.html("Probíhá načítání záznamů.");
                        tr.append(td);
                        $("#tableGePrepaid tbody").append(tr);
                    }
                });                                
                
                //načtení údajů o počtu účastníků dle typů placení (toto info je uloženo v tabulce s přehledem skup. cvičení u každého řádku               
                var cash = tr.attr("data-cash-count");
                var qr = tr.attr("data-qr-count");
                var benefit = tr.attr("data-benefit-count");
                var free = tr.attr("data-free-count");
                var note = tr.attr("data-genote");
                var pocetPredplatne = tr.attr("data-pocetPredplatne");

                $("#modal-gecount-id").val(id);
                $("#modal-gecashcount").val(cash);
                $("#modal-geqrcount").val(qr);
                $("#modal-gebenefitcount").val(benefit);
                $("#modal-gefreecount").val(free);
                $("#modal-genote").val(note);
                
                event.preventDefault();
                
                //var id = $(this).closest("tr").data("id");                
                
                isSuperAdmin = <?= $resultAdminUser->isSuperAdmin ?>;               
                                
                if (isrecorded == 1)  {
                    if (isSuperAdmin != 1) {
                        alert("Údaje byly již vyplněny. V případě, že došlo k chybnému vyplnění a je potřeba údaje změnit, kontaktujte Jiřího Havlického.");                        
                        return false;
                    }                    
                    if (isSuperAdmin == 1 && fixedinvoice == '1' && isrecorded =='1') {
                        alert('Konání kurzu již bylo lektorem potvrzeno. Účastníky tohoto cvičení neevidujeme.')
                    } else {
                    $("#zobrazit-zaznamLektora-modal").modal("show");
                    }
                } else {                    
                    $("#zobrazit-zaznamLektora-modal").modal("show");                    
                }  
            });                                            
            
            //uložení zaškrnutého klienta jako čerpání z předplatného do tabulky visits
            $("#tableGePrepaid").on("change", "input[name='ucast']", function() {                                
                var akce = $(this).prop("checked") ? 1 : 0;
                var id = $(this).attr("data-id");
                var client = $(this).attr("data-client");
                var td = $(this).closest("td");
                
                if (akce == '0') {
                    //Dodělat aktualizaci sloupce Kredit po po odškrnutí účasti na cvičení                    
                    // teď se kredit ukáže až při opětovném načtení modálního okna, nikoliv při zakrtnutí checkboxu - ničemu to nevadí
                }
                                       
                $.ajax({
                    url: "setPrepaid.php",
                    method: "post",
                    dataType: "text",
                    data: { "akce": akce, "ge": id, "client": client },
                    success: function() {
                        var span = $("<br><span class='label label-success'>Uloženo</span>");
                        td.append(span);
                        span.fadeOut(1500, function(){ $(this).remove(); });
                    },
                    error: function(xhr, msg) {
                            alert("nastala chyba: " + msg + " . Neprovádějte dále záznam lektora a kontaktujte správce.");
                    }
                });               
            });
            
            // zápis účasti klienta na semestral course do tabulky attendance
            $("#tableGeAttendance").on("change", "input[name='ucastsemestral']", function() {                                                
                var akce = $(this).prop("checked") ? 1 : 0;
                var type = 'ucast';
                var id = $(this).attr("data-id");
                var client = $(this).attr("data-client");
                var td = $(this).closest("td");                                      
               
                $.ajax({
                    url: "setAttendance.php",
                    method: "post",
                    dataType: "text",
                    data: { "akce": akce, "type": type, "ge": id, "client": client },
                    success: function() {
                        var span = $("<br><span class='label label-success'>Uloženo</span>");
                        td.append(span);
                        span.fadeOut(1500, function(){ $(this).remove(); });
                    },
                    error: function(xhr, msg) {
                            alert("nastala chyba: " + msg + " . Neprovádějte dále záznam lektora a kontaktujte správce.");
                    }
                });               
            }); 
            
            // zápis omluvy klienta na semestral course do tabulky attendance
            $("#tableGeAttendance").on("change", "input[name='omluvasemestral']", function() {                                
                var akce = $(this).prop("checked") ? 1 : 0;
                var type = 'omluva';
                var id = $(this).attr("data-id");
                var client = $(this).attr("data-client");
                var td = $(this).closest("td");                                      
               
                $.ajax({
                    url: "setAttendance.php",
                    method: "post",
                    dataType: "text",
                    data: { "akce": akce, "type": type, "ge": id, "client": client },
                    success: function() {
                        var span = $("<br><span class='label label-success'>Uloženo</span>");
                        td.append(span);
                        span.fadeOut(1500, function(){ $(this).remove(); });
                    },
                    error: function(xhr, msg) {
                            alert("nastala chyba: " + msg + " . Neprovádějte dále záznam lektora a kontaktujte správce.");
                    }
                });               
            }); 
            
            // zápis nechodí na semestral course do tabulky attendance
            $("#tableGeAttendance").on("change", "input[name='nechodisemestral']", function() {                                
                var akce = $(this).prop("checked") ? 1 : 0;
                var type = 'nechodi';
                var id = $(this).attr("data-id");
                var client = $(this).attr("data-client");
                var td = $(this).closest("td");                                      
               
                $.ajax({
                    url: "setAttendance.php",
                    method: "post",
                    dataType: "text",
                    data: { "akce": akce, "type": type, "ge": id, "client": client },
                    success: function() {
                        var span = $("<br><span class='label label-success'>Uloženo</span>");
                        td.append(span);
                        span.fadeOut(1500, function(){ $(this).remove(); });
                    },
                    error: function(xhr, msg) {
                            alert("nastala chyba: " + msg + " . Neprovádějte dále záznam lektora a kontaktujte správce.");
                    }
                });               
            }); 
            
            $("#prehled-rezervaci").on("click", "a[data-type='generateQR']", function (event) {
                event.preventDefault();                                
                var id = $(this).closest("tr").data("id");                
                var fixedinvoice =  $(this).closest("tr").data("fixedinvoice");
                                                
                if (fixedinvoice == '1') {
                    alert('Jedná se o firemní cvičení placené fakturou. Generování QR kódu neni povoleno')
                    return false;
                }
                
                
                $.ajax({
                    url: "getQRpayment.php",
                    method: "post",
                    dataType: "json",
                    data: {"id": id},
                    success: function (response) {
                        
                        $("#imgQR").attr("src", "data:image/png;base64," + response.img);
                        $("#qrAmount").html(response.amount);
                        $("#qrSymbol").html(response.symbol);                        
                        $("#zobrazit-qr-modal").modal("show");                                                                        
                    }
                });
            });                    

            var prehledRezervaci;
            if ($("#prehled-rezervaci td").length > 1) {
                prehledRezervaci = $("#prehled-rezervaci").DataTable({
                    "order": [[7, "asc"], [0, "asc"], [1, "asc"]],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                    },
                    "responsive": false,
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},                        
                        {responsivePriority: 2, targets: 2},
                        {responsivePriority: 3, targets: 6},
                        {responsivePriority: 4, targets: 7},
                        
                        {"width": "6%", "targets": 0},
                        {"width": "4%", "targets": 1},
                        {"width": "4%", "targets": 2},
                        {"width": "4%", "targets": 3},
                        {"width": "12%", "targets": 4},
                        {"width": "12%", "targets": 5},
                        {"width": "7%", "targets": 6},
                        {"width": "6%", "targets": 7},
                        {"width": "6%", "targets": 8},
                        
                        {"width": "6%", "targets": 9},
                        {"width": "6%", "targets": 10},
                        {"width": "3%", "targets": 11},
                        {"width": "6%", "targets": 12},                        
                        {"width": "6%", "targets": 13},                        
                        {"visible": false, "targets": 14}
                    ],
                    "fixedHeader": true
                });
                
                prehledRezervaci.on( 'responsive-display', function ( e, datatable, row, showHide, update ) {
                    var id = datatable.row(row.index()).nodes().to$().attr("data-id");
                    $("ul[data-dtr-index='" + row.index() + "']").closest("tr").attr("data-id", id);
                });
            };                                
            
            
            
            var participantsListTable = $('#participantsListTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                },
                dom: "ft",
                "order": [[1, "asc"]],
                "columns": [                   
                    { "data": "name" },
                    { "data": "surname" },
                    { "data": "email" },
                    { "data": "phone" },                    
                    { "data": "note" },
                    { "data": "paid" },
                    { "data": "faPohoda" },
                    { "data": "akce" },                    
                    { "data": "NrReg" },                                        
                    { "data": "registrationDate"},
                    { "data": "clientId" },
                    
                ],
                "fixedHeader": true,
                "pageLength": 100,
                
                "columnDefs": [
                    
                    
                    {responsivePriority: 1, targets: 0},                        
                    {responsivePriority: 2, targets: 1},
                    {responsivePriority: 3, targets: 4},
                    {responsivePriority: 4, targets: 5},                    
                    
                ],                                                     
                
                'columnDefs': [
                    {'targets': 4, 'createdCell':  function (td, cellData, rowData, row, col) 
                           {$(td).attr('data-field', 'note'); }                                           
                    },
                    {'targets': 5, 'createdCell':  function (td, cellData, rowData, row, col) 
                           {                               
                                $(td).attr('data-field', 'paid'); 
                                $(td).attr('class', 'text-center sorting_1;');
                                $(td).attr('style', 'vertical-align: middle;');                                
                            }                                            
                    },
                    {'targets': 6, 'createdCell':  function (td, cellData, rowData, row, col) 
                           {$(td).attr('data-field', 'faPohoda'); }                                           
                    }
                 ],         
                                                                    
                
                "createdRow": function( row, data, dataIndex ) {
                    $(row).attr('data-id', data.id);
                    $(row).attr('data-clientId', data.clientId);
                }
                
            });
            
            participantsListTable.on( 'responsive-display', function ( e, datatable, row, showHide, update ) {
                var id = datatable.row(row.index()).nodes().to$().attr("data-id");
                $("ul[data-dtr-index='" + row.index() + "']").closest("tr").attr("data-id", id);
            });

            $("#prehled-rezervaci").on("click", "a[data-type='participants-list']", function (event) {
                event.preventDefault();
                
                var id = $(this).closest("tr").data("id");

                $.ajax({
                    url: "participantsList.php",
                    method: "post",
                    dataType: "json",
                    data: {"id": id},
                    success: function (response) {
                        participantsListTable.clear();
                        participantsListTable.rows.add(response).draw();
                        
                        $("#participantsListModal").modal("show");
                        $("#selectedGroupExcercise").val(id);
                        $("#semestralCourse").val(response.semestralCourse);
                        
                        $("span[name='smsDetailIcon']").tlp({
                            content: function(callback) {
                                $.post("getSMSinfo.php", { "table": "groupExcercisesParticipants", "id": $(this).closest("tr").attr("data-id") }, function(response) { callback(response); }, "html");
                            }
                        });
                    }
                });               

                $.ajax({
                    url: "participantsEmails.php",
                    method: "post",
                    dataType: "json",
                    data: {"id": id},
                    success: function (response) {
                        $("#participantEmails").val(response.emails);                       
                    }
                });
            });
            
            $('#participantsListTable').on("click", "a[name='deleteLink']", function(event) {
                if ( !confirm("Skutečně si přejete zvolenou rezervaci na skupinové cvičení odstranit?") ) {
                    event.preventDefault();
                }
            });
            
            $('#participantsListTable').on("click", "a[name='emailLink']", function(event) {
                
                if ( !confirm("Skutečně si přejete odeslat klientovi potvrzení rezervace na skupinové cvičení?") ) {
                    event.preventDefault();
                }
            });
            
            $('#myModal').on('hidden.bs.modal', function (e) {
                participantsListTable.draw();
            });                       
            
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
                    data: { "table": "groupExcercisesParticipants", "predefinedMessage":  $("#smsText").val(), "customText": $("#smsCustomText").val(), "recipients": recipients },
                    success: function() {
                        location.reload();
                    }
                });
            });
            
            $("#zobrazit-zaznamLektora-modal").on("click", "#LecturerRecordSave", function() {
                var id = $("#modal-gecount-id").val();
                var cash = $("#modal-gecashcount").val();
                var qr = $("#modal-geqrcount").val();
                var benefit = $("#modal-gebenefitcount").val();                
                var free = $("#modal-gefreecount").val();                
                var note = $("#modal-genote").val();                
                
                //uložení hodnot z modálního dialogu do databáze - počet účastníků dle typu placení
                $.ajax({
                    url: "lecturerRecordSave.php",
                    method: "post",
                    dataType: "text",
                    data: { 
                        cash: cash,
                        qr: qr,
                        benefit: benefit,
                        free: free,
                        note: note,
                        id: id
                    },
                    success: function() {                                                
                        $("#prehled-rezervaci tr[data-id='" + id + "']").attr("data-cash-count", cash);
                        $("#prehled-rezervaci tr[data-id='" + id + "']").attr("data-qr-count", qr);
                        $("#prehled-rezervaci tr[data-id='" + id + "']").attr("data-benefit-count", benefit);
                        $("#prehled-rezervaci tr[data-id='" + id + "']").attr("data-free-count", free);
                        $("#prehled-rezervaci tr[data-id='" + id + "']").attr("data-genote", note);
                        $("#zobrazit-zaznamLektora-modal").modal("hide");
                    }
                });                
                location.reload();                
            });
            
            $("#prehled-rezervaci").on("change", "input[name='groupExcerciseCanceled']", function() {
                var val = $(this).prop("checked") ? 1 : 0;
                var id = $(this).closest("tr").attr("data-id");
                var td = $(this).closest("td");
                
                $.ajax({
                    url: "setGroupExcerciseCanceled.php",
                    method: "post",
                    dataType: "text",
                    data: { "canceled": val, "id": id },
                    success: function() {
                        var span = $("<br><span class='label label-success'>Uloženo</span>");
                        td.append(span);
                        span.fadeOut(1500, function(){ $(this).remove(); });
                    }
                });
            });
            
            $("#addParticipantButton").click(function() {
                var clientId = $("#addParticipant").val();
                var groupExcerciseId = $("#selectedGroupExcercise").val();
                
                if (clientId === "" || groupExcerciseId === "") {
                    alert("Nebyl vybrán žádný klient nebo žádné skupinové cvičení, registrace nebyla provedena.");
                } else {
                
                    $.ajax({
                        url: "addGroupExcerciseParticipant.php",
                        method: "post",
                        dataType: "text",
                        data: { "clientId": clientId, "groupExcerciseId": groupExcerciseId },
                        success: function() {
                            $(document).scrollTop(0);
                            location.reload();
                        }
                    });
                    
                }
            });                        
             
            $("#participantsListTable").on("click", "a[data-type='QRsms']", function (event) {               
                event.preventDefault();
                
                //id zápisu v tabulce groupexcercises, kde se dá dohledat id skup cvičení i id klienta
                var id = $(this).closest("tr").data("id");

                $.ajax({
                    url: "getQRsmsGroup.php",
                    method: "post",
                    dataType: "json",
                    data: {"id": id,  "predefinedMessage":  $("#smsText").val(), "customText": $("#smsCustomText").val()},
                    success: function (response) {
                        $("#imgQRsms").attr("src", "data:image/png;base64," + response.img);
                        $("#qrSMSClient").html(response.client);
                        $("#qrSMSPhone").html(response.phone);
                        $("#qrSMSFinalText").html(response.smsText);                        
                        
                        //$("participantsListModal").modal("hide");
                        $("#zobrazit-qrsms-modal").modal("show");                                                                
                    }
                });                 
            });
            
            $("#participantsListTable").on("click", "td a[data-role='editField']", function(event) {
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

            $("#participantsListTable").on("blur", "td input[type='text']", function() {
                var text = $(this).val();
                var span = $("<span data-role='content'></span>");

                span.text(text);
                $(this).replaceWith(span);
            });

            $("#participantsListTable").on("change", "td input", function() {
                var inputType = $(this).attr("type");
                var td = $(this).closest("td");
                var field = td.attr("data-field");                
                var clientId = $(this).closest("tr").attr("data-clientId");
                var groupExcerciseId = $("#selectedGroupExcercise").val();                
                var value;

                if (inputType === "text") {
                    value = $(this).val();
                } else if (inputType === "checkbox") {
                    value = ($(this).prop( "checked" ) ? 1 : 0);
                }

                //console.log(field + ": " + value);

                $.ajax({
                    "url": "groupExcerciseParticipantEdit.php",
                    "method": "post",
                    "data": { "clientId": clientId, "groupExcerciseId": groupExcerciseId, "field": field, "value": value },
                    "success": function(response) {
                        //console.log(response);
                        td.addClass("success");
                        setTimeout(function() { td.removeClass("success"); }, 1000);
                    }
                });
            });
            

            $("#alsoPassedAll").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewGroupReservations?alsoPassedAll";
                } else {
                    document.location = "viewGroupReservations";
                }
            });
            
            $("#alsoPassed6D").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewGroupReservations?alsoPassed6D";
                } else {
                    document.location = "viewGroupReservations";
                }
            });
            
            $("#alsoPassed3M").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewGroupReservations?alsoPassed3M";
                } else {
                    document.location = "viewGroupReservations";
                }
            });
            
            $("#alsoPassed6M").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewGroupReservations?alsoPassed6M";
                } else {
                    document.location = "viewGroupReservations";
                }
            });
                        
            $.widget.bridge("tlp", $.ui.tooltip); // ošetření konfliktu s widgetem tooltip() v Bootstrap, používáme ten z jQuery UI
        });

    </script>
</div>
</body>
</html>