<?php
$pageTitle = "FL - CancelledReservations";

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
        $dateFrom =  date("Y-m-d", strtotime("$date - 365day"));
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
                r.hour,
                r.minute,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') AS timeFrom,                
                s.name AS service,
                r.service as serviceId,
                r.note,
                r.internalnote,
                r.creationTimestamp,
                r.source,
                r.deleteTimestamp,
                CASE WHEN r.deleteUser IS NOT NULL 
                        THEN (SELECT displayName FROM adminLogin WHERE adminLogin.id = r.deleteUser)
                        ELSE 'Klient'
                END as deleteUser,
                r.deleteReason,
                r.deleteHash,
                a.displayName
                
            FROM reservations AS r
            LEFT JOIN services AS s ON s.id = r.service
            LEFT JOIN adminLogin AS a ON a.id = r.personnel
            WHERE 
                r.active = 0 AND
                r.date >= :dateFrom
            ORDER BY r.date, r.hour, r.minute";
$stmt = $dbh->prepare($query);
$stmt->bindValue(":dateFrom", $dateFrom, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
<div class="container-fluid" id="administrace-rezervaci">
    
    <?php include "menu.php" ?>

    <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            Přehled zrušených rezervací
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
                <table class="table table-bordered table-hover table-striped" id="prehled-cancelledReservations" style="horizontal-align: middle">
                    <thead>
                        <tr>                                                        
                            <th class="text-center" style="vertical-align: middle;">Datum</th>
                            <th class="text-center" style="vertical-align: middle;">Den</th>
                            <th class="text-center" style="vertical-align: middle;">Čas</th>                            
                            <th class="text-center" style="vertical-align: middle;">Jméno</th>
                            <th class="text-center" style="vertical-align: middle;">Příjmení</th>
                            <th class="text-center" style="vertical-align: middle;">Pracovník</th>
                            <th class="text-center" style="vertical-align: middle;">Služba</th>
                            <th class="text-center" style="vertical-align: middle;">Telefon</th>
                            <th class="text-center" style="vertical-align: middle;">Email</th>
                            <th class="text-center" style="vertical-align: middle;">Poznámka od klienta</th>   
                            <th class="text-center" style="vertical-align: middle;">Interní poznámka</th>   
                            <th class="text-center" style="vertical-align: middle;">Vytvořeno</th>
                            <th class="text-center" style="vertical-align: middle;">Zdroj</th>
                            <th class="text-center" style="vertical-align: middle;">ID</th>
                            <th class="text-center" style="vertical-align: middle;">Akce</th>
                            <th class="text-center" style="vertical-align: middle;">Důvod smazání</th>
                            <th class="text-center" style="vertical-align: middle;">Okamžik smazání</th>
                            <th class="text-center" style="vertical-align: middle;">Smazal</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $result): ?>
                                <tr data-id="<?= $result->id ?>">                                                                        
                                    <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j. n. Y") ?></td>                                    
                                    <td class="text-center"><?= $result->denTydne ?></td>
                                    <td class="text-center"><?= $result->timeFrom ?></td>
                                    <td class="text-left"><?= $result->name ?></td>
                                    <td class="text-left"><?= $result->surname ?></td>
                                    <td class="text-left"><?= $result->displayName ?></td>
                                    <td class="text-left"><?= $result->service ?></td>                                    
                                    <td class="text-left"><?= $result->phone ?></td>
                                    <td class="text-left"><?= $result->email ?></td>
                                    <td class="text-left"><?= $result->note ?></td>
                                    <td class="text-left"><?= $result->internalnote ?></td>
                                    <td class="text-left"><?= $result->creationTimestamp ?></td>
                                    <td class="text-left"><?= $result->source ?></td>
                                    <td class="text-left"><?= $result->id ?></td>
                                    <td class="text-left">
                                        <?php if ($result->serviceId === "10" || $result->serviceId === "12"): ?>
                                        <a href="dotaznikReadOnly.php?hash=<?= $result->deleteHash ?>" name="viewEntryQuest" title="Zobrazit vstupní/kontrolní dotazník" target="_blank">  
                                            <span class="glyphicon glyphicon-list-alt"></span>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-left"><?= $result->deleteReason ?></td>
                                    <td class="text-left"><?= $result->deleteTimestamp ?></td>
                                    <td class="text-left"><?= $result->deleteUser ?></td>
                                                                        
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                <tr><td colspan="11" class="text-center">Nenalezeny žádné rezervace.</td></tr>    
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
            if ($("#prehled-cancelledReservations td").length > 1) {
                prehledSlotu = $("#prehled-cancelledReservations").DataTable({
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
                        {"width": "7%", "targets": 10},
                        {"width": "5%", "targets": 11},
                        {"width": "4%", "targets": 12},
                        {"width": "4%", "targets": 13},
                        {"width": "4%", "targets": 14},
                        {"width": "4%", "targets": 15},
                        {"width": "5%", "targets": 16},
                        {"width": "8%", "targets": 17},
                    ],
                    "fixedHeader": true,
                    "pageLength": 50
                });
            };                                                                                                       
            
            $("#alsoPassedAll").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewCancelledReservations?alsoPassedAll";
                } else {
                    document.location = "viewCancelledReservations";
                }
            });
            
            $("#alsoPassed12M").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewCancelledReservations?alsoPassed12M";
                } else {
                    document.location = "viewCancelledReservations";
                }
            });
                        
            $.widget.bridge("tlp", $.ui.tooltip); // ošetření konfliktu s widgetem tooltip() v Bootstrap, používáme ten z jQuery UI
        });

    </script>
</div>
</body>
</html>