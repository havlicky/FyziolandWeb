<?php

$pageTitle = "FL - objednávky (VŠICHNI - denní)";

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";

session_start();

/*
if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    $messageBox = new MessageBox();
    
    $messageBox->addText("Na tento pohled nemáte oprávnění.");
    $messageBox->setClass("alert-danger");

    $_SESSION["messageBox"] = $messageBox;
    header("Location: viewReservations");
    die();
}
*/
require_once "../header.php";

if (isset($_GET["date"])) {
    if (!$date = (new DateTime)->createFromFormat("Y-m-d", $_GET["date"])) {
        $date = (new DateTime());
    }
} else {
    $date = (new DateTime()); 
}
    
$dayOfWeek = intval($date->format("N"));
$differenceFromMonday = $dayOfWeek - 1;
$lastMonday = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P" . $differenceFromMonday . "D"));

$today = new DateTime();
$days = array(1 => "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle");  


?>

        <?php include "menu.php" ?>

        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h3>DENNÍ KALENDÁŘ OBJEDNÁVEK - VŠICHNI</h3>
                </div>
            </div>
            
            <div class="col-lg-12 col-lg-offset-2">
                <div class="row">
                    <div class="col-md-6 rezervace-prehled-rezervaci">
                        <h3>
                            Přehled rezervací 
                            <input class="form-control text-center" style="display: inline-block; width: auto; font-size: 20px;" name="date" value="<?= $days[$date->format("N")] ?>, <?= $date->format("j. n. Y") ?>">
                            <input class="form-control" type="hidden" name="dateFormatted" id="dateFormatted" value="<?= $date->format("Y-m-d") ?>">
                        </h3>
                    </div>                               
                </div>
            </div>
            
            <div class="row" id="hlavicka-rezervaci">
                <div class="col-xs-4 text-left">
                    <a href="viewReservationsAllTherapists.php?date=<?= (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P1D"))->format("Y-m-d"); ?>">
                        <h4>
                            <span class="glyphicon glyphicon-chevron-left"></span>
                            Předchozí den
                        </h4>
                    </a>
                </div>
                <div class="col-xs-4 text-center">
                     <a href="viewReservationsAllTherapists.php">
                        <h4>
                            Aktuální den
                        </h4>
                    </a>
                </div>
                <div class="col-xs-4 text-right">
                    <a href="viewReservationsAllTherapists.php?date=<?= (new DateTime($date->format("Y-m-d")))->add(new DateInterval("P1D"))->format("Y-m-d"); ?>">
                        <h4>
                            Následující den
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </h4>
                    </a>
                </div>
            </div>
            
            <div id="tableContainer">
                <div id="tableLoading">
                    <table>
                        <tr>
                            <td style="vertical-align: middle; text-align: center">Probíhá načítání volných termínů...</td>
                        </tr>
                    </table>
                </div>
                <table class="table table-bordered" id="reservations">
                    <thead>
                        <tr>
                            <th style="vertical-align: middle" class="text-center">
                                <span class="visible-lg visible-md">Čas rezervace</span>
                                <span class="visible-sm visible-xs">Čas</span>
                            </th>
                            <?php
                                $query = "  SELECT               
                                    id,
                                    displayName,
                                    shortcut  
                                FROM adminLogin
                                
                                WHERE 
                                    active = 1 AND
                                    (isErgo = 1 OR isFyzio)
                                ORDER BY orderRank";
                                
                                $stmt = $dbh->prepare($query);                    
                                $stmt->execute();
                                $results = $stmt->fetchAll(PDO::FETCH_OBJ);
                                $days = array(1 => "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle");
                                $daysShort = array(1 => "Po", "Út", "St", "Čt", "Pá", "So", "Ne");
                                
                                foreach ($results as $result) {                                                                      
                            ?>
                            <th class="text-center" style="width: 14,29%" data-terapist ="<?= $result->id ?>" data-order="<?= $i+1 ?>">
                                <span class="visible-lg visible-md visible-sm"><?= $result->displayName ?></span>
                                <span class="visible-xs"><?= $result->shortcut ?></span>

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
                                <?= $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>
                            </td>
                            <?php
                                foreach ($results as $result) {  

                            ?>
                            <td class="grey text-center" style="vertical-align: middle;  cursor: pointer;" data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>" data-time="<?= str_pad($hour, 2, "0", STR_PAD_LEFT) ?>:<?= str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>">
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
        
        <script>
            $(document).ready(function() {
                
                $("input[name='date']").datepicker({
                    altField: "#dateFormatted",
                    altFormat: "yy-mm-dd",
                    dayNames: ["neděle", "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota"],
                    dayNamesMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
                    firstDay: 1,
                    dateFormat: "DD, d. m. yy",
                    monthNames: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"],                    
                });
                
                $("input[name='date']").change(function() {                                                                                
                    window.location = "viewReservationsAllTherapists.php?date=" + $("#dateFormatted").val();
                });
                
                var date = $("input[name='dateFormatted']").val();                                        
                //location.href = 'viewReservationsAllTherapists.php?date=' + date;
                //zde bych potřeboval nastavit proměnnou $date na hodnotu z dateFormatted, jde to?

               
                
                $("#tableLoading").show();
                
                // výpis dostupných slotů                    
                    $.ajax({
                        url: "getSlotsAll.php",
                        data: {"date": date},
                        method: "post",
                        dataType: "json",
                        success: function(response) {
                            showSlots($("#reservations"), response);
                            $("#tableLoading").hide();                             
                        },
                        error: function(xhr, msg) {
                                alert("nastala chyba: " + msg + " při zobrazování vypsaných slotů. Neprovádějte rezervace pomocí tohoto systému a kontaktujte správce.");
                        }
                    });
                                                                              
                function showSlots(table, dataSet) {
                    table.find("td[data-hour]").each(function() {
                        var column = $(this).index();                        
                        var terapist = table.find("thead tr th:nth-child(" + (column + 1) + ")").attr("data-terapist");                        

                        $(this).attr("data-terapist", terapist);
                        $(this).removeClass("green");
                        $(this).addClass("grey");                        
                    });

                    $.each(dataSet, function(i, obj) {
                        var terapist = obj.person;                        
                        var time = obj.time;                        
                        table.find("tbody td[data-terapist='" + terapist + "'][data-time='" + time + "']").removeClass("grey").addClass("green")
                        
                    });
                }                               
                
                // výpis klientů, kteří mají aktivní rezervaci na ergo
                $.ajax({
                    url: "getReservationsAll.php",
                    data: { "date": date },
                    method: "post",
                    dataType: "json",
                    success: function(response) {
                        showRes($("#reservations"), response);                        
                    },
                    error: function(xhr, msg) {
                        alert("nastala chyba: " + msg + " při vypisování rezervací. Neprovádějte rezervace pomocí tohoto systému a kontaktujte správce.");
                    }
                });
                
                function showRes(table, dataSet) {
                    table.find("td[data-hour]").each(function() {
                        var column = $(this).index();                                                
                        var terapist = table.find("thead tr th:nth-child(" + (column + 1) + ")").attr("data-terapist");                        
                                                
                        $(this).attr("data-terapist", terapist);
                        $(this).find("span").html("-");
                        
                    });

                    $.each(dataSet, function(i, obj) {                                               
                        var terapist = obj.personnel;                        
                        var time = obj.time;
                        var klient = obj.client;
                        var service = obj.shortcut;

                        table.find("tbody td[data-terapist='" + terapist + "'][data-time='" + time + "']").find("span").html('<b>' + klient + '</b>' + '<br> (' + service + ')');
                    });
                }
                
                $("#reservations tbody tr td").click(function() {
                   
                    var date = $("input[name='dateFormatted']").val();
                    var person = $(this).attr("data-terapist");
                    var time = $(this).attr("data-time");
                    
                    $.ajax({
                        url: "changeSlotAvailability.php",
                        data: { "person": person, "date": date, "time": time },
                        method: "post",
                        dataType: "text",
                        success: function(response) {
                            if (response === "1") {
                                $("select[name='terapist']").trigger("change");
                                $("input[name='date']").trigger("change");
                            } else {
                                alert("Došlo k chybě, kontaktujte vývojáře :-)");
                            }
                        }
                    });
                });
                
               $("#reservations").on("click", "a[data-type='viewReservation']", function(event) {						                                                               
                    var terapist = $(this).closest("th").attr("data-terapist");
                    var date = $("input[name='dateFormatted']").val();  
                    location.href = 'viewReservations.php?date=' + date + '&user='+terapist;
                });
              
                //$("input[name='date']").trigger("change");
            });
        </script>
    </body>
</html>
