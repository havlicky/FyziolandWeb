<?php

$pageTitle = "FL - Ergo MĚSÍC";

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

?>

        <?php include "menu.php" ?>

        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h3>MĚSÍČNÍ KALENDÁŘ OBJEDNÁVEK ERGO</h3>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12 text-center">                                           
                    <?php                          
                        $query = "  SELECT
                                        a.id,
                                        a.displayName,
                                        a.shortcut
                                    FROM adminLogin AS a
                                    WHERE
                                        a.active = 1 AND
                                        EXISTS (SELECT id FROM relationPersonService WHERE person = a.id) AND
                                        a.isErgo = 1
                                    ORDER BY a.displayName";
                        $stmt = $dbh->prepare($query);
                        $stmt->execute();
                        $resultsUsers = $stmt->fetchAll(PDO::FETCH_OBJ);
                       ?>                                               
                </div>
            </div>
            
            <div class="row" id="hlavicka-rezervaci">
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
            
            <div id="tableContainer">
                <div id="tableLoading">
                    <table>
                        <tr>
                            <td style="vertical-align: middle; text-align: center">Probíhá načítání volných termínů...</td>
                        </tr>
                    </table>
                </div>
                <table class="table table-bordered" id="reservations">
                    <?php foreach ($resultsUsers as $user): ?>
                    <thead>
                        
                        <tr>
                            <th style="vertical-align: middle" class="text-center">
                                <span class="visible-lg visible-md">Čas rezervace</span>
                                <span class="visible-sm visible-xs">Čas</span>
                            </th>
                                                        
                            <?php
                                $days = array(1 => "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle");
                                $daysShort = array(1 => "Po", "Út", "St", "Čt", "Pá", "So", "Ne");
                                for ($i = 0; $i < 28; $i++) {
                                    $currentDate = (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P" . $i . "D"));

                                    $dayName = $days[$currentDate->format("N")];
                                    $dayNameShort = $daysShort[$currentDate->format("N")];
                            ?>
                            
                            <th class="text-center" style="width: 13%" data-terapist="<?= $user->id?>" data-day="<?= $currentDate->format("Y-m-d") ?>" data-day-formatted="<?= $currentDate->format("j.n.Y") ?>" data-order="<?= $i+1 ?>">
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
                                for ($hour = Settings::$timeFrom; $hour <= 16; $hour++) {

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
                                    <?= $user->shortcut . "<br>" . $hour . ":" . str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>
                                </td>
                                <?php
                                    for ($i = 0; $i < 28; $i++) {

                                ?>
                                <td class="grey text-center" style="vertical-align: middle; " data-hour="<?= $hour ?>" data-terapist="<?= $user->id?>" data-minute="<?= $minuteFrom ?>" data-time="<?= str_pad($hour, 2, "0", STR_PAD_LEFT) ?>:<?= str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>">
                                    <span class="">&nbsp;</span>
                                </td>
                                <?php
                                    }
                                ?>                            
                            </tr>
                            <?php

                                }
                            ?>  
                        <?php endforeach; ?>
                    </tbody>    
                </table>
            </div>
            
            <!--
            <div class="row">
                <div class="col-lg-12 text-right">
                    <span id="weekInfo" ></span>
                </div>
            </div>
            -->
        </div>
        <div class="col-lg-12 text-right">                    
            <button class="btn btn-light"  style="width: 250px;" type="button" id="buttonUpdate">Aktualizovat kalendář</button>
        </div>
        <script>
            $(document).ready(function() {
                
                $("#buttonUpdate").click(function() {
                    
                    var dateFrom = $("#reservations thead tr th[data-day]").first().attr("data-day");
                    var dateTo = $("#reservations thead tr th[data-day]").last().attr("data-day");

                    // výpis klientů, kteří mají aktivní rezervaci
                    $("#tableLoading").show();
                    $.ajax({
                        url: "getReservations.php",
                        data: { "dateFrom": dateFrom, "dateTo": dateTo },
                        method: "post",
                        dataType: "json",
                        success: function(response) {
                            enableCellsRes($("#reservations"), response);
                            $("#tableLoading").hide();
                        }
                    });                                                            
                
                    // výpis dostupných slotů
                    $("#tableLoading").show();
                    $.ajax({
                        url: "getSlots.php",
                        data: { "dateFrom": dateFrom, "dateTo": dateTo },
                        method: "post",
                        dataType: "json",
                        success: function(response) {
                            enableCells($("#reservations"), response);
                            $("#tableLoading").hide();
                        }
                    });

                    var dateFrom = moment($("#reservations thead tr th[data-day]").first().attr("data-day"));
                    $("#weekInfo").html(dateFrom.week() + ". týden v roce, " + (parseInt(dateFrom.week()) % 2 === 0 ? "sudý" : "lichý") + " týden");
                });
                

                function enableCellsRes(table, dataSet) {
                    table.find("td[data-hour]").each(function() {
                        var columnDate2 = $(this).index();
                        var date2 = table.find("thead tr th:nth-child(" + (columnDate2 + 1) + ")").attr("data-day");
                        $(this).attr("data-date", date2);
                        
                        $(this).find("span").html("-");
                        
                    });

                    $.each(dataSet, function(i, obj) {
                        var date2 = obj.date;
                        var time2 = obj.time;
                        var klient = obj.client;
                        var terapist = obj.personnel;
                        var service = obj.shortcut;

                        if(service == 'VSTUP') {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "'][data-terapist='" + terapist + "']").find("span").html('<b>' + klient + '</b>' + '<br> <FONT COLOR=tomato> (' + service + ')<FONT COLOR=black>');}
                        else if(service == 'KONT') {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "'][data-terapist='" + terapist + "']").find("span").html('<b>' + klient +  '<br> <FONT COLOR=green> (' + service + ') </b><FONT COLOR=black>');}
                        else if(service == 'Ergo-Int') {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "'][data-terapist='" + terapist + "']").find("span").html('<b>' + klient + '</b>' + '<br> <FONT COLOR=blue> (' + service + ')<FONT COLOR=black>');}
                        else {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "'][data-terapist='" + terapist + "']").find("span").html('<b>' + klient + '</b>' + '<br> (' + service + ')');}
                       
                        
                    });
                }                

                function enableCells(table, dataSet) {
                    table.find("td[data-hour]").each(function() {
                        var columnDate = $(this).index();
                        var date = table.find("thead tr th:nth-child(" + (columnDate + 1) + ")").attr("data-day");
                        $(this).attr("data-date", date);

                        $(this).removeClass("green");
                        $(this).addClass("grey");                        
                    });

                    $.each(dataSet, function(i, obj) {
                        var date = obj.date;
                        var time = obj.time;
                        var terapist = obj.person;

                        table.find("tbody td[data-date='" + date + "'][data-time='" + time + "'][data-terapist='" + terapist + "']").removeClass("grey").addClass("green")
                    });
                }
                
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

                function newPeriod(dayCount) {
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
                    });
                    
                    $("#buttonUpdate").click();
                }
                
                $("#reservations tbody tr td").click(function() {
                    var person = $(this).attr("data-terapist");
                    
                    var date = $(this).attr("data-date");
                    var time = $(this).attr("data-time");
                    
                    $.ajax({
                        url: "changeSlotAvailability.php",
                        data: { "person": person, "date": date, "time": time },
                        method: "post",
                        dataType: "text",
                        success: function(response) {
                            if (response === "1") {
                                $("#buttonUpdate").click();
                            } else {
                                alert("Došlo k chybě, kontaktujte vývojáře :-)");
                            }
                        }
                    });
                });
                
                $("#reservations").on("click", "a[data-type='viewReservation']", function(event) {						                                           
                    var th = $("#reservations").closest("th"); 
                    var terapist = $(this).closest("th").attr("data-terapist");
                    var date = $(this).closest("th").attr("data-day");
                    location.href = 'viewReservations.php?date=' + date + '&user='+terapist;
                });
                                
                 $("#buttonUpdate").click();
            });
        </script>
    </body>
</html>
