<?php

include "header.php";

if (isset($_GET["date"])) {
    if (!$date = (new DateTime)->createFromFormat("Y-m-d", $_GET["date"])) {
        $date = (new DateTime());
    }
} else {
    $date = (new DateTime()); 
}

// je-li sobota, je třeba zobrazit následující týden
if (intval($date->format("N")) >= 6) {
    $date->add(new DateInterval("P" . (8 - intval($date->format("N"))) . "D"));
}

$today = new DateTime();
$differenceToFridayThisWeek = 5 - intval($today->format("N"));
if ($differenceToFridayThisWeek >= 0) {
    $fridayThisWeek = (new DateTime($today->format("Y-m-d")))->add(new DateInterval("P" . $differenceToFridayThisWeek . "D"));
} else {
    $fridayThisWeek = (new DateTime($today->format("Y-m-d")))->sub(new DateInterval("P" . abs($differenceToFridayThisWeek) . "D"));
}
$fridayNextWeek = (new DateTime($fridayThisWeek->format("Y-m-d")))->add(new DateInterval("P4W"));
    
$dayOfWeek = intval($date->format("N"));
$differenceFromMonday = $dayOfWeek - 1;
$lastMonday = (new DateTime($date->format("Y-m-d")))->sub(new DateInterval("P" . $differenceFromMonday . "D"));
$displayedFriday = (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P4D"));

$aktualniHodina = intval($today->format("H"));
$hodinaOdKtereLzeRezervovat = $aktualniHodina + 3;
//echo "today: {$today->format('Y-m-d')}, date: {$date->format('Y-m-d')}, fridayThisWeek: {$fridayThisWeek->format('Y-m-d')}, fridayNextWeek: {$fridayNextWeek->format('Y-m-d')}, lastMonday: {$lastMonday->format('Y-m-d')}, displayedFriday: {$displayedFriday->format('Y-m-d')}";

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

            <div class="row" id="rezervace-nadpis">
                Rezervace na fyzioterapii a rekondice<br>
                <div class="underline"></div>
            </div>

            <div class="row" id="rezervace-nad-tabulkou">
                <div class="col-lg-12">
                    Aenean vel massa quis mauris vehicula lacinia. Sed ac dolor sit amet purus malesuada congue. Duis condimentum augue id magna semper rutrum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse nisl. Nunc auctor. Nullam lectus justo, vulputate eget mollis sed, tempor sed magna. Etiam dui sem, fermentum vitae, sagittis id, malesuada in, quam. Nullam sit amet magna in magna gravida vehicula. Aliquam in lorem sit amet leo accumsan lacinia. In enim a arcu imperdiet malesuada. Proin mattis lacinia justo. Nunc auctor. Morbi scelerisque luctus velit. Proin in tellus sit amet nibh dignissim sagittis. Duis pulvinar.
                </div>
            </div>
            
            <div class="row">
                <?php
                    $query = "  SELECT
                                    s.id,
                                    s.name
                                FROM services AS s
                                WHERE EXISTS (SELECT id FROM relationPersonService WHERE service = s.id)
                                ORDER BY s.name";
                    $stmt = $dbh->prepare($query);
                    $stmt->execute();
                    $resultServices = $stmt->fetchAll(PDO::FETCH_OBJ);
                ?>
                <div class="col-lg-6 text-center">
                    <select name="service" class="form-control">
                        <?php foreach($resultServices as $resultService): ?>
                        <option value="<?= $resultService->id ?>"><?= htmlentities($resultService->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-6 text-center">
                    <select name="person" class="form-control">
                        <option value="">Kdokoli</option>
                    </select>
                </div>
            </div>

            <div class="row" id="hlavicka-rezervaci">
                <div class="col-xs-4 text-left">
                    <a href="<?= $absolutePath ?>rezervace/<?= (new DateTime($lastMonday->format("Y-m-d")))->sub(new DateInterval("P7D"))->format("Y-m-d"); ?>">
                        <h4>
                            <span class="glyphicon glyphicon-chevron-left"></span>
                            Předchozí týden
                        </h4>
                    </a>
                </div>
                <div class="col-xs-4 text-center">
                    <a href="<?= $absolutePath ?>rezervace">
                        <h4>
                            Aktuální týden pro objednávání
                        </h4>
                    </a>
                </div>
                <div class="col-xs-4 text-right">
                    <?php
                        if ($fridayNextWeek->format("Y-m-d") !== $displayedFriday->format("Y-m-d")) {
                    ?>
                    <a href="<?= $absolutePath ?>rezervace/<?= (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P7D"))->format("Y-m-d"); ?>">
                        <h4>
                            Následující týden
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </h4>
                    </a>
                    <?php
                        }
                    ?>
                </div>
            </div>

            <table class="table table-bordered" id="reservations">
                <thead>
                    <tr>
                        <th style="vertical-align: middle" class="text-center">
                            <span class="visible-lg visible-md">Čas rezervace</span>
                            <span class="visible-sm visible-xs">Čas</span>
                        </th>
                        <?php
                            $days = array(1 => "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek");
                            $daysShort = array(1 => "Po", "Út", "St", "Čt", "Pá");
                            for ($i = 0; $i < 5; $i++) {
                                $currentDate = (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P" . $i . "D"));

                                $dayName = $days[$currentDate->format("N")];
                                $dayNameShort = $daysShort[$currentDate->format("N")];
                        ?>
                        <th class="text-center" style="width: 15%" data-day="<?= $currentDate->format("Y-m-d") ?>" data-day-formatted="<?= $currentDate->format("j.n.Y") ?>">
                            <span class="visible-lg visible-md visible-sm"><?= $dayName ?></span>
                            <span class="visible-xs"><?= $dayNameShort ?></span>

                            <span class="visible-lg visible-md visible-sm"><?= $currentDate->format("j.n.Y") ?></span>
                            <span class="visible-xs"><small><?= $currentDate->format("j.n.") ?></small></span>
                        </th>
                        <?php
                            }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        for ($hour = Settings::$timeFrom; $hour <= Settings::$timeTo; $hour++) {
                            if (in_array($hour, Settings::$notAllowedTimes)) continue; // pauza na oběd
                            
                            $hourFrom = $hour;
                            $minuteFrom = 0;

                            $hourTo = $hourFrom + 1;
                            $minuteTo = 0;

                            $minute = 0;
                    ?>
                    <tr>
                        <td data-hour="<?= $hour ?>" data-minute="<?= $minute ?>" class="text-center">
                            <?= $hour . ":" . str_pad($minute, 2, "0", STR_PAD_LEFT) . " - " . $hourTo . ":" . str_pad($minuteTo, 2, "0", STR_PAD_LEFT) ?>
                        </td>
                        <?php
                                for ($i = 0; $i < 5; $i++) {
                                    $currentDate = (new DateTime($lastMonday->format("Y-m-d")))->add(new DateInterval("P" . $i . "D"));

                                    $query = "  SELECT
                                                    COUNT(*) AS count
                                                FROM holidays
                                                WHERE
                                                    (
                                                        month = :month1 AND day = :day1 AND year IS NULL
                                                    )
                                                    OR
                                                    (
                                                        month = :month2 AND day = :day2 AND year = :year
                                                    )";
                                    $stmt = $dbh->prepare($query);
                                    $stmt->bindValue(":month1", $currentDate->format("n"), PDO::PARAM_INT);
                                    $stmt->bindValue(":day1", $currentDate->format("j"), PDO::PARAM_INT);
                                    $stmt->bindValue(":month2", $currentDate->format("n"), PDO::PARAM_INT);
                                    $stmt->bindValue(":day2", $currentDate->format("j"), PDO::PARAM_INT);
                                    $stmt->bindValue(":year", $currentDate->format("Y"), PDO::PARAM_INT);
                                    $stmt->execute();
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    //rezervace lze provádět pouze od zítřka do konce příštího týdne
                                    if (
                                            $currentDate->format("Y-m-d") < $today->format("Y-m-d") || 
                                            ($currentDate->format("Y-m-d") === $today->format("Y-m-d") && $hour < $hodinaOdKtereLzeRezervovat)
                                    ) {
                                        $tdClass = "danger";
                                        $tdText = "Obsazeno";
                                    } else if ($currentDate->format("Y-m-d") > $fridayNextWeek->format("Y-m-d")) {
                                        $tdClass = "notYetAllowed";
                                        $tdText = "";
                                    } else if (intval($result["count"]) === 1) {
                                        $tdClass = "danger";
                                        $tdText = "Obsazeno";
                                    } else {

                                        $query = "  SELECT
                                                        COUNT(id) AS count
                                                    FROM reservations
                                                    WHERE
                                                        date = :date AND
                                                        hour = :hour AND
                                                        minute = :minute AND
                                                        active = 1";
                                        $stmt = $dbh->prepare($query);
                                        $stmt->bindValue(":date", $currentDate->format("Y-m-d"), PDO::PARAM_STR);
                                        $stmt->bindParam(":hour", $hour, PDO::PARAM_INT);
                                        $stmt->bindParam(":minute", $minute, PDO::PARAM_INT);
                                        $stmt->execute();

                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        $tdClass = (intval($result["count"]) === 1 ? "danger" : "success");
                                        $tdText = (intval($result["count"]) === 1 ? "Obsazeno" : "Rezervovat");
                                    }
                        ?>
                        <td class="<?= $tdClass ?> text-center" style="vertical-align: middle;">
                            <span class="large-device"><?= $tdText; ?></span>
                            <span class="small-device"><?= mb_substr($tdText, 0, 3); ?></span>
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

            <div class="row" id="rezervace-pod-tabulkou">
                <div class="col-lg-12">
                    Fusce tellus odio, dapibus id fermentum quis, suscipit id erat. Etiam neque. Integer malesuada. Curabitur ligula sapien, pulvinar a vestibulum quis, facilisis vel sapien. Duis viverra diam non justo. Vivamus ac leo pretium faucibus. Sed convallis magna eu sem. Etiam commodo dui eget wisi. Vestibulum fermentum tortor id mi. Curabitur vitae diam non enim vestibulum interdum. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. In convallis. Vestibulum erat nulla, ullamcorper nec, rutrum non, nonummy ac, erat. Etiam sapien elit, consequat eget, tristique non, venenatis quis, ante. Fusce consectetuer risus a nunc. Aenean vel massa quis mauris vehicula lacinia. Integer tempor. Nam quis nulla.
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
                        <h4 class="modal-title">Zadání rezervace</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Datum a čas rezervace: </label>
                            <input class="form-control" type="text" id="dateAndTimeOfReservation" value="" readonly>
                        </div>
                        <div class="form-group">
                            <label for="name">Jméno: </label>
                            <input class="form-control" type="text" name="name" id="name" value="" required>
                        </div>
                        <div class="form-group">
                            <label for="surname">Příjmení: </label>
                            <input class="form-control" type="text" name="surname" id="surname" value="" required>
                        </div>
                        <div class="form-group">
                            <label for="email">E-mailová adresa: </label>
                            <input class="form-control" type="email" name="email" id="email" value="@" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Telefonní číslo: </label>
                            <input class="form-control" type="tel" name="phone" id="phone" value="" required>
                        </div>
                        <div class="form-group">
                            <label for="alert-type">Zaslání zdarma upozornění den předem na nadcházející terapii: </label>
                            <select class="form-control" name="alert-type" id="alert-type">
                                <option value="email">E-mailem</option>
                                <option value="sms">SMS zprávou na mobilní telefon</option>
                                <option value="both">SMS zprávou na mobilní telefon i E-mailem</option>
                                <option value="none">Nezasílat</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="note">Indikativní popis Vašich obtíží: </label>
                            <textarea class="form-control" name="note" id="note"></textarea>
                        </div>
                        <div class="checkbox small">
                            <label>
                                <input type="checkbox" name="personalDetailsAgreement" value="1" required> V souladu se zákonem č. 101/2000 Sb., v platném znění (zákon o ochraně osobních údajů a&nbsp;o&nbsp;změně některých zákonů) souhlasím se zpracováváním osobních údajů správcem Fyzioland s.r.o.
                            </label>
                        </div>
                        <div class="form-group text-xs-center">
                            <div class="g-recaptcha" data-sitekey="6LcR5TQUAAAAAEbcz2NRs8gTzUpwuqwFsEBlt2o2" data-callback="recaptchaCallback"></div>
                        </div>
                        <input type="hidden" id="date" name="date" value="">
                        <input type="hidden" id="hour" name="hour" value="">
                        <input type="hidden" id="minute" name="minute" value="">
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
        $("#reservations td.success").click(function() {
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
                url: "getPeopleForService.php",
                data: { "service": val },
                method: "post",
                dataType: "html",
                success: function(response) {
                    $("select[name='person']").html(response);
                }
            });
        });
        
    });

    recaptchaCallback = function (parameter) {
        $("#recaptcha").val(1);
    };
    
</script>

<?php

require "footer.php";

?>