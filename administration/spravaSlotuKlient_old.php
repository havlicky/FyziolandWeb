<?php

$pageTitle = "Klient - správa Slotů";

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";

session_start();

require_once "../header.php";

$tdText = "";

$date = (new DateTime()); 
$dayOfWeek = intval($date->format("N"));
$differenceFromMonday = $dayOfWeek - 1;
$lastMonday = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P" . $differenceFromMonday . "D"));

?>

<?php include "menu.php" ?>

<div class="container">    
    
    <div class="row">
        
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group form-inline">
                <label for="slotTypes">Typ slotů</label>
                <select class="form-control" id="slotTypes" name="slotTypes">                    
                    <option value="null">&lt;nevybráno&gt;</option>
                    <option value="must">Jediné možné</option>
                    <option value="nicetohave">Preferované</option>
                </select>                
            </div>
        </div>                       
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                <label for="activeErgoClient">Aktivní ček. listina ERGO</label>
                <select class="form-control" name="activeErgoClient" id="activeErgoClient" style="width: 160px;" value="">
                    <option value="Y">ANO</option>
                    <option value="N">NE</option>
                </select>
            </div>
        </div>
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                    <label for="freqWergo">ERGO - Týdenní fr. návštěv</label>
                    <input type="number" class="form-control" name="freqWergo" id="freqWergo" style="width: auto;" value="">
            </div>						
        </div>
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                    <label for="freqMergo">ERGO - Měsíční fr. návštěv</label>
                    <input type="number" class="form-control" name="freqMergo" id="freqMergo" style="width: auto;" value="">
            </div>						
        </div>        
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                    <label for="lastSlotsUpdate">Terapeutky</label>
                    <input type="text" class="form-control" name="lastSlotsUpdate" id="therapists" style="width: auto;" value="" readonly>
            </div>						
        </div>
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                    <label for="lastSlotsUpdate">Last update</label>
                    <input type="text" class="form-control" name="lastSlotsUpdate" id="lastSlotsUpdate" style="width: auto;" value="" readonly>
            </div>						
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group form-inline">
                <label for="updateType">Aktualizovat</label>
                <select class="form-control" id="updateType" name="updateType">                    
                    <option value="null">&lt;nevybráno&gt;</option>
                    <option value="manual">Ručně</option>
                    <option value="auto">Automatem</option>
                </select>                
            </div>
        </div>  
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                <label for="activeFyzioClient">Aktivní ček. listina FYZIO</label>
                <select class="form-control" name="activeFyzioClient" id="activeFyzioClient" style="width: 160px;" value="">
                    <option value="Y">ANO</option>
                    <option value="N">NE</option>
                </select>
            </div>
        </div>        
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                <label for="freqWfyzio">FYZ - Týdenní fr. návštěv</label>
                <input type="number" class="form-control" name="freqWfyzio" id="freqWfyzio" style="width: auto;" value="">
            </div>						
        </div>
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                <label for="freqMfyzio">FYZ - Měsíční fr. návštěv</label>
                <input type="number" class="form-control" name="freqMfyzio" id="freqMfyzio" style="width: auto;" value="">
            </div>						
        </div>        
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                <label for="fyzduration">Délka terapie Kiss [min]</label>
                <input type="number" class="form-control" name="fyzduration" id="fyzduration" step="15" style="width: auto;" value="">
            </div>					
        </div>
        <div class="col-md-2 text-left" style="margin-top: 1px;">
            <div class="form-group">
                <label for="fyzbezlehatka">Fyzioterapie bez lehátka</label>
                <select class="form-control" id="fyzbezlehatka" name="fyzbezlehatka" data-form="true">
                    <option value="">&lt;nevybráno&gt;</option>
                    <option value="Y">Ano</option>
                    <option value="N">Ne</option>
                </select>
            </div>
        </div>
    </div>
    <!--
    <div class="row">
        <div class="col-lg-12 text-center">
            <h3>Obecně preferované sloty klienta</h3>
        </div>
    </div>
    -->
    <div>
        <table class="table table-bordered" id="clientslots">
            <thead>
                <tr>
                    <th style="vertical-align: middle" class="text-center">
                        <span class="visible-lg visible-md">Čas rezervace</span>
                        <span class="visible-sm visible-xs">Čas</span>
                    </th>
                    <?php
                        $days = array(1 => "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle");
                        $daysShort = array(1 => "Po", "Út", "St", "Čt", "Pá", "So", "Ne");
                        
                        for ($i = 0; $i < 7; $i++) {                            
                            $currentDate = (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P" . $i . "D"));
                            $dayName = $days[$currentDate->format("N")];
                            $dayNameShort = $daysShort[$currentDate->format("N")];
                    ?>
                    <th class="text-center" style="width: 13%" data-dayOfWeek="<?= $i ?>" data-order="<?= $i+1 ?>">
                        <span class="visible-lg visible-md visible-sm"><?= $dayName ?></span>
                        <span class="visible-xs"><?= $dayNameShort ?></span>                        
                    </th>
                    <?php
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    for ($hour = Settings::$timeFrom; $hour <= Settings::$timeTo; $hour++) {

                        $hourFrom = $hour;

                        if ($hourFrom == 10 || $hourFrom == 11 || $hourFrom == 12) {
                            $minuteFrom = 15;
                        } else {
                            $minuteFrom = 0;
                        }

                        if ($hourFrom == 10 || $hourFrom == 11) {
                            $minuteTo = 15;
                        } else {
                            $minuteTo = 0;
                        }

                        $hourTo = $hourFrom + 1;
                ?>
                <tr>
                    <td class="text-center" data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>">
                        <?= $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) . " - " . $hourTo . ":" . str_pad($minuteTo, 2, "0", STR_PAD_LEFT) ?>
                    </td>
                    <?php
                        for ($i = 0; $i < 7; $i++) {

                    ?>
                    <td class="grey text-center" style="vertical-align: middle; cursor: pointer;" data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>" data-time="<?= str_pad($hour, 2, "0", STR_PAD_LEFT) ?>:<?= str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>">
                        <span class="large-device"> <?=$tdText?></span>
                        <span class="small-device"> <?= mb_substr($tdText, 0, 2); ?></span>
                    </td>
                    <?php
                        }
                    ?>
                </tr>
                <?php

                    }
                ?>
            </tbody>    
        </table>
    </div>
    
    <div class="row">
        <!--
        <div class="col-lg-12 text-center">
                <h3>TÝDENNÍ KALENDÁŘ</h3>
        </div>
        -->
    </div>     
    
    <div class="row" style = "margin-bottom: 5px;">
        <div class="col-lg-9 col-lg-offset-1 text-center">
            <div class="form-group">
                <br>
                <label for="client">Vybrat klienta:</label>
                <select id="client" name="client" class="form-control">
                    <option value=""></option>
                    <?php
                        $query = "  SELECT
                                        c.id,
                                        AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
                                        AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                                        AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                                        AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email                                        
                                    FROM clients AS c
                                    ORDER BY
                                        AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'),
                                        AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')";
                        $stmt = $dbh->prepare($query);
                        $stmt->execute();
                        $resultClients = $stmt->fetchAll(\PDO::FETCH_OBJ);
                        foreach ((array)$resultClients as $client):                   
                            // vlastnosti do atributů data-atribut="hodnota"
                            $attributes = array();
                            foreach ($client as $key => $property) {
                                $attributes[] = "data-" . $key . '="' . htmlspecialchars($property) . '"';
                            }
                            $attributesString = implode(" ", $attributes)
                    ?>

                    
                    <option value="<?= $client->id ?>" <?= $attributesString ?>>
                        <?= $client->surname . ", " . $client->name ?>
                        <?= empty($client->email) ? "" : " ({$client->email})" ?>
                        <?= empty($client->phone) ? "" : " ({$client->phone})" ?>
                    </option>
                    <?php
                        endforeach;
                    ?>
                </select>                
            </div>
        </div>       
    </div>
    
    <div class="row">
        <div class="col-lg-6 col-lg-offset-4">
            <div class="form-group">
                <label for="therapist"> Terapeut/terapeutka</label>
                <select name="therapist" id="therapist" class="form-control" style=" width: 380px;">
                    <option value = 'allErgoTherapists' value="">Všichni (celý přehled klientů)</option>                    
                    <option value = 'clientOnly' value="">Všichni ERGO (pouze vybraný klient)</option>
                    <option disabled value="">-------------------------------------------</option>
                        <?php
                            if (intval($resultAdminUser->isSuperAdmin) === 1) {
                                $query = "  SELECT
                                                a.id,
                                                a.displayName,
                                                a.isFyzio
                                            FROM adminLogin AS a
                                            WHERE
                                                a.active = 1 AND
                                                EXISTS (SELECT id FROM relationPersonService WHERE person = a.id)
                                            ORDER BY a.displayName";
                                $stmt = $dbh->prepare($query);
                            } else {
                                $query = " SELECT
                                                a.id,
                                                a.displayName,
                                                a.isFyzio
                                            FROM adminLogin AS a
                                            WHERE
                                                a.active = 1 AND
                                                EXISTS (SELECT id FROM relationPersonService WHERE person = a.id)
                                            ORDER BY a.displayName";
                                $stmt = $dbh->prepare($query);
                                $stmt->bindParam(":login", $_COOKIE["loginName"], PDO::PARAM_STR);
                            }
                            $stmt->execute();
                            $resultsUsers = $stmt->fetchAll(PDO::FETCH_OBJ);

                        ?>
                    <?php foreach ($resultsUsers as $user): ?>
                    <option value="<?= $user->id ?>" <?= $user->id == $selectedUser ? "selected" : "" ?> data-isFyzio= <?=  $user->isFyzio?> ><?= $user->displayName ?> </option>
                    <?php endforeach; ?>
                    <option disabled value="">------------------------</option>                    
                    <option value = 'freeSlotsOnly' value="">VOLNÉ - ALL</option>
                    <option value = 'freeSlotsEntryOnly' value="">VOLNÉ PRO VSTUPY</option>
                </select>
            </div>
        </div>
        <div class="col-lg-2" >
            <div class="form-group">
                <label for="countErgoClients">Počet klientů z erga</label>
                <input type="text" class="form-control" name="countErgoClients" id="countErgoClients" style="width: auto;" value="" readonly>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 col-lg-offset-4">
            <div class="form-group">
                <label for="service">Služba pro nové rezervace</label>
                <select name="service" id= "service" class="form-control" style="width: 380px;"> </select>
            </div>
        </div>        
    </div>    
    
    <div class="row">
        <div class="col-xs-4 text-left">
            <a href="#" id="previousWeekButton">
                <h4>
                    <span class="glyphicon glyphicon-chevron-left"></span>
                    Předchozí týden
                </h4>
            </a>
        </div>
        <div class="col-xs-4 text-center">
            <a href="#"id="currentWeekButton">
                <h4>
                    Aktuální týden
                </h4>
            </a>
        </div>
        <div class="col-xs-4 text-right">
            <a href="#" id="nextWeekButton">
                <h4>
                    Následující týden
                    <span class="glyphicon glyphicon-chevron-right"></span>
                </h4>
            </a>
        </div>
    </div>                
    
    <div id="tableContainer" >
        <div id="tableLoading">
            <table>
                <tr>
                    <td style="vertical-align: middle; text-align: center">Probíhá načítání volných termínů...</td>
                </tr>
            </table>
        </div>
        <table class="table table-bordered" style = "margin-bottom: 5px;" id="reservations">
            <thead>
                <tr>
                    <th style="vertical-align: middle" class="text-center">
                        <span class="visible-lg visible-md">Čas rezervace</span>
                        <span class="visible-sm visible-xs">Čas</span>
                    </th>

                    <?php
                        $days = array(1 => "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle");
                        $daysShort = array(1 => "Po", "Út", "St", "Čt", "Pá", "So", "Ne");
                        $today = (new DateTime())->format("Y-m-d");                       
                        
                        for ($i = 0; $i < 7; $i++) {
                            $currentDate = (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P" . $i . "D"));
                            $currentDate2 = $currentDate->format("Y-m-d");                            
                            $dayName = $days[$currentDate->format("N")];
                            $dayNameShort = $daysShort[$currentDate->format("N")];
                    ?>
                    <?php if ($today === $currentDate2): ?>
                    <th class="text-center" style="width: 13%; color: white; background-color: black" data-day="<?= $currentDate->format("Y-m-d") ?>" data-day-formatted="<?= $currentDate->format("j.n.Y") ?>" data-order="<?= $i+1 ?>">
                    <?php endif; ?>
                        
                    <?php if ($today != $currentDate2): ?>
                    <th class="text-center" style="width: 13%; color: black; background-color: rgba(255, 255, 255, 0.4);" data-day="<?= $currentDate->format("Y-m-d") ?>" data-day-formatted="<?= $currentDate->format("j.n.Y") ?>" data-order="<?= $i+1 ?>">
                    <?php endif; ?>
                        
                        <span class="visible-lg visible-md visible-sm"><?= $dayName ?></span>
                        <span class="visible-xs"><?= $dayNameShort ?></span>

                        <span class="visible-lg visible-md visible-sm" data-content="longDate"><?= $currentDate->format("j.n.Y") ?></span>
                        <span class="visible-xs"><small  data-content="shortDate"><?= $currentDate->format("j.n.") ?></small></span>

                        <a href="#" data-type="viewReservation" data->
                        <span class="glyphicon glyphicon-share-alt" title="Zobrazit denní detailní plán terapeuta"></span></a>
                    </th>
                    <?php
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    for ($hour = Settings::$timeFrom; $hour <= Settings::$timeTo; $hour++) {

                        $hourFrom = $hour;

                        if ($hourFrom == 10 || $hourFrom == 11 || $hourFrom == 12) {
                            $minuteFrom = 15;
                        } else {
                            $minuteFrom = 0;
                        }

                        if ($hourFrom == 10 || $hourFrom == 11) {
                            $minuteTo = 15;
                        } else {
                            $minuteTo = 0;
                        }

                        $hourTo = $hourFrom + 1;
                ?>
                <tr>
                    <td class="text-center" data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>">
                        <?= $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) . " - " . $hourTo . ":" . str_pad($minuteTo, 2, "0", STR_PAD_LEFT) ?>
                    </td>
                    <?php
                        for ($i = 0; $i < 7; $i++) {

                    ?>
                    <td class="context-menu-one grey text-center" style="vertical-align: middle; " data-clientName = "" data-resid="" data-resservice ="" data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>" data-time="<?= str_pad($hour, 2, "0", STR_PAD_LEFT) ?>:<?= str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>">
                        <span class="">&nbsp;</span>
                    </td>
                    <?php
                        }
                    ?>                            
                </tr>
                <?php

                    }
                ?>                        
            </tbody>    
        </table>
        
        
            <div class="col-lg-3 col-lg-offset-11">
                <button type="button" style = "margin-top: 0px;" class="btn btn-secondary"  style="margin-top: 25px; margin-bottom: 15px;  margin-left:0px;" id="buttonprevres"><</button>			
                <button type="button" style = "margin-top: 0px;" class="btn btn-secondary"  style="margin-top: 25px; margin-bottom: 15px;  margin-left:0px;" id="buttonnextres">></button>	
            </div>
       
    </div>
    
    <div class="row">
        <div class="col-lg-12 text-center">
            <h3>ČEKACÍ LISTINA</h3>
            <h5>Rychlé volby</h5>
        </div>
    </div>        

    <div class="row">
        <div class="col-lg-6 col-lg-offset-4">
            <div class="form-group">
                <div class="input-group">                    
                    <select name="quickwl" id="quickwl" class="form-control" style=" width: auto;">
                        <option value = 'allWeek' value="">Celý týden</option>                    
                        <option value = 'allForenoon' value="">Každé dopoledne</option>
                        <option value = 'allAfternoon' value="">Každé odpoledne</option>
                        <option value = '0' value="">Pondělí</option>
                        <option value = '1' value="">Úterý</option>
                        <option value = '2' value="">Středa</option>
                        <option value = '3' value="">Čtvrtek</option>
                        <option value = '4' value="">Pátek</option>
                        <option value = '5' value="">Sobota</option>
                        <option value = '6' value="">Neděle</option>
                    </select>
                    <span class="input-group-btn">
                        <button class="btn btn-success" id="quickWLaction" type="button"> Vyznačit </button>
                        <button class="btn btn-danger" id="quickWLcancel" type="button"> Odznačit </button>                            
                    </span>
                    
                    <span class="input-group-btn">                           
                        <button class="btn" id="quickWLcopyToNextWeek" style ="margin-left: 5px; " type="button">Zkopírovat týden do následujícího</button>
                    </span>
                    <span class="input-group-btn">                           
                        <button class="btn" id="quickWLcopyToNextWeeks" style ="margin-left: 5px; " type="button">Zkopírovat týden do 8 násl.</button>
                    </span>
                    
                
            </div>            
        </div>
    </div>        
    
    <div class="row">
        <div class="col-xs-4 text-left">
            <a href="#" id="previousWeekButtonWL">
                <h4>
                    <span class="glyphicon glyphicon-chevron-left"></span>
                    Předchozí týden
                </h4>
            </a>
        </div>
        <div class="col-xs-4 text-center">
            <a href="#"id="currentWeekButtonWL">
                <h4>
                    Aktuální týden
                </h4>
            </a>
        </div>
        <div class="col-xs-4 text-right">
            <a href="#" id="nextWeekButtonWL">
                <h4>
                    Následující týden
                    <span class="glyphicon glyphicon-chevron-right"></span>
                </h4>
            </a>
        </div>
    </div> 
    
    <div id="tableContainer" >        
        <table class="table table-bordered" id="waitingList">
            <thead>
                <tr>
                    <th style="vertical-align: middle" class="text-center">
                        <span class="visible-lg visible-md">Čas rezervace</span>
                        <span class="visible-sm visible-xs">Čas</span>
                    </th>

                    <?php
                        $days = array(1 => "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle");
                        $daysShort = array(1 => "Po", "Út", "St", "Čt", "Pá", "So", "Ne");
                        for ($i = 0; $i < 7; $i++) {
                            $currentDate = (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P" . $i . "D"));

                            $dayName = $days[$currentDate->format("N")];
                            $dayNameShort = $daysShort[$currentDate->format("N")];
                    ?>
                    <th class="text-center" style="width: 13%; " data-day="<?= $currentDate->format("Y-m-d") ?>" data-day-formatted="<?= $currentDate->format("j.n.Y") ?>" data-order="<?= $i+1 ?>">
                        <span class="visible-lg visible-md visible-sm"><?= $dayName ?></span>
                        <span class="visible-xs"><?= $dayNameShort ?></span>

                        <span class="visible-lg visible-md visible-sm" data-content="longDate"><?= $currentDate->format("j.n.Y") ?></span>
                        <span class="visible-xs"><small  data-content="shortDate"><?= $currentDate->format("j.n.") ?></small></span>

                        <!--
                        <a href="#" data-type="viewReservation" data->
                        <span class="glyphicon glyphicon-share-alt" title="Zobrazit denní detailní plán terapeuta"></span></a>
                        -->
                    </th>
                    <?php
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    for ($hour = Settings::$timeFrom; $hour <= Settings::$timeTo; $hour++) {

                        $hourFrom = $hour;

                        if ($hourFrom == 10 || $hourFrom == 11 || $hourFrom == 12) {
                            $minuteFrom = 15;
                        } else {
                            $minuteFrom = 0;
                        }

                        if ($hourFrom == 10 || $hourFrom == 11) {
                            $minuteTo = 15;
                        } else {
                            $minuteTo = 0;
                        }

                        $hourTo = $hourFrom + 1;
                ?>
                <tr>
                    <td class="text-center" data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>">
                        <?= $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) . " - " . $hourTo . ":" . str_pad($minuteTo, 2, "0", STR_PAD_LEFT) ?>
                    </td>
                    <?php
                        for ($i = 0; $i < 7; $i++) {

                    ?>
                    <td class="context-menu-one grey text-center" style="vertical-align: middle; " data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>" data-time="<?= str_pad($hour, 2, "0", STR_PAD_LEFT) ?>:<?= str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>">
                        <span class="">&nbsp;</span>
                    </td>
                    <?php
                        }
                    ?>                            
                </tr>
                <?php

                    }
                ?>                        
            </tbody>    
        </table>
    </div>
        
</div>

<div style="position: fixed; top: 120px; right: 25px;">
   
    
    <div>        
        <div class="col-md-4 text-left" style="margin-top: 1px;">
            <div class="form-group">
                <label for="clientname">Klient</label>
                <input type="text" class="form-control" name="clientname" id="clientname" style="width: auto;" value="" readonly>
            </div>
        </div>
        <div class="col-md-4 text-left" style="margin-top: 1px;">
            <div class="form-group">
                <label for="clientphone">Telefon</label>
                <input type="text" class="form-control" name="clientphone" id="clientphone" style="width: auto;" value="" readonly>
            </div>
        </div>
        <div class="col-md-4 text-left" style="margin-top: 23px;">
            <div class="col-md-6" style="margin-top: 1px;">
                <button class="btn btn-primary"  style="width: 150px;" type="button" id="ordinace">Ordinace</button>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-striped table-hover" id="tableResWLoverview" style = "font-size:13px; width: 100%;">
                    <thead>
                        <tr>                                            
                            <th class="text-center" style="vertical-align: middle;">Týden od</th>
                            <th class="text-center" style="vertical-align: middle;">Datum</th>
                            <th class="text-center" style="vertical-align: middle;">Den</th>                                
                            <th class="text-center" style="vertical-align: middle;">Čas</th>
                            <th class="text-center" style="vertical-align: middle;">Klient</th>                            
                            <th class="text-center" style="vertical-align: middle;">Služba</th>
                            <th class="text-center" style="vertical-align: middle;">Terapeut</th>                                                                                                                                    
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center">Nebyly nalezeny žádné rezervace.</td>
                        </tr>
                </tbody>

                </table>
            </div>
        </div>
        <div class="row">
            
        </div>
    </div> 
</div>

<!-- modální dialog pro zobrazení historie všech akcí s klietem na čekací listině a budoucích poznámek k jednotlivým týdnům-->
<div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-prehledKomunikace-modal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Přehled provedených akcí s nabídkami volných termínů + týdenní poznámky</h3>
            </div>
            <div class="modal-body text-center">                    
                <table id="TableactionListWL" class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10%;" class="text-center">Okamžik pobídky</th>     
                            <th style="width: 10%;" class="text-center">Nabízený termín</th>     
                            <th style="width: 10%;" class="text-center">Typ</th>
                            <th style="width: 10%;" class="text-center">Terapeut</th>
                            <th style="width: 40%;" class="text-center">Poznámka</th>     
                            <th style="width: 10%;" class="text-center">Využito</th>
                            <th style="width: 10%;" class="text-center">Odmítnuto</th>                                
                            <th style="width: 10%;" class="text-center">Akce</th>                                
                        </tr>
                    </thead>
                    <tbody>
                        <tr>                                
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>                    
            </div>
        </div>
    </div>
</div>

<!-- modální dialog návrhy rezervací dle waiting listu klienta-->
<div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-WLnavrhy-modal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Přehled nabídky termínů pro klienta dle čekací listiny</h3>
            </div>
            <div class="modal-body text-center">                    
                <table id="TableWLnavrhy" class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10%;" class="text-center">Datum</th>     
                            <th style="width: 10%;" class="text-center">Čas</th>     
                            <th style="width: 10%;" class="text-center">Terapeut</th>                            
                            <th style="width: 10%;" class="text-center">Akce</th>                                
                        </tr>
                    </thead>
                    <tbody>
                        <tr>                                
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>                    
            </div>
        </div>
    </div>
</div>

<div style="position: fixed; top: 80px; left: 25px;">       
    <div class="col-md-6" style="margin-top: 1px;">
        <button class="btn btn-secondary"  style="width: 150px;" type="button" id="prehledKomunikace">Přehled komunikace</button>
    </div>
    <div class="col-md-6" style="margin-top: 1px;">
        <button class="btn btn-secondary"  style="width: 150px;" type="button" id="WLnavrhy">Návrhy dle ček. listiny</button>
    </div>
</div>

<div style="position: fixed; top: 150px; left: 25px;">       
    <div>                
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group" >
                        <label for="clientnotewl">Obecná poznámka ke klientovi</label>
                        <textarea class="form-control"  rows="15" style="width:250%;"  id="clientnotewl" name="clientnotewl" data-form="true" placeholder="">  </textarea>
                </div>
            </div>
        </div>
    </div> 
</div>

<div style="position: fixed; top: 550px; left: 25px;">       
    <div>        
        
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group" >
                        <label for="clientweeknotewl">Poznámka ke klientovi na daný týden</label>
                        <textarea class="form-control"  rows="12" style="width:200%;"  id="clientweeknotewl" name="clientweeknotewl" data-form="true" placeholder="">  </textarea>
                </div>
            </div>
        </div>
    </div> 
</div>

<script>
    $(document).ready(function() {        
        var firstDateFrom = $("#waitingList thead tr th[data-day]").first().attr("data-day");
        var firstDateTo = $("#waitingList thead tr th[data-day]").last().attr("data-day");
        
        var d = new Date();
        var currDate = d.getFullYear() + (d.getMonth()+1) + "-" +d.getDate();
                
        //testovací
        //var currHour = 8;
        var d = new Date();        
        var currDate = d.getFullYear() + '-' + (d.getMonth()+1) + "-" +d.getDate();
        var currHour = d.getHours();
        var currMinute = 00;
        var poradi = -1;
        switch (currHour) {
            case 10:
              currMinue = "15";
              break;
            case 11:
              currMinue = "15";
              break;
            case 12:
              currMinue = "15";
        }     
        
        $.fn.select2.defaults.set( "width", "50%" );                  
        
        $("#client").select2({                           
            placeholder: "Začněte psát příjmení klienta",
            language: {
                noResults: function () {
                    return $('<a href="#" id="notFound">Nenalezeny žádné výsledky</a>');
                }
            },
            selectOnClose: true
        });                 
        
        // *****************************************************
        // ***************** ZMĚNA TERAPEUTA *******************
        // *****************************************************
        
        $("select[name='therapist']").change(function() {
            var person = $("select[name='therapist']").val();            
            var dateFrom = $("#reservations thead tr th[data-day]").first().attr("data-day");
            var dateTo = $("#reservations thead tr th[data-day]").last().attr("data-day");
            var isFyzio =  $("select[name='therapist']").find("option:selected").first().attr("data-isFyzio");
            var limitW_ErgoClients =  "<?= $resultAdminUser->limitW_ErgoClients ?>";
            
            //potřebuji vědět klienta kvůli zabarvení v kalendáři
            var client = $("select[name='client']").val();               
            var email = $("select[name='client']").find(':selected').data("email");
            var phone = $("select[name='client']").find(':selected').data("phone");   

            //naplnění comboxu se službami daného terapeuta
            var sel = document.getElementById('service'); // find the drop down
            $.ajax({
                url: "getServices.php",
                data: { "person": person },
                method: "post",
                dataType: "json",
                success: function(response) {                                                            
                    //vyčištění combo boxu
                    var i, L = sel.options.length - 1;
                    for(i = L; i >= 0; i--) {
                        sel.remove(i);
                    }
                    //naplnění comboboxu novými hodnotami
                    for (let i = 0; i < response.length; i++) { 
                        var opt = document.createElement("option"); // Create the new element
                        opt.value = response[i].id; // set the value
                        opt.text = response[i].name; // set the text
                        sel.appendChild(opt); // add it to the select
                    }
                    
                    if (isNaN(person)==false){                                                         
                        $("select[name='service']").trigger("change");
                    }
                    
                }
            });                          

            //Pro fyzioterapeutky zjištění počtu erga            
            if (isFyzio == 1) {                
                $.ajax({
                    url: "getCountErgoClients.php",
                    data: { "person": person, "dateFrom": dateFrom, "dateTo": dateTo },
                    method: "post",
                    dataType: "json",
                    success: function(response) {
                       //ZDE 
                       $("#countErgoClients").val(response.count);
                    }
                });
            }            
            
            // výpis rezervací terapeuta       
            $.ajax({
                url: "getReservations.php",
                data: { "client": client, "emali": email, "phone": phone, "person": person, "dateFrom": dateFrom, "dateTo": dateTo, "active": 1 },
                method: "post",
                dataType: "json",
                success: function(response) {
                    enableCellsRes($("#reservations"), response);                   
                }
            });                                                                        
        
        
            function enableCellsRes(table, dataSet) {
                table.find("td[data-hour]").each(function() {
                    var columnDate2 = $(this).index();
                    var date2 = table.find("thead tr th:nth-child(" + (columnDate2 + 1) + ")").attr("data-day");                  
                    $(this).attr("data-date", date2);
                    $(this).find("span").html("-");
                    $(this)[0].style.color = "#000000";
                    $(this).attr("data-resid", ""); 
                    $(this).attr("data-clientName", ""); 
                    $(this).attr("data-resservice", "");

                    var person = $("select[name='therapist']").val();  
                    if (isNaN(person)==true){     
                        $(this).removeClass("green");
                        $(this).addClass("grey");  
                    }
                });

                $.each(dataSet, function(i, obj) {
                    var date2 = obj.date;
                    var time2 = obj.time;                                
                    var klient = obj.client;                
                    var service = obj.shortcut;
                    var resid = obj.id;  
                    var resservice = obj.service;
                    var freeSlotsTherapists = obj.freeSlotsTherapists;
                    var person = $("select[name='therapist']").val();
                    var zelenit = obj.green;

                    if(person == 'allErgoTherapists'){
                        table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html(klient);
                        table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").removeClass("grey").addClass("green");
                    } else if (person == 'freeSlotsOnly') {
                        table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html(freeSlotsTherapists);
                        if (zelenit == 1) {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").removeClass("grey").addClass("green");}
                    } else if (person == 'freeSlotsEntryOnly') {
                        table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html(freeSlotsTherapists);
                        table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").removeClass("grey").addClass("green");                    
                    } else if (person == 'clientOnly'){ 
                        table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html('<b>' + klient + '</b>');                  
                        table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").removeClass("grey").addClass("green");
                    } else {
                        table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html('<b>' + klient + '</b>' + '<br> (' + service + ')');                  
                    }
                    table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").attr("data-resid", resid);                
                    table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").attr("data-resservice", resservice);                
                    table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").attr("data-clientName", klient);                
                });
            }
        });
                
        // *****************************************
        // ********* ZMĚNA SLUŽBY ******************
        // *****************************************
        
         $("select[name='service']").change(function() {
            var person = $("select[name='therapist']").val();            
            var dateFrom = $("#reservations thead tr th[data-day]").first().attr("data-day");
            var dateTo = $("#reservations thead tr th[data-day]").last().attr("data-day");
            var service = $("select[name='service']").val();	 
            //alert(service);
            
            // výpis dostupných slotů - pokud se jedná o konkrétního terapeuta a ne ostatní volby
            if (isNaN(person)==false){                                                                         
                $.ajax({
                    url: "getSlots.php",
                    data: { "person": person, "dateFrom": dateFrom, "dateTo": dateTo, "service":service },
                    method: "post",
                    dataType: "json",
                    success: function(response) {
                        enableCellsWLRes($("#reservations"), response, "res");
                        //$("#tableLoading").hide();
                    }                                
                });
            }                        
         });
         
        function enableCellsWLRes(table, dataSet, type) {
            table.find("td[data-hour]").each(function() {
                var columnDate = $(this).index();
                var date = table.find("thead tr th:nth-child(" + (columnDate + 1) + ")").attr("data-day");
                $(this).attr("data-date", date);

                $(this).removeClass("green");
                $(this).addClass("grey");
                if (type == "WL") {$(this).find("span").html("");}
            });

            $.each(dataSet, function(i, obj) {
                var date = obj.date;
                var time = obj.time;
                var user = obj.user;
                var lastEditDate = obj. lastEditDate;
                table.find("tbody td[data-date='" + date + "'][data-time='" + time + "']").removeClass("grey").addClass("green");
                if (type == "WL" && user !=null) {table.find("tbody td[data-date='" + date + "'][data-time='" + time + "']").find("span").html('<FONT COLOR=gray>' + user + ' <small>(' + lastEditDate + ')</small>');}
            });
        }
        
        
        // *****************************************
        // ********* ZMĚNA KLIENTA *****************
        // *****************************************
        // pozn.: na konci dochází i k triggeru změny terapeuta
        
        $("select[name='client']").change(function() {
            //výběr klienta v comboboxu
            //naplnění atributů ke klientovi
            element = $(this).find(':selected')[0];                        
            $.each(element.attributes, function(i, attrib) {
                var attributeName = attrib.name.substring(5);
                var attributeValue = attrib.value;
                
                var element = $("#" + attributeName);                
                if (element.length > 0) {
                    element.val(attributeValue);
                } else {
                    element = $("[name='" + attributeName + "'][value='" + attributeValue + "'").prop("checked", "checked");
                }
            });
        
            var client = $("select[name='client']").val();               
            var email = $("select[name='client']").find(':selected').data("email");
            var phone = $("select[name='client']").find(':selected').data("phone");   
            var surname = $("select[name='client']").find(':selected').data("surname");
            var name = $("select[name='client']").find(':selected').data("name");
            $("#clientname").val(surname + ', ' + name);
            $("#clientphone").val(phone);
                        
            var today_1 = new Date();
            var today = today_1.getFullYear()+'-'+(today_1.getMonth()+1)+'-'+today_1.getDate();
			            
            var dateFrom = $("#waitingList thead tr th[data-day]").first().attr("data-day");
            var dateTo = $("#waitingList thead tr th[data-day]").last().attr("data-day");
            
            //aktualizace tabulky rezervací klienta zafixované vpravo na obrazovce
            $("#tableResWLoverview tbody tr").remove();

            $.ajax({
                url: "getClientResWLoverview.php?_=" + new Date().getTime(),
                method: "post",
                data: {"dateFrom" : firstDateFrom, "dateTo" : firstDateTo, "email" : email, "phone" : phone, "client" : client},
                dataType: "json",
                success: function(response) {
                    $("#tableResWLoverview tbody tr").remove();
                    if (response.length > 0) {
                        $.each(response, function(i, obj) {                        
                        if (response[i].dateFormatted>=dateFrom && response[i].dateFormatted<=dateTo){
                            var tr = $("<tr style='color:red; font-weight: bold; font-size:105%;' ></tr>");
                        } else {                            
                            var tr = $("<tr></tr>");
                        }
                            $.each(obj, function(key, value) {                                       
                                var td = $("<td></td>");
                                if (key !== "dateFormatted" && key!=='weeknote') {
                                    td.css("text-align", "center");
                                    td.html(value);
                                    tr.append(td);
                                }                                           
                                                                        
                            });
                            $("#tableResWLoverview tbody").append(tr);
                        });                        
                    } else {
                        var tr = $("<tr></tr>");
                        var td = $("<td colspan='11' class='text-center'></td>");
                        td.html("Nebyly nalezeny žádné budoucí rezervace klienta");
                        tr.append(td);
                        $("#tableResWLoverview tbody").append(tr);
                    }
                    
                    },
                    beforeSend: function() {
                        var tr = $("<tr></tr>");
                        var td = $("<td colspan='11'></td>");
                        td.html("Probíhá načítání záznamů.");
                        tr.append(td);
                        $("#tableResWLoverview tbody").append(tr);
                    }
                });                            

            $.ajax({
                url: "getClientInfoSlots.php",
                data: { "client": client },
                method: "post",
                dataType: "json",
                success: function(response) {
                   $("#activeFyzioClient").val(response.activeFyzioClient);                   
                   if (response.activeFyzioClient == 'Y') {
                        $("#freqMfyzio").prop("readonly", false);
                        $("#freqWfyzio").prop("readonly", false);
                   } else {
                        $("#freqMfyzio").prop("readonly", true);
                        $("#freqWfyzio").prop("readonly", true);                   
                   }
                   
                   $("#activeErgoClient").val(response.activeErgoClient);
                   if (response.activeErgoClient == 'Y') {
                        $("#freqMergo").prop("readonly", false);
                        $("#freqWergo").prop("readonly", false);
                   } else {
                        $("#freqMergo").prop("readonly", true);
                        $("#freqWergo").prop("readonly", true);                   
                   }
                   
                   $("#freqWergo").val(response.freqWergo);
                   $("#freqMergo").val(response.freqMergo); 
                   $("#freqWfyzio").val(response.freqWfyzio);
                   $("#freqMfyzio").val(response.freqMfyzio); 
                   $("#fyzduration").val(response.fyzduration); 
                   $("#fyzbezlehatka").val(response.fyzBezLehatka); 
                   $("#slotTypes").val(response.slotTypes);
                   $("#updateType").val(response.updateType);
                   $("#lastSlotsUpdate").val(response.lastSlotsUpdate);
                   $("#therapists").val(response.mainTherapists + ' (' + response.otherTherapists+ ') ALL: ' + response.allTherapists );
                   
                }
            });
                                    
            //načtení obecných slotů, které klientovi vyhovují
            $.ajax({
                url: "getSlotsClient.php",
                data: { "client": client },
                method: "post",
                dataType: "json",
                success: function(response) {
                    enableCells_obvykleSloty($("#clientslots"), response);
                    //$("#tableLoading_clientSlots").hide();
                }
            });
                                               
            $.ajax({
                url: "getResStatistics.php",
                data: { "client": client },
                method: "post",
                dataType: "json",
                success: function(response) {
                    statistics($("#clientslots"), response);
                    //$("#tableLoading_clientSlots").hide();
                }
            });
            
            //načtení slotů čekací listiny                        
            $.ajax({
                url: "getSlotsWL.php",
                data: { "client": client, "dateFrom": dateFrom, "dateTo": dateTo },
                method: "post",
                dataType: "json",
                success: function(response) {
                    enableCellsWLRes($("#waitingList"), response, "WL");
                    //$("#tableLoading_waitingList").hide();
                }
            });
            
            //načtení týdenní poznámky od klienta
            $.ajax({
                url: "getClientWeekNoteWL.php",
                data: { "client": client, "lastmonday": dateFrom},
                method: "post",
                dataType: "json",
                success: function(response) {
                    $("#clientweeknotewl").val(response.note);
                }
            }); 
            
            //načtení obecné poznámky od klienta
            $.ajax({
                url: "getClientNoteWL.php",
                data: { "client": client},
                method: "post",
                dataType: "json",
                success: function(response) {
                    $("#clientnotewl").val(response.noteWL);
                }
            });
            
            $("select[name='therapist']").trigger("change");
        });                       
        
        function statistics(table, dataSet) {            
            table.find("td[data-hour]").each(function() {
                var columnDate = $(this).index();
                var dayOfWeek = table.find("thead tr th:nth-child(" + (columnDate + 1) + ")").attr("data-dayOfWeek");
                $(this).attr("data-dayOfWeek", dayOfWeek);                
                $(this).find("span.large-device").html("");                
            });
            
            $.each(dataSet, function(i, obj) {
                var pocet = obj.pocet;
                var hour = obj.hour;  
                var dayOfWeek = obj.dayOfWeek;
                table.find("tbody td[data-dayOfWeek='" + dayOfWeek + "'][data-hour='" + hour + "']").find("span.large-device").html('<b>' + pocet + '</b>');
            });
        }                

        function enableCells_obvykleSloty(table, dataSet) {
            table.find("td[data-hour]").each(function() {
                var columnDate = $(this).index();
                var dayOfWeek = table.find("thead tr th:nth-child(" + (columnDate + 1) + ")").attr("data-dayOfWeek");
                $(this).attr("data-dayOfWeek", dayOfWeek);

                $(this).removeClass("green");
                $(this).addClass("grey");
                //$(this).find("span.large-device").html("NE");
                //$(this).find("span.small-device").html("NE");
            });

            $.each(dataSet, function(i, obj) {
                var dayOfWeek = obj.dayOfWeek;
                var time = obj.time;

                table.find("tbody td[data-dayOfWeek='" + dayOfWeek + "'][data-time='" + time + "']").removeClass("grey").addClass("green").find("span.large-device").html();;
                //table.find("tbody td[data-dayOfWeek='" + dayOfWeek + "'][data-time='" + time + "']").find("span.small-device").html("ANO");
            });
        }                

        $("#buttonprevres").click(function(event) {            
            event.preventDefault();
            poradi = poradi - 1;
            //alert(poradi);
            var person = $("select[name='therapist']").val();
            $.ajax({
                url: "getPrevReservation.php",
                data: { "person": person, "hour": currHour, "minute": currMinute, "poradi": poradi, "date": currDate },
                method: "post",
                dataType: "json",
                success: function(response) {                    
                    //alert(response.client);
                    currDate = response.date;
                    currHour = response.hour;
                    currMinute = response.minute;
                    poradi = parseInt((response.poradi));                    
                    $("select[name='client']").val(response.client).trigger('change');
                }
            });
        });
        
        $("#buttonnextres").click(function(event) {            
            event.preventDefault();            
            poradi = poradi + 1;          
            var person = $("select[name='therapist']").val();
            $.ajax({
                url: "getNextReservation.php",
                data: { "person": person, "hour": currHour, "minute": currMinute, "poradi": poradi, "date": currDate },
                method: "post",
                dataType: "json",
                success: function(response) {                   
                    //alert(response.client);
                    currDate = response.date;
                    currHour = response.hour;
                    currMinute = response.minute;
                    poradi = parseInt((response.poradi));                    
                    $("select[name='client']").val(response.client).trigger('change');
                }
            });
        });
                    
        $("#nextWeekButton").click(function(event) {
            event.preventDefault();
            newPeriod(7);
        });

        $("#previousWeekButton").click(function(event) {
            event.preventDefault();
            newPeriod(-7);
        });

        $("#currentWeekButton").click(function(event) {
            event.preventDefault();
            newPeriod(0);
        });
        
        $( "#nextWeekButtonWL").click(function(event) {
            event.preventDefault();
            newPeriod(7);
        });

        $("#previousWeekButtonWL").click(function(event) {
            event.preventDefault();
            newPeriod(-7);
        });

        $("#currentWeekButtonWL").click(function(event) {
            event.preventDefault();
            newPeriod(0);
        });
        
        $("#ordinace").click(function(event) {   
            var client = $("select[name='client']").val();
            window.open('http://192.168.1.204/index.php?client=' + client, '_blank');
        });
        
        $("#prehledKomunikace").click(function(event) {               
            var client = $("select[name='client']").val();            
            $.ajax({
                    url: "getActionListWL.php",
                    method: "post",
                    dataType: "json",
                    data: {"client": client, "type": "ALL"},
                    success: function (response) {                        
                        $("#TableactionListWL tbody tr").remove();
                        if (response.length > 0) {
                            $.each(response, function(i, obj) {
                                var tr = $("<tr data-id=" + response[i].id + "></tr>");                                 
                                tr.append( $("<td style = 'text-align: center'>" + response[i].date + "</td>") );
                                tr.append( $("<td style = 'text-align: center'>" + response[i].WLdate + "</td>") );
                                tr.append( $("<td style = 'text-align: center'>" + response[i].type + "</td>") );                                
                                tr.append( $("<td style = 'text-align: center'>" + response[i].displayName + "</td>") );                                
                                tr.append( $("<td style = 'text-align: left' data-field='note'>" + "<span data-role='content'> " + response[i].note + "</span>" + " <a href='#' style='float: right;' data-role='editField'><span class='glyphicon glyphicon-pencil'></span></a></td>") );
                                tr.append( $('<td data-field="utilized" ><input type="checkbox" name="utilized" '+ response[i].utilized + '></td>') );
                                tr.append( $('<td data-field="rejected" ><input type="checkbox" name="rejected" '+ response[i].rejected + '></td>') );                                
                                tr.append( $("<td style = 'text-align: center'>" + response[i].akce + "</td>") );                                
                                $("#TableactionListWL tbody").append(tr);
                                $("#zobrazit-prehledKomunikace-modal").modal("show"); 
                            });
                        } else {
                            var tr = $("<tr></tr>");
                            var td = $("<td colspan='6' class='text-center'></td>");
                            td.html("Nebyly nalezeny žádné akce.");
                            tr.append(td);
                            $("#TableactionListWL tbody").append(tr);
                            $("#zobrazit-prehledKomunikace-modal").modal("show"); 
                        }
                    },
                    beforeSend: function() {
                        var tr = $("<tr></tr>");
                        var td = $("<td colspan='6'></td>");
                        td.html("Probíhá načítání záznamů.");
                        tr.append(td);
                        $("#TableactionListWL tbody").append(tr);
                    }
                });
            
        });
        
        $("#WLnavrhy").click(function(event) {               
            var client = $("select[name='client']").val();                        
            
            $.ajax({
                    url: "getWLnavrhy.php",
                    method: "post",
                    dataType: "json",
                    data: {"client": client},
                    success: function (response) {                                                
                        $("#TableWLnavrhy tbody tr").remove();
                        if (response.length > 0) {
                            $.each(response, function(i, obj) {
                                var tr = $("<tr data-id=" + response[i].id + "></tr>");                                 
                                tr.append( $("<td style = 'text-align: center'>" + response[i].date + "</td>") );
                                tr.append( $("<td style = 'text-align: center'>" + response[i].time + "</td>") );
                                tr.append( $("<td style = 'text-align: center'>" + response[i].therapist + "</td>") );
                                tr.append( $("<td style = 'text-align: center'>" + response[i].akce + "</td>") );
                                $("#TableWLnavrhy tbody").append(tr);
                                $("#zobrazit-WLnavrhy-modal").modal("show"); 
                            });
                        } else {
                            var tr = $("<tr></tr>");
                            var td = $("<td colspan='4' class='text-center'></td>");
                            td.html("Nebyly nalezeny žádné akce.");
                            tr.append(td);
                            $("#TableWLnavrhy tbody").append(tr);
                            $("#zobrazit-WLnavrhy-modal").modal("show"); 
                        }
                    },
                    beforeSend: function() {
                        var tr = $("<tr></tr>");
                        var td = $("<td colspan='4'></td>");
                        td.html("Probíhá načítání záznamů.");
                        tr.append(td);
                        $("#TableWLnavrhy tbody").append(tr);
                    }
                });
               
            
        });
        
        
        $("#TableactionListWL").on("click", "td a[data-role='editField']", function(event) {
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

        $("#TableactionListWL").on("blur", "td input[type='text']", function() {
            var text = $(this).val();
            var span = $("<span data-role='content'></span>");

            span.text(text);
            $(this).replaceWith(span);
        });

        $("#TableactionListWL").on("click", "td a[data-role='editField']", function(event) {
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

        $("#TableactionListWL").on("blur", "td input[type='text']", function() {
            var text = $(this).val();
            var span = $("<span data-role='content'></span>");

            span.text(text);
            $(this).replaceWith(span);
        });

        $("#TableactionListWL").on("change", "td input", function() {
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
                "url": "getActionListWLedit.php",
                "method": "post",
                "data": { "id": id, "field": field, "value": value },
                "success": function(response) {
                    //console.log(response);
                    td.addClass("success");
                    setTimeout(function() { td.removeClass("success"); }, 1000);
                }
            });
        });
        
        function newPeriod(dayCount) {
            $("#waitingList thead tr th[data-day]").each(function() {
                var curDate = $(this).attr("data-day");
                if (parseInt(dayCount) > 0) {
                    var newDate = moment(curDate, "YYYY-MM-DD").add(dayCount, "days");
                } else if (parseInt(dayCount) < 0) {
                    var newDate = moment(curDate, "YYYY-MM-DD").subtract(-dayCount, "days");
                } else {
                    var newDate = moment().day($(this).attr("data-order"));
                }

                $(this).attr("data-day", newDate.format("YYYY-MM-DD"));
                $(this).attr("data-day-formatted", newDate.format("D.M.YYYY"));
                $(this).find("[data-content='longDate']").html(newDate.format("D.M.YYYY"));
                $(this).find("[data-content='shortDate']").html(newDate.format("D.M."));
            });
            
            $("#reservations thead tr th[data-day]").each(function() {
                var curDate = $(this).attr("data-day");
                if (parseInt(dayCount) > 0) {
                    var newDate = moment(curDate, "YYYY-MM-DD").add(dayCount, "days");
                } else if (parseInt(dayCount) < 0) {
                    var newDate = moment(curDate, "YYYY-MM-DD").subtract(-dayCount, "days");
                } else {
                    var newDate = moment().day($(this).attr("data-order"));
                }

                $(this).attr("data-day", newDate.format("YYYY-MM-DD"));
                $(this).attr("data-day-formatted", newDate.format("D.M.YYYY"));                
                $(this).find("[data-content='longDate']").html(newDate.format("D.M.YYYY"));
                $(this).find("[data-content='shortDate']").html(newDate.format("D.M."));
                
                var dnes = "<?= $today ?>";
                var datumKalendar = newDate.format("YYYY-MM-DD");
                //alert('dnes: ' + dnes + ' datumKalendar: ' + datumKalendar);
                if(dnes == datumKalendar) {                                  
                    $(this).attr("style", "width: 13%; color: white; background-color: black;");                     
                } else {                                        
                    $(this).attr("style", "width: 13%; color: black; background-color: rgba(255, 255, 255, 0.4);");     
                }                
            });

            $("select[name='client']").trigger("change");
        }
        
        $("#clientweeknotewl").on("change", function() {                                 
            var client = $("select[name='client']").val();
            var dateFrom = $("#waitingList thead tr th[data-day]").first().attr("data-day");
            var note = $("#clientweeknotewl").val();
            
            $.ajax({
                url: "changeClientWeekNoteWL.php",
                data: { "client": client, "lastmonday": dateFrom, "note": note},
                method: "post",
                dataType: "text",
                success: function(response) {
                    $("select[name='client']").trigger("change");
                }
            });                                 
        });
        
        $("#clientnotewl").on("change", function() {                                 
            var client = $("select[name='client']").val();            
            var note = $("#clientnotewl").val();
            
            $.ajax({
                url: "changeClientNoteWL.php",
                data: { "client": client, "note": note},
                method: "post",
                dataType: "text",
                success: function(response) {
                    
                }
            });                                 
        }); 
        
        $("#activeFyzioClient").on("change", function() {
            var client = $("select[name='client']").val();
            var activeFyzioClient = $("#activeFyzioClient").val();            
            
             $.ajax({
                url: "changeActiveClient.php",
                data: { "client": client, "value": activeFyzioClient, "type": 'Fyzio' },
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                        if(activeFyzioClient == 'N') {
                            alert('Frekvence chození pro čekací listinu na FYZIO byly vynulovány.')
                            $("#freqMfyzio").val(0).trigger('change');    
                            $("#freqWfyzio").val(0).trigger('change'); 
                            $("#freqMfyzio").prop("readonly", true);
                            $("#freqWfyzio").prop("readonly", true);
                        } else {
                            alert('Frekvence chození pro čekací listiiu na FYZIO byly nastaveny na výchozí hodnoty. V případě potřeby prosím upravte.')
                            // zde záleží na pořadí, je potřeba nejprve nastavit nenulovou hodnotu a pak terpve nulovou, jinak se to zacyklí (nulové hodnoty totiž zpsůobů další změnu na neaktivního klienta a jsem v kole
                            $("#freqMfyzio").val(2).trigger('change');
                            $("#freqWfyzio").val(0).trigger('change');
                            $("#freqMfyzio").prop("readonly", false);
                            $("#freqWfyzio").prop("readonly", false);
                        }
                        $("select[name='client']").trigger("change");                        
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });            
        });
        
        $("#activeErgoClient").on("change", function() {
            var client = $("select[name='client']").val();
            var activeErgoClient = $("#activeErgoClient").val();            
            
             $.ajax({
                url: "changeActiveClient.php",
                data: { "client": client, "value": activeErgoClient, "type": 'Ergo' },
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {                        
                        if(activeErgoClient == 'N') {
                            alert('Frekvence chození pro čekací listinu na ERGO byly vynulovány.')
                            $("#freqMergo").val(0).trigger('change');    
                            $("#freqWergo").val(0).trigger('change');
                            $("#freqWergo").prop("readonly", true);
                            $("#freqMergo").prop("readonly", true);
                        } else {
                            alert('Frekvence chození pro čekací listinu na ERGO byly nastaveny na výchozí hodnoty. V případě potřeby prosím upravte.')
                            // zde záleží na pořadí, je potřeba nejprve nastavit nenulovou hodnotu a pak terpve nulovou, jinak se to zacyklí (nulové hodnoty totiž zpsůobů další změnu na neaktivního klienta a jsem v kole
                            $("#freqWergo").val(1).trigger('change');  
                            $("#freqMergo").val(0).trigger('change');
                            $("#freqWergo").prop("readonly", false);
                            $("#freqMergo").prop("readonly", false);
                        }
                        $("select[name='client']").trigger("change");                        
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });            
        });
        
        $("#freqMergo").on("change", function() {
            var client = $("select[name='client']").val();
            var freqMergo = $("#freqMergo").val();
            var freqWergo = $("#freqWergo").val();
            var activeErgoClient = $("#activeErgoClient").val(); 
            
             $.ajax({
                url: "changeClientFreqMRes.php",
                data: { "client": client, "freqMergo": freqMergo, "type": 'Ergo' },
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                        if( (freqWergo == 0 || freqWergo == null) && (freqMergo == 0 || freqMergo == null) && activeErgoClient == 'Y')  {
                           alert('Klientovi bude VYPNUTA čekací listina na ERGO')
                           $("#activeErgoClient").val('N').trigger("change"); 
                        }
                        $("select[name='client']").trigger("change");                        
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });            
        });
        
         $("#freqMfyzio").on("change", function() {
            var client = $("select[name='client']").val();
            var freqMfyzio = $("#freqMfyzio").val();            
            var freqWfyzio = $("#freqWfyzio").val();
            var activeFyzioClient = $("#activeFyzioClient").val(); 
            
             $.ajax({
                url: "changeClientFreqMRes.php",
                data: { "client": client, "freqMfyzio": freqMfyzio, "type": 'Fyzio' },
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                        if( (freqWfyzio == 0 || freqWfyzio == null) && (freqMfyzio == 0 || freqMfyzio == null) && activeFyzioClient == 'Y')  {
                           alert('Klientovi bude VYPNUTA čekací listina na FYZIO')
                           $("#activeFyzioClient").val('N').trigger("change"); 
                        }
                        $("select[name='client']").trigger("change");                        
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });            
        });               
        
        $("#freqWergo").on("change", function() {
            var client = $("select[name='client']").val();
            var freqWergo = $("#freqWergo").val();
            var freqMergo = $("#freqMergo").val();  
            var activeErgoClient = $("#activeErgoClient").val(); 
            
            
            $.ajax({
                url: "changeClientFreqWRes.php",
                data: { "client": client, "freqWergo": freqWergo, "type": 'Ergo' },
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {                      
                       if( (freqWergo == 0 || freqWergo == null) && (freqMergo == 0 || freqMergo == null) && activeErgoClient == 'Y')  {
                           alert('Klientovi bude VYPNUTA čekací listina na ERGO')
                           $("#activeErgoClient").val('N').trigger("change"); 
                       }
                       $("select[name='client']").trigger("change");
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });           
        });
        
        $("#freqWfyzio").on("change", function() {
            var client = $("select[name='client']").val();
            var freqWfyzio = $("#freqWfyzio").val();
            var freqMfyzio = $("#freqMfyzio").val();
            var activeFyzioClient = $("#activeFyzioClient").val(); 
                        
            $.ajax({
                url: "changeClientFreqWRes.php",
                data: { "client": client, "freqWfyzio": freqWfyzio, "type": 'Fyzio' },
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                       if( (freqWfyzio == 0 || freqWfyzio == null) && (freqMfyzio == 0 || freqMfyzio == null) && activeFyzioClient == 'Y')  {
                           alert('Klientovi bude VYPNUTA čekací listina na FYZIO')
                           $("#activeFyzioClient").val('N').trigger("change"); 
                        }
                        $("select[name='client']").trigger("change");                       
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });           
        });
        
        $("#fyzduration").on("change", function() {
            var client = $("select[name='client']").val();
            var fyzduration = $("#fyzduration").val();            
            
             $.ajax({
                url: "changeClientFyzDuration.php",
                data: { "client": client, "fyzduration": fyzduration},
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                        $("select[name='client']").trigger("change");                        
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });            
        });
        
        $("#fyzbezlehatka").on("change", function() {
            var client = $("select[name='client']").val();
            var fyzbezlehatka = $("#fyzbezlehatka").val();            
            
             $.ajax({
                url: "changeClientFyzBezLehatka.php",
                data: { "client": client, "fyzbezlehatka": fyzbezlehatka},
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                        $("select[name='client']").trigger("change");                        
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });            
        });
        
        $("#slotTypes").on("change", function() {
            var client = $("select[name='client']").val();
            var slotTypes = $("select[name='slotTypes']").val();
            
            
            $.ajax({
                url: "changeClientSlotTypes.php",
                data: { "client": client, "slotTypes": slotTypes},
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                       $("select[name='client']").trigger("change");                       
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });           
        });
        
        $("#updateType").on("change", function() {
            var client = $("select[name='client']").val();
            var updateType = $("#updateType").val();
            
            
            $.ajax({
                url: "changeSlotsUpdateType.php",
                data: { "client": client, "updateType": updateType},
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                       $("select[name='client']").trigger("change");                       
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });           
        });
        
        $("#clientslots tbody tr td").click(function() {
            var client = $("select[name='client']").val();
            var dayOfWeek = $(this).attr("data-dayOfWeek");
            var time = $(this).attr("data-time");

            $.ajax({
                url: "changeClientSlotAvailability.php",
                data: { "client": client, "dayOfWeek": dayOfWeek, "time": time },
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                        $("select[name='client']").trigger("change");
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });
        }); 
        
        $("#waitingList tbody tr td").click(function() {            
            var client = $("select[name='client']").val();
            var date = $(this).attr("data-date");
            var time = $(this).attr("data-time");
            //alert(date);

            $.ajax({
                url: "changeSlotAvailabilityWL.php",
                data: { "client": client, "date": date, "time": time },
                method: "post",
                dataType: "text",
                success: function(response) {
                    if (response === "1") {
                        $("select[name='client']").trigger("change");
                    } else {
                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                    }
                }
            });
        });
        
       $("#reservations tbody tr td").click(function() {						                                                                                       
            var person = $("select[name='therapist']").val();	
            var client = $("#client").find(":selected").attr("data-id");
            if (isNaN(person)==true){ 
                alert('Vyberte terapeutku a/nebo klienta');
            } else {
                
                var date = $(this).attr("data-date");
                var time = $(this).attr("data-time");
                var hour = $(this).attr("data-hour");
                var minute = $(this).attr("data-minute");
                var resservice = $("select[name='service']").val();	
                var resid = $(this).attr("data-resid");
                var displayName =  "<?= $resultAdminUser->displayName ?>";
                var user =  "<?= $resultAdminUser->id ?>";
                
                var name = $("#client").find(":selected").attr("data-name");
                var surname = $("#client").find(":selected").attr("data-surname");
                var phone = $("#client").find(":selected").attr("data-phone");
                var email = $("#client").find(":selected").attr("data-email");
                var note = null;
                var clientName = $(this).attr("data-clientName");

                var delconf = false;

                if (resid>0) {
                    var delconf = confirm('Skutečně si přejete smazat zvolenou rezervaci pro ' + clientName + '?');			
                    // použije se id služby dle rezervace, nikoliv dle volby v comboboxu, protože jsem ve větvi kdy rezervaci mažu a ne vytvářím
                    var resservice = $(this).attr("data-resservice");
                    }
                if (resid == 0) {
                    if (!confirm('Skutečně si přejete vytvořit rezervaci pro ' + name + ' ' + surname + '?')) {
                    return;
                    }
                }

                $.ajax({
                    url: "api_reservationAction.php",
                    data: { 
                            "resid" : resid, 
                            "person": person, 
                            "date": date, 
                            "hour": hour,
                            "minute": minute,									
                            "service": resservice,
                            "client": client,
                            "name": name,
                            "surname": surname,
                            "phone": phone,
                            "email": email,
                            "delconf": delconf,                            
                            "displayName": displayName,
                            "user": user,
                            "note": note
                      },

                    method: "post",
                    dataType: "text",
                    success: function(response) {					                        
                            $("select[name='client']").trigger("change");
                    },
                    error: function(xhr, msg) {
                            alert("nastala chyba: " + msg + " . Neprovádějte rezervace pomocí tohoto systému a kontaktujte správce.");
                    }
                });                
            }            		
        });
        
        $("#quickWLaction").on("click", function() {		
            var client = $("#client").find(":selected").attr("data-id");
            var quickwl = $("select[name='quickwl']").val();	
            var dateFrom = $("#waitingList thead tr th[data-day]").first().attr("data-day");
            var dateTo = $("#waitingList thead tr th[data-day]").last().attr("data-day");
            
            if(client == null) {
                alert('Vyberte nejprve klienta');
                return(false);
            }
            
            $.ajax({
                url: "quickWLaction.php",
                data: { "client": client, "dateFrom": dateFrom, "dateTo": dateTo, "type":quickwl, "deleteOnly": 'N' },
                method: "post",
                dataType: "text",
                success: function(response) {
                    $("select[name='client']").trigger("change");                    
                }
            });
            
        });
        
        $("#quickWLcancel").on("click", function() {		            
            var client = $("#client").find(":selected").attr("data-id");
            var quickwl = $("select[name='quickwl']").val();	
            var dateFrom = $("#waitingList thead tr th[data-day]").first().attr("data-day");
            var dateTo = $("#waitingList thead tr th[data-day]").last().attr("data-day");
            
            if(client == null) {
                alert('Vyberte nejprve klienta');
                return(false);
            }
            
            $.ajax({
                url: "quickWLaction.php",
                data: { "client": client, "dateFrom": dateFrom, "dateTo": dateTo, "type": quickwl, "deleteOnly": 'Y' },
                method: "post",
                dataType: "text",
                success: function(response) {
                    $("select[name='client']").trigger("change");
                }
            });
            
        });
        
        $("#quickWLcopyToNextWeek").on("click", function() {		            
            var client = $("#client").find(":selected").attr("data-id");
            var quickwl = $("select[name='quickwl']").val();	
            var dateFrom = $("#waitingList thead tr th[data-day]").first().attr("data-day");    // dateFrom ve smyslu, co je vzor který kopíruji do následujícího týdne
            var dateTo = $("#waitingList thead tr th[data-day]").last().attr("data-day");       // dateTo ve smyslu, co je vzor který kopíruji do následujícího týdne
            
            if(client == null) {
                alert('Vyberte nejprve klienta');
                return(false);
            }
            
            $.ajax({
                url: "quickWLaction.php",
                data: { "client": client, "dateFrom": dateFrom, "dateTo": dateTo, "type": 'CopyToNextWeek'},
                method: "post",
                dataType: "text",
                success: function(response) {
                    $("select[name='client']").trigger("change");
                    alert('WL byl zkopírován do následujícího týdne');
                }
            });
            
        });
        
        $("#quickWLcopyToNextWeeks").on("click", function() {		            
            var client = $("#client").find(":selected").attr("data-id");
            var quickwl = $("select[name='quickwl']").val();	
            var dateFrom = $("#waitingList thead tr th[data-day]").first().attr("data-day");    // dateFrom ve smyslu, co je vzor který kopíruji do následujících týdnů
            var dateTo = $("#waitingList thead tr th[data-day]").last().attr("data-day");       // dateTo ve smyslu, co je vzor který kopíruji do následujících týdnů
            
            if(client == null) {
                alert('Vyberte nejprve klienta');
                return(false);
            }
            
            $.ajax({
                url: "quickWLaction.php",
                data: { "client": client, "dateFrom": dateFrom, "dateTo": dateTo, "type": 'CopyToNextWeeks'},
                method: "post",
                dataType: "text",
                success: function(response) {
                    $("select[name='client']").trigger("change");
                    alert('WL byl zkopírován do 8 následujících týdnů');
                }
            });
            
        });
        
        $("select[name='therapist']").trigger("change");
        $("#buttonnextres").click();
    });
</script>
</body>
</html>
