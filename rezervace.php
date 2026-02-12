<?php

include "header.php";

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
<div class="container-fluid">
    <div class="row" id="rezervace-title">
        <div class="col-md-12" id="title-logo">
            <a href="/" title="Na hlavní stránku">
                <img src="<?= $absolutePath ?>img/titulni-logo.png" title="Fyzioland">
            </a>
        </div>
    </div>        

    <div class="row">
        <div class="col-lg-10 col-lg-offset-1" id="rezervace-telo">
            <?php
                if (isset($_SESSION["messageBox"])) {
            ?>
            
            <!-- Měřicí kód Sklik.cz -->
            <iframe width="0" height="0" frameborder="0" scrolling="no" src="//c.imedia.cz/checkConversion?c=100045609&amp;color=ffffff&amp;v="></iframe>

            <div class="alert <?= $_SESSION["messageBox"]->getClass() ?> in fade text-center">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?= $_SESSION["messageBox"]->getText() ?>
            </div>
            <?php
                } else {
            ?>
            <script type="text/javascript">
                /* <![CDATA[ */
                var seznam_retargeting_id = 57208;
                /* ]]> */
            </script>
            <script type="text/javascript" src="//c.imedia.cz/js/retargeting.js"></script>
            <?php
                }
                unset($_SESSION["messageBox"]);
            ?>
            
            <div class="row" id="proc-si-vybrat-prave-nas" style="margin-top: 10px;">
                <div class="col-md-12 text-center nadpis">
                    Postup pro přihlášení na individuální terapii, masáže a&nbsp;další služby<br>
                    <div class="underline"></div>
                </div>
                <div class="col-md-12 text-center small">
                    <div class="col-md-4">
                        <div class="duvod-proc-nas">
                            <div class="nadpis">
                                1. krok
                            </div>
                            <p>Nejprve vyberte níže v&nbsp;seznamu službu, na kterou se chcete objednat. V&nbsp;kalendáři se Vám zobrazí volné termíny zelenou barvou.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="duvod-proc-nas">
                            <div class="nadpis">
                                2. krok
                            </div>
                            <p>Pokud chcete zobrazit pouze dostupné časy vybraného terapeuta nebo terapeutky, zvolte v&nbsp;dalším seznamu jeho/její jméno. Kalendář s&nbsp;volnými termíny se Vám automaticky aktualizuje a&nbsp;zobrazí volné termíny pouze vybraného terapeuta/terapeutky.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="duvod-proc-nas">
                            <div class="nadpis">
                                3. krok
                            </div>
                            <p>Klikněte v&nbsp;kalendáři na zelené okénko termínu, který si přejete rezervovat a&nbsp;vyplňte krátký formulář pro závazné přihlášení. Po úspěšné rezervaci dostanete od nás potvrzovací e&#8209;mail. </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 vypln"></div>
                <div class="col-md-12 text-center podnadpis">

                </div>
            </div>
        </div>
    </div>
    
    <div class="row modra">
        <div class="col-lg-10 col-lg-offset-1" style="padding-top: 15px;">
            <div class="row" id="rezervace-nadpis">
                Rezervační kalendář<br>
                <div class="underline"></div>
            </div>

            <div class="row">
                <?php
                    $query = "  SELECT
                                    s.id,
                                    s.name
                                FROM services AS s
                                WHERE EXISTS (SELECT id FROM relationPersonService WHERE service = s.id) AND s.active = 1
                                ORDER BY s.order";
                    $stmt = $dbh->prepare($query);
                    $stmt->execute();
                    $resultServices = $stmt->fetchAll(PDO::FETCH_OBJ);
                ?>
                <div class="col-lg-6 text-right">
                    <div class="form-group form-inline">
                        <label>Zvolte službu: </label>
                        <select name="service" class="form-control">
                            <?php foreach($resultServices as $resultService): ?>
                            <option value="<?= $resultService->id ?>"><?= htmlentities($resultService->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6 text-left">
                    <div class="form-group form-inline">
                        <label>Zvolte terapeuta: </label>
                        <select name="person" class="form-control">
                            <option value="">Kdokoli</option>
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
                            Aktuální týden pro objednávání
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
                                for ($i = 0; $i < 7; $i++) {

                            ?>
                            <td class="grey text-center" style="vertical-align: middle;" data-hour="<?= $hour ?>" data-minute="<?= $minuteFrom ?>" data-time="<?= str_pad($hour, 2, "0", STR_PAD_LEFT) ?>:<?= str_pad($minuteFrom, 2, "0", STR_PAD_LEFT) ?>">
                                <span class="large-device">Obsazeno</span>
                                <span class="small-device"></span>
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
           
           
            <div class="row" id="rezervace-nadpis">
                <br>
                Chcete získat přehled Vašich rezervací?<br>
                <div class="underline"></div>
            </div>
            <div class="row" id="proc-si-vybrat-prave-nas" style="margin-top: 0px;">
                <div class="col-md-12 text-center small">
                    <div class="col-md-12" style="vertical-align: middle;">
                            <p>Stačí vyplnit e-mailovou adresu a náš systém Vám zašle přehled všech Vašich budoucích rezervací, u kterých jste uvedli Vaši e-mailovou adresu.</p>
                    </div>     
                    <div class="col-md-12" style="vertical-align: middle;">
                        <form action="<?= $absolutePath ?>sendReservationOverviewToClient" method="post" id="reservationoverviewForm">
                            <input type="hidden" id="recaptcha2" value="0">
                            <div class="col-md-4 col-md-offset-4 text-center">
                                <div class="form-group">
                                    <label for="email">E-mailová adresa</label>
                                    <input type="email" class="form-control" id="emailforoverview" name="emailforoverview" placeholder="E-mailová adresa" value="">                                           
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group text-xs-center">
                                        <div class="g-recaptcha" data-sitekey="6LcR5TQUAAAAAEbcz2NRs8gTzUpwuqwFsEBlt2o2" data-callback="recaptchaCallback2"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-md-offset-4 text-center">
                                <div class="form-group">
                                    <button class="btn btn-primary" type="submit" name="submit" id="submitButton" style="margin-top: 10px;">Zaslat přehled rezervací</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
           
        </div>                                      
    </div>
    
    <div class="row" style="padding: 20px;">
        <div class="col-lg-10 col-lg-offset-1">

            <div class="row" id="proc-si-vybrat-prave-nas" style="margin-top: 0px;">
                <div class="col-lg-12">
                    <div class="col-md-12 text-center nadpis">
                        Další pravidla<br>
                        <div class="underline"></div>
                    </div>
                    <div class="col-md-12 text-center small">
                        <div class="col-md-4">
                            <div class="duvod-proc-nas">
                                <div class="nadpis">
                                    Dostupné rezervace
                                </div>
                                <p>Nejbližší nabízené termíny v on&#8209;line rezervaci jsou vždy na následující den. Ve 20:00 se rezervace na následující den uzavírají. Pokud potřebujete akutně rychlou návštěvu u&nbsp;nás, kontaktujte nás prosím telefonicky a&nbsp;společně pro Vás najdeme vhodný termín.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="duvod-proc-nas">
                                <div class="nadpis">
                                    Nenašli jste pro sebe vhodný termín?
                                </div>
                                <p>Pokud jste nenašli vhodný termín, kontaktujte nás telefonicky nebo e&#8209;mailem, uděláme vše pro to, abychom společně našli volný termín pro Vás v&nbsp;časech, který Vám vyhovuje.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="duvod-proc-nas">
                                <div class="nadpis">
                                    Zrušení rezervace
                                </div>
                                <p>V případě, že potřebujete zrušit již rezervovaný termín dříve než 48&nbsp;hodin dopředu, využijte k&nbsp;tomu odkaz pro zrušení rezervace, který jste obdrželi e&#8209;mailem v&nbsp;potvrzovací zprávě o&nbsp;provedené rezervaci. Následně si můžete v&nbsp;rezervačním kalendáři zarezervovat nový termín. V&nbsp;případě potřeby nám napiště e&#8209;mail nebo zavolejte. Rádi Vám se vším pomůžeme.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 vypln"></div>
                    <div class="col-md-12 text-center podnadpis">

                    </div>
                 </div>
            </div>
        </div>
    </div>

    <div class="modal fade"  id="rezervace-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="<?= $absolutePath ?>rezervaceAction" method="post" id="reservationsForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">
                            Zadání rezervace: 
                            <small style="color: white">
                                služba: <span data-reservation-type="service"></span>, terapeut: <span data-reservation-type="person"></span>
                            </small>
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="name">Datum a čas rezervace: </label>
                                    <input class="form-control" type="text" id="dateAndTimeOfReservation" value="" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"  style="margin-bottom: 0px;">
                                <div class="form-group">
                                    <label for="name">Jméno klienta (*): </label>
                                    <input class="form-control" type="text" name="name" id="name" value="" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" style="margin-bottom: 0px;">
                                    <label for="surname">Příjmení klienta (*): </label>
                                    <input class="form-control" type="text" name="surname" id="surname" value="" required>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-bottom: 15px; margin-top: 0px; font-size: 10pt;">
                            <div class="col-md-12">
                                *) V případě rezervace termínu pro dítě, prosím, uveďte jméno a přijmení dítěte.
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">E-mailová adresa: </label>
                                    <input class="form-control" type="email" name="email" id="email" value="@" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Telefonní číslo: </label>
                                    <input class="form-control" type="tel" name="phone" id="phone" value="" required>
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
                                    <label for="note">Indikativní popis Vašich obtíží: </label>
                                    <textarea class="form-control" name="note" id="note"></textarea>
                                </div>
                            </div>
                        </div>                       
                        <div class="row">
                            <div class="col-md-12">
                                <div class="checkbox small">
                                    <label>
                                        <input type="checkbox" name="personalDetailsAgreement" value="1" required> V souladu s&nbsp;Nařízením Evropského parlamentu a&nbsp;Rady (EU) 2016/679 (GDPR) v&nbsp;platném znění souhlasím se zpracováváním osobních údajů správcem Fyzioland s.r.o.
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group text-xs-center">
                                    <div class="g-recaptcha" data-sitekey="6LcR5TQUAAAAAEbcz2NRs8gTzUpwuqwFsEBlt2o2" data-callback="recaptchaCallback"></div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="date" name="date" value="">
                        <input type="hidden" id="hour" name="hour" value="">
                        <input type="hidden" id="minute" name="minute" value="">
                        <input type="hidden" id="time" name="time" value="">
                        <input type="hidden" id="service" name="service" value="">
                        <input type="hidden" id="personnel" name="personnel" value="">
                        <input type="hidden" id="recaptcha" value="0">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít bez uložení</button>
                        <button type="submit" class="btn btn-primary" name="submit">Odeslat rezervaci</button>
                    </div>
                </div><!-- /.modal-content -->
            </form>
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    
<script>
    $(document).ready(function() {
        
        //grecaptcha.reset();
        $("#recaptcha2").val(0);        
        
        $("#reservations").on("click", "td.success", function() {
            var thisColumn = $(this).index() + 1;
            var firstTdInThisColumn = $(this).closest("table").find("tr:first").find("th:nth-child(" + thisColumn + ")");

            var thisRow = $(this).closest("tr").index() + 1;
            var firstTdOnThisRow = $(this).closest("table").find("tr:nth-child(" + thisRow + ")").find("td:first");

            var date = firstTdInThisColumn.attr("data-day");
            var dateFormatted = firstTdInThisColumn.attr("data-day-formatted");
            var hour = firstTdOnThisRow.attr("data-hour");
            var minute = firstTdOnThisRow.attr("data-minute");
            var timeSpan = $.trim(firstTdOnThisRow.html());

            $("#dateAndTimeOfReservation").val(dateFormatted + " " + timeSpan);
            $("#date").val(date);
            $("#hour").val(hour);
            $("#minute").val(minute);
            $("#time").val($(this).attr("data-time"));
            
            $("span[data-reservation-type='service']").html($("select[name='service'] option:selected").html());
            $("#service").val($("select[name='service']").val());
            $("span[data-reservation-type='person']").html($("select[name='person'] option:selected").html());
            $("#personnel").val($("select[name='person']").val());

            $('#rezervace-modal').modal();
            $('#rezervace-modal').on('shown.bs.modal', function () {
                $("#name").focus();
            });
            $('#rezervace-modal').on('hidden.bs.modal', function () {
                grecaptcha.reset();
                $("#recaptcha").val(0);
            });
        });

        $("form#reservationsForm").submit(function(event) {
            $(".g-recaptcha").removeClass("redBorder");
            if ($("#recaptcha").val() !== "1") {
                $(".g-recaptcha").addClass("redBorder");
                event.preventDefault();
            }
        });
        
        $("form#reservationoverviewForm").submit(function(event) {
            $(".g-recaptcha").removeClass("redBorder");
            if ($("#recaptcha2").val() !== "1") {
                $(".g-recaptcha").addClass("redBorder");
                event.preventDefault();
            }
        });

        $("#openAdminLoginForm").click(function(event) {
            $("#adminLoginForm").show();
            event.preventDefault();
        });

        $("#adminLoginForm button.close").click(function(event) {
            $("#adminLoginForm").hide();
        });
        
        $("#rezervace-modal input[name='phone']").mask("+420 999 999 999");                    
        
        $("select[name='service']").change(function() {
            var val = $(this).val();
            
            $.ajax({
                url: "/getPeopleForService.php",
                data: { "service": val },
                method: "post",
                dataType: "html",
                success: function(response) {
                    $("select[name='person']").html(response);
                }
            });
        });
           
        $("select[name='service'], select[name='person']").change(function() {
            var service = $("select[name='service']").val();
            var person = $("select[name='person']").val();
            var dateFrom = $("#reservations thead tr th[data-day]").first().attr("data-day");
            var dateTo = $("#reservations thead tr th[data-day]").last().attr("data-day");
            
            $("#tableLoading").show();
            $.ajax({
                url: "/getSlots_new3.php",
                data: { "service": service, "person": person, "dateFrom": dateFrom, "dateTo": dateTo },
                method: "post",
                dataType: "json",
                success: function(response) {
                    enableCells($("#reservations"), response);
                    setTimeout( function() { $("#tableLoading").hide(); } , 400);
                    
                }
            });
            
            if (dateFrom === moment().day(1).format("YYYY-MM-DD")) {
                $("#previousWeekButton").hide();
            } else {
                $("#previousWeekButton").show();
            }
            
            if (dateFrom === moment().day(78).format("YYYY-MM-DD")) {
                $("#nextWeekButton").hide();
            } else {
                $("#nextWeekButton").show();
            }
        });
        
        $("select[name='service']").trigger("change");
    });
    
    recaptchaCallback = function (parameter) {
        $("#recaptcha").val(1);
    };
    
    recaptchaCallback2 = function (parameter) {
        $("#recaptcha2").val(1);
    };
    
    function enableCells(table, dataSet) {
        table.find("td[data-hour]").each(function() {
            var columnDate = $(this).index();
            var date = table.find("thead tr th:nth-child(" + (columnDate + 1) + ")").attr("data-day");
            $(this).attr("data-date", date);
            
            $(this).removeClass("success");
            $(this).addClass("grey");
            $(this).find("span.large-device").html("Obsazeno");
        });
        
        $.each(dataSet, function(i, obj) {
            var date = obj.date;
            var time = obj.time;
            
            table.find("tbody td[data-date='" + date + "'][data-time='" + time + "']").removeClass("grey").addClass("success").find("span.large-device").html("Rezervovat");
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
        
        $("select[name='person']").trigger("change");
        
        var dateFrom = $("#reservations thead tr th[data-day]").first().attr("data-day");
        var hostname = window.location.hostname;
        if (hostname.substring(0,3) === "www") {
            history.replaceState(dateFrom, 'Fyzioland - rezervace', 'https://www.fyzioland.cz/rezervace/' + dateFrom);
        } else {
            history.replaceState(dateFrom, 'Fyzioland - rezervace', 'https://fyzioland.cz/rezervace/' + dateFrom);
        }
    }
    
</script>

<?php

require "footer.php";

?>