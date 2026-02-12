<?php
$pageTitle = "FL - All Reservations";

require_once "checkLogin.php";
require_once "../header.php";

$today = new DateTime();
$days = array(1 => "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle");

$alsoPassedAll = isset($_GET["alsoPassedAll"]) ? true : false;
$alsoPassed12M = isset($_GET["alsoPassed12M"]) ? true : false;
if ($alsoPassedAll) {
    $dateFrom = '1970-01-01';
} else {
    if ($alsoPassed12M) {
        $date = date("Y-m-d"); 
        $dateFrom =  date("Y-m-d", strtotime("$date - 12month"));
    } else {
    $dateFrom = (new DateTime())->format("Y-m-d");
    }
}

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
                r.note COLLATE utf8_general_ci as note,
                r.internalNote COLLATE utf8_general_ci as internalNote,
                a.displayName,
                a.id as personnel,
                r.source,
                CONCAT(
                    '<div style=\'text-align: center;\'>',
                    '<a href=\'#\'',                                          
                    'title=\'Zobrazit rezervaci terapeuta na celodenním přehledu\' data-type=\'viewReservation\'>',
                    '<span class=\'glyphicon glyphicon-step-backward\'></span>',
                    '</a>',
                    
                    '<a href=\'deleteReservation.php?id=', r.id,
                        '&amp;source=2>\' name=\'deleteReservation\' title=\'Smazat rezervaci\'>',
                        '<span class=\'glyphicon glyphicon-trash\'></span>',                                    
                    '</a>'
                    ) as akce,
                r.code as code,
                r.creationTimestamp
                

                
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE 
                r.active = 1 AND
                r.date >= :dateFrom            
            
            UNION
            
            SELECT
                GEP.id,
                AES_DECRYPT(C.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(C.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(C.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(C.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                GE.date as date,
                SUBSTRING(DAYNAME(GE.date),1,3) as denTydne,
                GE.timeFrom as hour,
                ' ',
                GE.title,
                GEP.note COLLATE utf8_general_ci,
                NULL as internalNote,
                a.displayName,   
                a.id as personnel,
                'web',
                ' ' as akce,
                ' ' as code,
                GEP.registrationDate
                
            FROM groupExcercisesParticipants AS GEP
            
            LEFT JOIN clients as C ON GEP.client = C.id
            LEFT JOIN groupExcercises as GE ON GEP.groupExcercise = GE.id
            LEFT JOIN adminLogin as a ON GE.instructor = a.id
            
            WHERE                 
                GE.date >= :dateFrom                        
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
            Přehled všech rezervací
        </h2>                

        <div class="row" style="margin-bottom: 10px;">
            <div class="col-lg-1 col-lg-offset-10">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassedAll" <?= $alsoPassedAll ? "checked" : "" ?>> Zpětně VŠE
                    </label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="alsoPassed12M" <?= $alsoPassed12M ? "checked" : "" ?>> Zpětně -12M
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
                <table class="table table-bordered table-hover table-striped" id="prehled-allReservations" style="horizontal-align: middle">
                    <thead>
                        <tr>                                                        
                            <th class="text-center" style="vertical-align: middle;">Datum</th>
                            <th class="text-center" style="vertical-align: middle;">Den</th>                            
                            <th class="text-center" style="vertical-align: middle;">Hod</th>                            
                            <th class="text-center" style="vertical-align: middle;">Jméno</th>
                            <th class="text-center" style="vertical-align: middle;">Příjmení</th>
                            <th class="text-center" style="vertical-align: middle;">Pracovník</th>
                            <th class="text-center" style="vertical-align: middle;">Služba</th>
                            <th class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th class="text-center" style="vertical-align: middle;">Email</th>
                            <th class="text-center" style="vertical-align: middle;">Poznámka od klienta</th>                            
                            <th class="text-center" style="vertical-align: middle;">Poznámka interní</th>                            
                            <th class="text-center" style="vertical-align: middle;">Zdroj</th>   
                            <th class="text-center" style="vertical-align: middle;">Vytvořeno</th>
                            <th class="text-center" style="vertical-align: middle;">Kód</th>
                            <th class="text-center" style="vertical-align: middle;">Akce</th>                                                        
                            <th class="text-center" style="vertical-align: middle;">ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $result): ?>
                                <tr data-id="<?= $result->id ?> " data-date="<?= $result->date ?>" data-terapist="<?= $result->personnel ?>">                                    
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j. n. Y") ?></td>                                    
                                    <td class="text-center"><?= $result->denTydne ?></td>
                                    <td class="text-left"><?= $result->hour ?></td>
                                    <td class="text-left"><?= $result->name ?></td>
                                    <td class="text-left"><?= $result->surname ?></td>
                                    <td class="text-left"><?= $result->displayName ?></td>
                                    <td class="text-left"><?= $result->service ?></td>                                    
                                    <td class="text-left"><?= $result->phone ?></td>
                                    <td class="text-left"><?= $result->email ?></td>
                                    <td class="text-left"><?= $result->note ?></td>
                                    <td class="text-left"><?= $result->internalNote ?></td>
                                    <td class="text-left"><?= $result->source ?></td>                                    
                                    <td class="text-left"><?= $result->creationTimestamp ?></td>
                                    <td class="text-left"><?= $result->code ?></td>
                                    <td class="text-left"><?= $result->akce ?></td>
                                    <td class="text-left"><?= $result->id ?></td>                                    
                                    <!--
                                    <td class="text-center">
                                        <a href="#" data-type="viewReservation">
                                        <span class="glyphicon glyphicon-step-backward" title="Zobrazit rezervaci terapeuta na celodenním přehledu"></span></a>
                                        <a href="#" data-type="deleteReservation">
                                        <span class="glyphicon glyphicon-trash" title="Smazat rezervaci"></span></a>
                                    </td>                                    
                                    -->
                                    
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="15" class="text-center">Nenalezeny žádné rezervace.</td></tr>    
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> 


    <script>
        $(document).ready(function () {
            $.fn.modal.Constructor.prototype.enforceFocus = function () {};

            $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();
            
            var prehledSlotu;
            if ($("#prehled-allReservations td").length > 1) {
                prehledSlotu = $("#prehled-allReservations").DataTable({
                    "order": [[0, "asc"]],
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
                        {"width": "8%", "targets": 5},
                        {"width": "9%", "targets": 6},
                        {"width": "6%", "targets": 7},
                        {"width": "6%", "targets": 8},
                        {"width": "7%", "targets": 9},
                        {"width": "3%", "targets": 10},
                        {"width": "4%", "targets": 11},
                        {"width": "3%", "targets": 12},
                        {"width": "5%", "targets": 13},
                    ],
                    "fixedHeader": true,
                    "pageLength": 50
                });
            };                                                                                                       
            
            $("#prehled-allReservations").on("click", "a[data-type='viewReservation']", function(event) {						            
                var tr = $(this).closest("tr");            
                var terapist = tr.attr("data-terapist");
                var date = tr.attr("data-date");            
                location.href = 'viewReservations.php?date=' + date + '&user='+terapist;
            });
            
            $("#prehled-allReservations").on("click", "a[name='deleteReservation']", function(event) {
                if ( !confirm('Skutečně si přejete smazat zvolenou rezervaci?') ) {
                    event.preventDefault();
                } else {
                    if ( confirm('Přejete si klientovi odeslat e-mail s notifikací o zrušené rezervaci?') ) {
                        var newHref = $(this).attr("href") + "&sendEmail=true";
                        $(this).attr("href", newHref);
                    }
                }  
            });
        
            $("#alsoPassedAll").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewAllReservations?alsoPassedAll";
                } else {
                    document.location = "viewAllReservations";
                }
            });
            
            $("#alsoPassed12M").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewAllReservations?alsoPassed12M";
                } else {
                    document.location = "viewAllReservations";
                }
            });
                        
            $.widget.bridge("tlp", $.ui.tooltip); // ošetření konfliktu s widgetem tooltip() v Bootstrap, používáme ten z jQuery UI
        });

    </script>
</div>
</body>
</html>