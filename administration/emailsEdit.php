<?php

$pageTitle = "FL - hromadný mailing";

require_once "checkLogin.php";

if ($resultAdminUser->isSuperAdmin !== "1") {
    header("Location: viewGroupReservations");
}

require_once "../header.php";

$query = "  SELECT
                e.id,
                e.subject,
                e.body,
                e.dateSent,
                e.dateToSend,
                e.state
            FROM emails AS e
            WHERE e.id = :id
            ORDER BY dateSent DESC, subject";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":id", $_GET["id"], \PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);

if ($result !== FALSE) {
    $result->sendingTime = (empty($result->dateToSend) ? "" : (new DateTime($result->dateToSend))->format("d.m.Y H:i"));
}


if ( $result === FALSE ) {
    $title = "Vytvoření nového e-mailu";
} else {
    if ($_GET["akce"] === "kopie") {
        $result->id = NULL;
        $title = "Vytvoření kopie e-mailu";
    } else {
        $title = "Editace e-mailu";
    }
}

/* 

 * is Fyzio
IF(
    (SELECT 
        COUNT(r.id) 
        FROM reservations r 
        LEFT JOIN services s ON s.id = r.service 
        WHERE 
            r.client = c.id AND 
            (s.id = 1 OR s.id = 11) AND 
            r.active = 1
    )>0, 
    1, 
    0
) as filter1,
 
* is Ergo
IF(
    (SELECT 
        COUNT(r.id) FROM reservations r 
        LEFT JOIN services s ON s.id = r.service 
        WHERE 
            r.client = c.id AND 
            (s.id = 2 OR s.id = 10) AND 
            r.active = 1
    )>0,
    1, 
    0
) as filter2,
 
 * kroužek MVSP 
 EXISTS
    (SELECT 
        id 
        FROM groupExcercisesParticipants p                                 
        WHERE 
            p.client = c.id AND 
            p.groupExcercise = 2677

    ),
    1, 
    0
) as filter1,
 */

$queryRecipients = 
    "SELECT
        c.id,
        AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
        AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
        AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email,
        c.mailing,
        1 as filter1,
        1 as filter2,
        CASE WHEN er.id IS NULL THEN 0 ELSE 1 END AS isRecipient
    FROM clients AS c
    LEFT JOIN emailsRecipients AS er ON er.emailId = :emailId AND er.recipient = c.id
    WHERE c.mailing = 1
    ORDER BY
        AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'),
        AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')
    -- LIMIT 2000
    LIMIT 1000 OFFSET 1999;
   ";

$stmtRecipients = $dbh->prepare($queryRecipients);
$stmtRecipients->bindParam(":emailId", $result->id, \PDO::PARAM_INT);
$stmtRecipients->execute();
$recipients = $stmtRecipients->fetchAll(PDO::FETCH_OBJ);

?>

<?php include "menu.php" ?>

<div class="container" style="margin-top: 10px;">
    <div class="col-lg-10 col-lg-offset-1">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2><?= $title ?></h2>
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

        <form action="emailsAction.php" method="post">
            <input type="hidden" name="id" value="<?= $result->id ?>">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="subject">Předmět</label>
                        <input type="text" class="form-control" value="<?= htmlentities($result->subject) ?>" id="subject" name="subject" autofocus required="required">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="body">Tělo e-mailu</label>
                        <textarea class="form-control" id="body" name="body" rows="10"><?= htmlentities($result->body) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-center">
                    <h4>Adresáti</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-hover table-striped" id="recipients">
                        <thead>
                            <tr>
                                <th>Jméno a příjmení</th>
                                <th>Filter 1</th>
                                <th>Filter 2</th>
                                <th>E-mailová adresa</th>
                                <th class="text-center">Obesílat</th>
                                <th class="text-center">Označit jako adresáta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recipients as $recipient): ?>
                            <tr data-id="<?= $recipient->id ?>" data-email="<?= $recipient->email ?>" data-filter1="<?= $recipient->filter1 ?>" data-filter2="<?= $recipient->filter2 ?>" data-mailing="<?= $recipient->mailing === "1" ? "1" : "0" ?>">
                                <td data-order="<?= htmlentities($recipient->surname) ?>"><?= trim(htmlentities($recipient->surname . ", " . $recipient->name)) ?></td>
                                <td class="text-center" class="row-check"><?= $recipient->filter1 === "1" ? "ANO" : "-" ?></td>
                                <td class="text-center"><?= $recipient->filter2 === "1" ? "ANO" : "-" ?></td>
                                <td><?= htmlentities($recipient->email) ?></td>
                                <td class="text-center"><?= $recipient->mailing === "1" ? "Ano" : "Ne" ?></td>
                                <td class="text-center" data-search="<?= $recipient->isRecipient === "1" ? "??XX??" : "" ?>"><input type="checkbox" <?= $recipient->isRecipient === "1" ? "checked='true'" : "" ?>></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="button" id="buttonSelectFyzio" class="btn btn-info">Označit všechny F1</button>
                    <button type="button" id="buttonSelectErgo" class="btn btn-info">Označit všechny F2</button>
                    <button type="button" id="buttonSelectMailables" class="btn btn-info">Označit všechny se souhlasem k mailingu</button>
                    <button type="button" id="buttonSelectAll" class="btn btn-info">Označit všechny</button>
                    <button type="button" id="buttonSelectNone" class="btn btn-info">Neoznačit nikoho</button>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-lg-12">
                    <div class="form-group">
                        <label for="sendingTime">Plánované datum a čas odeslání (dd.mm.YYYY hh:mm):</label>
                        <input type="text" class="form-control" id="sendingTime" name="sendingTime" value="<?= $result->sendingTime ?>" placeholder="Nevyplněná hodnota znamená odeslání okamžitě.">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 text-center">
                    <button class="btn btn-success" name="saveButton" value="Koncept">Uložit jako koncept</button>
                </div>
                <div class="col-md-6 text-center">
                    <button class="btn btn-success" name="saveButton" value="K odeslání">Uložit a odeslat</button>
                </div>
            </div>
            
        </form>
    </div>
    
