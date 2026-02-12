<?php

$pageTitle = "REZERVACE (týdenní přehled)";

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";

session_start();

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
    
$dayOfWeek = intval($date->format("N"));
$differenceFromMonday = $dayOfWeek - 1;
$lastMonday = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P" . $differenceFromMonday . "D"));

if ( intval($resultAdminUser->isSuperAdmin) === 1 && !empty($selectedUserCookie)) {
    $selectedUser = $selectedUserCookie;
} else {
    $selectedUser = $resultAdminUser->id;
}

?>

        <?php include "menu.php" ?>

        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h3>TÝDENNÍ KALENDÁŘ OBJEDNÁVEK</h3>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="form-group">
                        <label for="terapist">Terapeut/terapeutka</label>
                        <select name="terapist" id="terapist" class="form-control" style="display: inline-block; width: auto;">
                             <?php
                                    
                                        $query = "  SELECT
                                                        a.id,
                                                        a.displayName
                                                    FROM adminLogin AS a
                                                    WHERE
                                                        a.active = 1 AND
                                                        EXISTS (SELECT id FROM relationPersonService WHERE person = a.id)
                                                    ORDER BY a.displayName";
                                        $stmt = $dbh->prepare($query);
                                    
                                    $stmt->execute();
                                    $resultsUsers = $stmt->fetchAll(PDO::FETCH_OBJ);
                                    
                                ?>
                            <?php foreach ($resultsUsers as $user): ?>
                            <option value="<?= $user->id ?>" <?= $user->id == $selectedUser ? "selected" : "" ?>><?= $user->displayName ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="form-group">
                        <label for="active">Platnost rezervací</label>
                        <select name="active" id="active" class="form-control" style="display: inline-block; width: auto;">                            
                            <option value="1">Platné</option>
                            <option value="0">Zrušené</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row" id = "duvodSelect">
                <div class="col-lg-12 text-center">
                    <div class="form-group">
                        <label for="active">Důvod zrušení rezervací</label>
                        <select name="reason" id="reason" class="form-control" style="display: inline-block; width: auto;">                            
                            <option value="ALL">Všechny</option>
                            <option value="Nemoc klienta">Nemoc klienta</option>
                            <option value="Nemoc terapeuta">Nemoc terapeuta</option>
                            <option value="Klient nedorazil">Klient nedorazil</option>
                            <option value="Termín se klientovi nehodí">Termín se klientovi nehodí</option>
                            <option value="Klient již nemá zájem o služby">Klient již nemá zájem o služby</option>
                            <option value="Organizační důvody na straně FL">Organizační důvody na straně FL</option>
                            <option value="Jiné">Jiné důvody</option>
                        </select>
                    </div>
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
            
            <div id="tableContainer" >
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
                                $days = array(1 => "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle");
                                $daysShort = array(1 => "Po", "Út", "St", "Čt", "Pá", "So", "Ne");
                                for ($i = 0; $i < 7; $i++) {
                                    $currentDate = (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P" . $i . "D"));

                                    $dayName = $days[$currentDate->format("N")];
                                    $dayNameShort = $daysShort[$currentDate->format("N")];
                            ?>
                            <th class="text-center" style="width: 13%" data-day="<?= $currentDate->format("Y-m-d") ?>" data-day-formatted="<?= $currentDate->format("j.n.Y") ?>" data-order="<?= $i+1 ?>">
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
            
            <div class="row">               
                <div class="col-lg-12 text-right">
                    <button type="button" style = "margin-top: 0px;" class="btn btn-info"  style="margin-top: 25px; margin-bottom: 15px;  margin-left:0px;" name="slotsCopy" id="slotsCopyAll">Nastavit volné sloty dle referenčního týdne (22.6.2026) VŠEM TERAPEUTŮM</button>
                    <button type="button" style = "margin-top: 0px;" class="btn btn-secondary"  style="margin-top: 25px; margin-bottom: 15px;  margin-left:0px;" name="slotsCopy" id="slotsCopyTherapist">Nastavit volné sloty dle referenčního týdne (22.6.2026) vybranému terapeutovi</button>
                    <span id="weekInfo" ></span>
                </div>
            </div>
        
        
            <div class="modal fade" tabindex="-1" role="dialog" id="zobrazit-navrhy-rezervaci">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">                            
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>                            
                            <h4 class="modal-title"> Nabídka možných nových rezervací klientů pro daný termín a terapeutku </h4>                            
                        </div>
                        <div class="modal-body text-center">                        
                            <h3>Nabídka možných nových rezervací klientů pro daný termín a terapeutku</h3>
                            <table class="table  table-bordered table-striped" id="table-navrhy-rezervaci">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;" class="text-center">Klient</th>
                                        <th style="width: 20%;" class="text-center">Telefon</th>
                                        <th style="width: 20%;" class="text-center">Email</th>
                                        <th style="width: 10%;" class="text-center">Frekvence</th> 
                                        <th style="width: 10%;" class="text-center">Typ</th>                                         
                                        <th style="width: 10%;" class="text-center">Poslední rezervace</th>
                                        <th style="width: 20%;" class="text-center">Budoucí rezervace</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" class="text-center">Nebyly nalezeny žádné záznamy.</td>
                                    </tr>
                                </tbody>
                            </table>
                            <h6>V reportu se nezobrazují klienti, kteří již v daném týdnu/měsíci měli požadovaný počet rezervací a také klienti, kteří v daném termínu mají zrušenou rezervaci.</h6>
                            <h6>Přiřazení klienta k terapeutovi se provede dle existujících historických i budoucích rezervací (existuje-li alespoň jedna rezervace klienta u daného terapeuta).</h6>
                        </div>
                       
                    </div>
                </div>
            </div>
        </div>
        
        
        
        <script>            
            $(document).ready(function() {                                                                                
                $("select[name='active']").change(function() {
                    $("select[name='terapist']").trigger("change");                     
                 });
                 
                  $("select[name='reason']").change(function() {
                    $("select[name='terapist']").trigger("change");                     
                 });
                 
                $("select[name='terapist']").change(function() {
                    if( $("select[name='active']").val() == 0) {                        
                        var duvod = document.getElementById('reason');                        
                        duvod.disabled = false;
                        //duvod.style.visibility = 'visible';                        
                    } else {                        
                        var duvod = document.getElementById('reason');
                        $("select[name='reason']").val('ALL');
                        duvod.disabled = true;
                        //duvod.style.visibility = 'hidden';                        
                    }
                        
                    var person = $("select[name='terapist']").val();
                    var active = $("select[name='active']").val();
                    var reason = $("select[name='reason']").val();                    
                    var dateFrom = $("#reservations thead tr th[data-day]").first().attr("data-day");
                    var dateTo = $("#reservations thead tr th[data-day]").last().attr("data-day");

                    // výpis klientů, kteří mají aktivní rezervaci
                    $("#tableLoading").show();
                    $.ajax({
                        url: "getReservations.php",
                        data: { "person": person, "dateFrom": dateFrom, "dateTo": dateTo, "active": active, "reason": reason },
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
                        data: { "person": person, "dateFrom": dateFrom, "dateTo": dateTo },
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
                        var service = obj.shortcut;
                        var active = obj.active;
                        
                        if(active == 1) {
                            if(service == 'VSTUP') {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html('<b>' + klient + '</b>' + '<br> <FONT COLOR=tomato> (' + service + ') <FONT COLOR=black> ');}
                            else if(service == 'KONT') {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html('<b>' + klient + '<br> <FONT COLOR=green> (' + service + ') </b> <FONT COLOR=black> ');}
                            else if(service == 'Ergo-Int') {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html('<b>' + klient + '</b> <br> <FONT COLOR=blue> (' + service + ')  <FONT COLOR=black> ');}
                            else {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html('<b>' + klient + '</b>' + '<br> (' + service + ')');}
                        }
                        if(active == 0) {table.find("tbody td[data-date='" + date2 + "'][data-time='" + time2 + "']").find("span").html(klient);}
                       
                        
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

                        table.find("tbody td[data-date='" + date + "'][data-time='" + time + "']").removeClass("grey").addClass("green")
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
                    
                    $("select[name='terapist']").trigger("change");
                }
                
                $("#reservations tbody tr td").click(function() {
                    var person = $("select[name='terapist']").val();
                    var date = $(this).attr("data-date");
                    var time = $(this).attr("data-time");
                    
                    $.ajax({
                        url: "changeSlotAvailability.php",
                        data: { "person": person, "date": date, "time": time },
                        method: "post",
                        dataType: "text",
                        success: function(response) {
                            if (response === "1") {
                                $("select[name='terapist']").trigger("change");
                            } else {
                                alert("Došlo k chybě, kontaktujte vývojáře :-)");
                            }
                        }
                    });
                });                                
                
                $("#reservations").on("click", "a[data-type='viewReservation']", function(event) {						                                           
                    var th = $("#reservations").closest("th"); 
                    var terapist = $("select[name='terapist']").val();                                                                                 
                    var date = $(this).closest("th").attr("data-day")                                 
                    location.href = 'viewReservations.php?date=' + date + '&user='+terapist;
                });
                
                $("#reservations tbody tr td").mousedown(function(event){
                    event.preventDefault();                    
                    if(event.which == 2)
                    {
                        event.preventDefault();
                        var time = $(this).attr("data-time");
                        var date = $(this).attr("data-date");
                        var therapist = $("select[name='terapist']").val(); 
                            
                        $("#table-navrhy-rezervaci tbody tr").remove();
                        $.ajax({
                            url: "get-navrhy-rezervaci.php?_=" + new Date().getTime(),
                            method: "POST",
                            data: {time: time, date:date, therapist:therapist},
                            dataType: "json",
                            success: function(response) {
                                $("#table-navrhy-rezervaci tbody tr").remove();
                                if (response.length > 0) {
                                    $.each(response, function(i, obj) {
                                        var tr = $("<tr></tr>");
                                        $.each(obj, function(key, value) {
                                            var td = $("<td></td>");
                                            if (key === "price") {
                                                td.css("text-align", "right");
                                            }                                            
                                            td.html(value);
                                            tr.append(td);								
                                        });
                                        $("#table-navrhy-rezervaci tbody").append(tr);
                                    });
                                } else {
                                    var tr = $("<tr></tr>");
                                    var td = $("<td colspan='7' class='text-center'></td>");
                                    td.html("Nebyly nalezeny žádné záznamy.");
                                    tr.append(td);
                                    $("#table-navrhy-rezervaci tbody").append(tr);
                                }
                            },
                            beforeSend: function() {
                                var tr = $("<tr></tr>");
                                var td = $("<td colspan='2'></td>");
                                td.html("Probíhá načítání záznamů.");
                                tr.append(td);
                                $("#table-navrhy-rezervaci tbody").append(tr);
                            }
                        });                                                

                    $("#zobrazit-navrhy-rezervaci").modal("show");
                    }
                });                                 
                                               
               $("button[name='slotsCopy']").click(function(event) {
                    if($(this).attr('id') == "slotsCopyAll") {
                        var therapist = null;
                    }
                    if($(this).attr('id') == "slotsCopyTherapist") {
                        var therapist = $("select[name='terapist']").val();
                    }                    
                    var dateFrom = $("#reservations thead tr th[data-day]").first().attr("data-day");
                    var refWeekStart = '2026-06-22';
                    
                    //alert(therapist);
                    
                    $.ajax({
                        url: "slotsCopy.php",
                        data: { "therapist": therapist, "refWeekStart": refWeekStart, "dateFrom": dateFrom },
                        method: "post", 
                        dataType: "text",
                        success: function(response) {
                            $("select[name='terapist']").trigger("change");                    
                        }
                    });                    
                });
                
                $("select[name='terapist']").trigger("change");
                    document.onkeydown = function(e) {
                        if (event.keyCode === 39 && event.altKey) {
                            event.preventDefault();  
                            $("#nextWeekButton").click();
                        }
                        if (event.keyCode === 37 && event.altKey ) {
                             $("#previousWeekButton").click();
                             event.preventDefault(); 
                        }                    
                    }
                });
              
        </script>
    </body>
</html>
