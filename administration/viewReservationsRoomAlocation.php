<?php

$pageTitle = "FL - MÍSTNOSTI";

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
                    <h3>DENNÍ KALENDÁŘ OBJEDNÁVEK - ALOKACE MÍSTNOSTÍ</h3>
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
                    <a href="viewReservationsRoomAlocation.php?date=<?= (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P1D"))->format("Y-m-d"); ?>">
                        <h4>
                            <span class="glyphicon glyphicon-chevron-left"></span>
                            Předchozí den
                        </h4>
                    </a>
                </div>
                <div class="col-xs-4 text-center">
                     <a href="viewReservationsRoomAlocation.php">
                        <h4>
                            Aktuální den
                        </h4>
                    </a>
                </div>
                <div class="col-xs-4 text-right">
                    <a href="viewReservationsRoomAlocation.php?date=<?= (new DateTime($date->format("Y-m-d")))->add(new DateInterval("P1D"))->format("Y-m-d"); ?>">
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
                                
                                $datum =  $date->format("Y-m-d");  
                                $query = "  SELECT 
                                            al.displayName, 
                                            al.id,
                                            al.shortcut,
                                             (SELECT 
                                                SUM(
                                                        (SELECT c.difficulty FROM clients c 
                                                            WHERE (c.id = r.client OR (r.client is null and (r.phone = c.phone OR r.email = c.email))) 
                                                            ORDER BY c.difficulty DESC
                                                            LIMIT 1
                                                        )
                                                    )
                                              FROM reservations r 
                                              
                                              WHERE 
                                                r.date = :date AND 
                                                r.personnel = al.id AND 
                                                r.active = 1
                                                
                                              ) as difficulty
                                            FROM adminLogin al

                                            WHERE
                                                (al.roomAllocation = 1) AND
                                                al.active = 1                
                                            ORDER BY al.orderRank
                                            ";
                                $stmt = $dbh->prepare($query);
                                $stmt->bindParam(":date", $datum, PDO::PARAM_STR);
                                $stmt->execute();
                                $results = $stmt->fetchAll(PDO::FETCH_OBJ);
                            
                                $days = array(1 => "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle");
                                $daysShort = array(1 => "Po", "Út", "St", "Čt", "Pá", "So", "Ne");
                                                                
                                foreach ($results as $result) {
                                    
                                    
                            ?>
                            <th class="text-center" data-therapist ="<?= $result->id ?>" data-order="<?= $i+1 ?>">
                                <span class="visible-lg visible-md visible-sm"><?= $result->displayName ?>  <b>[<?= $result->difficulty?>] </b></span>
                                <span class="visible-xs"><?= $result->shortcut ?> <b>[<?= $result->difficulty?>] </b></span>                                
                                
                                <a href="#" data-type="viewReservation">
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
                            <td class="context-menu-one grey text-center" style="vertical-align: middle;  cursor: pointer;" data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>" data-time="<?= str_pad($hour, 2, "0", STR_PAD_LEFT) ?>:<?= str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>">
                                <span class=""></span>
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
                <h5><b>Legenda</b>: žlutá - TV; fialová - SN; modrá - GM (1.patro); hněda - </h5>
            </div>                        
        </div>

        <!-- modální dialog pro zadání úkolu-->
        <div class="modal fade" tabindex="-1" role="dialog"  data-backdrop="static" id="zadat-task-modal">
            <div class="modal-dialog" role="document">
                <form action="php/addFyzio.php" method="post">
                    <input type="hidden" name="taskId" id="taskId" value="">
                    <input type="hidden" name="date" id="date" value=""><!-- comment -->
                    <input type="hidden" name="person" id="person" value=""><!-- comment -->
                    <input type="hidden" name="time" id="time" value=""><!-- comment -->
                    
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h3 class="modal-title" id="myModalLabel">Zadání úkolu</h3>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="modal-task-plan">Plán</label>
                                        <textarea class="form-control" rows="5" id="modal-task-plan" name="modal-task-plan" data-form="true" placeholder="">  </textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="modal-task-reality">Skutečnost</label>
                                        <textarea class="form-control" rows="5" id="modal-task-reality" name="modal-task-reality" data-form="true" placeholder="">  </textarea>
                                    </div>
                                </div>                        
                            </div>                    																			                            
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                            <button type="button" class="btn btn-success" name="updateTask" id="updateTask">Uložit hodnoty</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.contextMenu.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.contextMenu.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.ui.position.js"></script>

        <script>
            $(document).ready(function() {
                
                $(function() {
                    $.contextMenu({
                        selector: '.context-menu-one', 
                        callback: function(key, options) {
                            var room = key;
                            var time = $(this).attr("data-time");
                            var date = $("#dateFormatted").val();
                            var person = $(this).attr("data-therapist");
                                                         
                             //window.console && console.log(room) || alert(room); 
                             //window.console && console.log(date) || alert(date); 
                             //window.console && console.log(time) || alert(time); 
                             //window.console && console.log(person) || alert(person);                                      
                            
                            $.ajax({
                                url: "changeRoom.php",
                                data: { "person": person, "date": date, "time": time, "room": room },
                                method: "post",
                                dataType: "text",
                                success: function(response) {
                                    if (response === "1") {
                                        //$("select[name='therapist']").trigger("change");
                                        $("input[name='date']").trigger("change");
                                    } else {
                                        alert("Došlo k chybě, kontaktujte vývojáře :-)");
                                    }
                                }
                            });
                                      
                            
                        },
                        items: {
                            "TV": {name: "TV přízemí"},
                            "TV_2": {name: "TV-2 horní patro"},
                            "SN": {name: "SN Snoezelen"},
                            "GM": {name: "GM Grafomotorika"},
                            "FYZ": {name: "FYZ Fyzioterapie"},
                            "sep1": "---------",
                            "": {name: "Žádná", icon: function(){
                                return 'context-menu-icon context-menu-icon-quit';
                            
                            }},
                            "sep12": "---------",
                            "TV_pod": {name: "TV přízemí + podoskop"},
                            "TV_2_pod": {name: "TV-2 horní patro + podoskop"},
                            "SN_pod": {name: "SN Snoezelen + podoskop"},
                            "GM_pod": {name: "GM Grafomotorika + podoskop"},
                            "FYZ_pod": {name: "FYZ Fyzioterapie + podoskop"}
                        }                        
                    });                                                              
                });
                
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
                    window.location = "viewReservationsRoomAlocation.php?date=" + $("#dateFormatted").val();
                });
                
                var date = $("input[name='dateFormatted']").val();                                        
                //location.href = 'viewReservationsRoomAlocation.php?date=' + date;
                //zde bych potřeboval nastavit proměnnou $date na hodnotu z dateFormatted, jde to?
                               
                $("#tableLoading").show();
                
                // výpis klientů, kteří mají rezervaci a příznak roomAllocation = 1
                $.ajax({
                    url: "getReservationsRoom.php",
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
                        var therapist = table.find("thead tr th:nth-child(" + (column + 1) + ")").attr("data-therapist");                        
                                                
                        $(this).attr("data-therapist", therapist);
                        //$(this).find("span").html("-");
                        
                    });

                    $.each(dataSet, function(i, obj) {                                               
                        var therapist = obj.personnel;                        
                        var time = obj.time;
                        var klient = obj.client;
                        var service = obj.service;
                        var difficulty = obj.difficulty;
                        var type = obj.type;
                        
                        var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            
                            if (type==="task") {
                                // to znamená, že se jedná o úkol a nikoliv rezervaci
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' <small> <FONT color = gray>' + service + '</FONT></small>');
                            } else {                                 
                                if(service == 'Ergo vstup' || service == 'ERGO-kont' || service == 'Ergo-Int-děti') {table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html('<b>' + klient + '</b> [' + difficulty +  ']' + '<br> <FONT COLOR=tomato> (' + service + ') <FONT COLOR=black>' + span_Text);}
                                else {table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html('<b>' + klient + '</b> [' + difficulty +  ']' + '<br> (' + service + ')' + span_Text);}
                            }
                        
                    });
                }
        
                // výpis dostupných slotů                    
                    $.ajax({
                        url: "getSlotsRoom.php",
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
                        var therapist = table.find("thead tr th:nth-child(" + (column + 1) + ")").attr("data-therapist");                        

                        $(this).attr("data-therapist", therapist);
                        $(this).removeClass("green");
                        $(this).addClass("grey");                        
                    });

                    $.each(dataSet, function(i, obj) {
                        var therapist = obj.person;                        
                        var time = obj.time;                        
                        var room = obj.room; 
                        
                        table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").removeClass("grey").addClass("green");
                        
                        if (room === "TV") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#eee190";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' TV');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' TV');
                            }
                        }                          
                        if (room === "TV_pod") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#eee190";
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.color = "#c71e1e";
                            
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' TV-POD');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' TV-POD');
                            }
                        }  
                        if (room === "TV_2") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#e5a98a";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' TV-2');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' TV-2');
                            }
                        } 
                        if (room === "TV_2_pod") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#e5a98a";
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.color = "#c71e1e";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' TV-2-POD');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' TV-2-POD');
                            }
                        } 
                        if (room === "SN") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#c5aeda";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' SN');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' SN');
                            }
                        } 
                        if (room === "SN_pod") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#c5aeda";
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.color = "#c71e1e";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' SN-POD');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' SN-POD');
                            }
                        } 
                        if (room === "GM") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#6fbbf6";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' GM');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' GM');
                            }
                        }  
                        if (room === "GM_pod") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#6fbbf6";
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.color = "#c71e1e";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' GM-POD');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' GM-POD');
                            }
                        }                        
                        if (room === "FYZ") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#b3bab0";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' FYZ');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' FYZ');
                            }
                        }  
                        if (room === "FYZ_pod") {
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.backgroundColor = "#b3bab0";
                            table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']")[0].style.color = "#c71e1e";
                            var span_Text = table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html();                            
                            if (span_Text==="") {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(' FYZ-POD');
                            } else {
                                table.find("tbody td[data-therapist='" + therapist + "'][data-time='" + time + "']").find("span").html(span_Text + ' FYZ-POD');
                            }
                        }
                    });
                }                                                               
                
                $("#reservations tbody tr td").click(function() {
                                                          
                    var date = $("input[name='dateFormatted']").val();
                    var person = $(this).attr("data-therapist");
                    var time = $(this).attr("data-time");
                    
                    
                    $("#date").val(date);
                    $("#person").val(person);
                    $("#time").val(time);
                    $("#plan").val('');
                    $("#reality").val('');

                    $.ajax({
                        url: "getTask.php",
                        data: {  "person": person, "date": date, "time": time },
                        method: "post",
                        dataType: "json",
                        success: function(response) {
                            $("#modal-task-plan").val(response.plan);                            
                            $("#modal-task-reality").val(response.reality);
                            $("#taskId").val(response.id);
                            $("#zadat-task-modal").modal("show");
                            const textarea = document.getElementById("modal-task-plan");
                            textarea.focus();
                            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
                        }
                    });
                });
                
                $("#updateTask").on("click", function() {			
                    var date =  $("#date").val();
                    var person =  $("#person").val();
                    var time =  $("#time").val();
                    var id = $("#taskId").val();
                    var plan = $("#modal-task-plan").val();
                    var reality = $("#modal-task-reality").val();
                    
                    $.ajax({
                        url: "changeTask.php",
                        data: { "id": id, "person": person, "date": date, "time": time, "plan": plan, "reality": reality },
                        method: "post",
                        dataType: "text",
                        success: function(response) {
                            if (response === "1") {                                
                                $("input[name='date']").trigger("change");
                            } else {
                                alert("Došlo k chybě, kontaktujte prosím vývojáře");
                            }
                        }
                    });
		});
                
                $("#reservations").on("click", "a[data-type='viewReservation']", function(event) {						                                           
                    var th = $("#reservations").closest("th"); 
                    var therapist = $(this).closest("th").attr("data-therapist");
                    var date = $("input[name='dateFormatted']").val();  
                    location.href = 'viewReservations.php?date=' + date + '&user='+therapist;
                });
                
               
              
                //$("input[name='date']").trigger("change");
            });
        </script>
    </body>
</html>