<script>
    $(document).ready(function() {  
        
        var roxyFileman = '/../php/RoxyFileman/index.html'; 
        
        CKEDITOR.replace( 'body', {
            language: 'cs',
            customConfig: '/../js/ckeditor_config.js',
            filebrowserBrowseUrl: roxyFileman,
            filebrowserImageBrowseUrl: roxyFileman+'?type=image',
            removeDialogTabs: 'link:upload;image:upload',
            disallowedContent : 'img{width,height}'
        });

        
        var table = $("#recipients").DataTable({
            "order": [[0, "asc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
            },
            "fixedHeader": true,
            "lengthMenu": [ [10, 25, 100, -1], [10, 25, 100, "Všechny"]  ]
        });
        
        $('#recipients').on( 'init.dt', function () {
            
            var filterButton = $("<button type='button' class='btn btn-info' style='margin-left: 5px;'>Jen zaškrtnuté</button>");
            filterButton.click(function() {
               
                if ($(this).hasClass("active")) {
                    table.search("").draw();
                    $(this).removeClass("active");
                } else {
                    table.search("??XX??").draw();
                    $(this).addClass("active");
                }                                         
            });
            $("#recipients_filter label").after(filterButton);
        } );
        
        $("#buttonSelectMailables").click(function() {
            table.rows().every(function ( rowIdx, tableLoop, rowLoop ) {
                if ( $(this.node()).attr("data-mailing") === "1" ) {
                    $(this.node()).find("input[type='checkbox']").prop('checked', true);
                }
            });
            table.draw();
        });
        
        $("#buttonSelectFyzio").click(function() {
            table.rows().every(function ( rowIdx, tableLoop, rowLoop ) {
                if ($(this.node()).attr("data-mailing") === "1" && $(this.node()).attr("data-filter1") === "1" ) {
                    $(this.node()).find("input[type='checkbox']").prop('checked', true);
                }
            });
            table.draw();
        });
        $("#buttonSelectErgo").click(function() {
            table.rows().every(function ( rowIdx, tableLoop, rowLoop ) {
                if ( $(this.node()).attr("data-mailing") === "1" && $(this.node()).attr("data-filter2") === "1" ) {
                    $(this.node()).find("input[type='checkbox']").prop('checked', true);
                }
            });
            table.draw();
        });
        
        $("#buttonSelectAll").click(function() {
            table.rows().every(function ( rowIdx, tableLoop, rowLoop ) {
                $(this.node()).find("input[type='checkbox']").prop('checked', true);
            });
            table.draw();
        });
        
        $("#buttonSelectNone").click(function() {
            table.rows().every(function ( rowIdx, tableLoop, rowLoop ) {
                $(this.node()).find("input[type='checkbox']").prop('checked', false);
            });
            table.draw();
        });
        
        $("form").submit(function(event) {
            table.rows().every(function ( rowIdx, tableLoop, rowLoop ) {
                var tr = $(this.node());
                if ( tr.find("input[type='checkbox']").first().prop('checked') === true ) {
                    var id = tr.attr("data-id");                    
                    var hidden = $("<input type='hidden' name='recipients[]' value=''>");
                    hidden.val(id);
                    $("form").append(hidden);
                    
                    var email = tr.attr("data-email");                    
                    var hidden = $("<input type='hidden' name='email[]' value=''>");
                    hidden.val(email);
                    $("form").append(hidden);
                }
            });
        });
        
        $("#sendingTime").mask("99.99.9999 99:99");
    });
</script>