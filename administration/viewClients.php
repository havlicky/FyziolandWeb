<?php

$pageTitle = "FL - Přehled Klientů";

require_once "checkLogin.php";

if ($resultAdminUser->isSuperAdmin !== "1") {
    header("Location: viewGroupReservations");
}

require_once "../header.php";


$query = "  SELECT
                c.id,
                AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(c.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(c.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                ROUND(DATEDIFF(now(), c.date)/365,1) as age,
                c.difficulty,
                c.activated,
                c.lastEditDate,
                c.mailing
            FROM clients AS c
            ORDER BY
                AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "'),
                AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "')";
$stmt = $dbh->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
        
        <?php include "menu.php" ?>

        <div class="container-fluid" style="margin-top: 10px;">
            <div class="col-lg-10 col-lg-offset-1">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h2>
                            Přehled klientů 
                        </h2>
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
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-success" id="newClientButton" style="margin-bottom: 20px;">Nový klient</button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered table-hover table-striped" id="clients">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Příjmení</th>
                                    <th>Jméno</th>
                                    <th>Věk</th>
                                    <th>Náročnost</th>
                                    <th>E&#8209;mailová adresa</th>
                                    <th>Obesílat</th>
                                    <th>Telefon</th>
                                    <th class="text-center">Poslední změna</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($results as $result): ?>
                                <tr style="height: 50px;" data-id="<?= $result->id ?>">
                                    <td data-field="id" style="vertical-align: middle; color: grey;">
                                        <span><?= $result->id ?></span>
                                    </td>
                                    <td data-field="surname" style="vertical-align: middle;">
                                        <span><?= $result->surname ?></span>
                                        <a href="#"><span class="glyphicon glyphicon-pencil" style="float: right;"></span></a>
                                    </td>
                                    <td data-field="name" style="vertical-align: middle;">
                                        <span><?= $result->name ?></span>
                                        <a href="#"><span class="glyphicon glyphicon-pencil" style="float: right;"></span></a>
                                    </td>
                                    <td data-field="age" style="vertical-align: middle;">
                                        <span><?= $result->age ?></span>
                                        <a href="#" style="float: right;"></span></a>
                                    </td>
                                    <td data-field="difficulty" style="vertical-align: middle;">
                                        <span><?= $result->difficulty ?></span>
                                        <a href="#" style="float: right;"></span></a>
                                    </td>
                                    <td data-field="email" style="vertical-align: middle;">
                                        <span><?= $result->email ?></span>
                                        <a href="#" style="float: right;"><span class="glyphicon glyphicon-pencil"></span></a>
                                    </td>                                    
                                    <td data-field="mailing" style="vertical-align: middle;" class="text-center">
                                        <input type="checkbox" <?= $result->mailing === "1" ? "checked" : "" ?> value="1">
                                    </td>
                                    <td data-field="phone" style="vertical-align: middle;">
                                        <span><?= $result->phone ?></span>
                                        <a href="#"><span class="glyphicon glyphicon-pencil" style="float: right;"></span></a>
                                    </td>
                                    <td data-field="lastEditDate" class="text-center" style="vertical-align: middle;" data-order="<?= (new DateTime($result->lastEditDate))->format("Y-m-d H:i:s") ?>">
                                        <span><?= (new DateTime($result->lastEditDate))->format("j. n. Y G:i") ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                      
            <div class="modal fade"  id="modal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <form action="clientsCreate" method="post">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Nový klient</h4>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Jméno: </label>
                                            <input class="form-control" type="text" name="name" id="name" value="" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="surname">Příjmení: </label>
                                            <input class="form-control" type="text" name="surname" id="surname" value="" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="padding-top: 0px; padding-bottom: 0px;">
                                    <div class="col-sm-9">
                                        <div class="form-group">
                                            <label for="email">E-mailová adresa: </label>
                                            <input class="form-control" type="email" name="email" id="email" value="">
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label for="send_email">Obesílat: </label>
                                            <select class="form-control" name="send_email">
                                                <option value="1">Ano</option>
                                                <option value="0">Ne</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="phone">Telefonní číslo: </label>
                                            <input class="form-control" type="tel" name="phone" id="phone" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít bez uložení</button>
                                <button type="submit" class="btn btn-primary" name="submit">Uložit nového klienta</button>
                            </div>
                        </div><!-- /.modal-content -->
                    </form>
                </div><!-- /.modal-dialog -->
            </div>
            
            <script>
                $(document).ready(function() {
                    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
                    
                    $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();
                    
                    $("#clients").DataTable({
                        "fixedHeader": true
                    });
                    
                    $("#clients").on("click", "td a", function(event) {
                        event.preventDefault();
                        
                        var prevSpan = $(this).prev();
                        var td = $(this).closest("td");
                        
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
                    
                    $("#clients").on("blur", "td input[type='text']", function() {
                        var text = $(this).val();
                        var span = $("<span></span>");
                        
                        span.text(text);
                        $(this).replaceWith(span);
                    });
                    
                    $("#clients").on("change", "td input", function() {
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
                        
                        //console.log(field + ": " + value);
                        
                        $.ajax({
                            "url": "clientsEdit.php",
                            "method": "post",
                            "data": { "id": id, "field": field, "value": value },
                            "success": function(response) {
                                //console.log(response);
                                td.addClass("success");
                                setTimeout(function() { td.removeClass("success"); }, 1000);
                            }
                        });
                    });
                    
                    $("#newClientButton").click(function() {
                        $("#modal").modal("show");
                    });
                    
                    $("#modal input[name='phone']").mask("+420 999 999 999");
                });

            </script>
        </div>
    </body>
</html>