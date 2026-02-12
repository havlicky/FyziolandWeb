<?php

$pageTitle = "FL - vklady (předplatné)";

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";

session_start();

$alsoZero = isset($_GET["alsoZero"]) ? true : false;

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    $messageBox = new MessageBox();
    
    $messageBox->addText("Na tento pohled nemáte oprávnění.");
    $messageBox->setClass("alert-danger");

    $_SESSION["messageBox"] = $messageBox;
    header("Location: viewReservations");
    die();
}

require_once "../header.php";
include "menu.php" 

?>

<div class="container">
    <div class="col-lg-10 col-lg-offset-1">
        <h2 class="text-center">
            Přehled všech vkladů
        </h2>
    </div>
    
    
    <?php
        //Dotaz na celkovou částku nevyčerpaného kreditu
        $query = " SELECT SUM(               
                    (
                        (SELECT 
                            deposits.amount 
                            FROM deposits WHERE deposits.Id = D.id)							
                              - 
                        (SELECT IFNULL(SUM(visits.prepaid),0) 
                            FROM visits WHERE visits.depositId = D.id)			
                    )) AS totalKvycerpani

            FROM deposits AS D								
            ";
        $stmt = $dbh->prepare($query);
        $stmt->execute();
        $resultKvycerpani = $stmt->fetchAll(PDO::FETCH_OBJ);
    
        //Dotaz na celkovou částku nezaplaceného kreditu
        $query = " SELECT SUM(deposits.amount) as notPaid FROM deposits WHERE paymentDate IS NULL";					
        $stmt = $dbh->prepare($query);
        $stmt->execute();
        $resultNotPaid = $stmt->fetchAll(PDO::FETCH_OBJ);
    ?>		
	
    <div class="row">
        <div class="col-md-2">
        </div>
        <div class="col-md-2">
        </div>		
        <div class="col-md-2">                        
            <table class="table table-bordered table-striped" id="TotalCredit">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 100%;">Nezaplaceno</th>						
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($resultNotPaid) > 0): ?>
                        <?php foreach ($resultNotPaid as $result): ?>                                
                            <tr data-id="<?= $result->id ?>">                                    
                                <td class="text-center" data-order="<?= $result->notPaid ?>"><?= number_format($result->notPaid, 0, ",", " ") ?></td>									
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="1" class="text-center">Vše zaplaceno.</td></tr>    
                    <?php endif; ?>                    														
                </tbody>
            </table>
        </div>
        <div class="col-md-2">                        
            <table class="table table-bordered table-striped" id="TotalCredit">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 100%;">K dočerpání</th>						
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($resultKvycerpani) > 0): ?>
                        <?php foreach ($resultKvycerpani as $result): ?>                                
                            <tr data-id="<?= $result->id ?>">                                    
                                <td class="text-center" data-order="<?= $result->totalKvycerpani ?>"><?= number_format($result->totalKvycerpani, 0, ",", " ") ?></td>									
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="1" class="text-center">Nenalezeny žádné předplacené vklady a jejich čerpání.</td></tr>    
                    <?php endif; ?>                    														
                </tbody>
            </table>
            <br>
        </div>		
    </div>

    <?php
    $query = " SELECT
                D.id,
                D.date,
                D.amount,
                (
                    (SELECT 
                        deposits.amount 
                        FROM deposits WHERE deposits.Id = D.id)							
                                             - 
                    (SELECT IFNULL (SUM(visits.prepaid),0) 
                        FROM visits WHERE visits.depositId = D.id)			
                ) AS credit,
                CONCAT (AES_DECRYPT(C.surname, '" . Settings::$mySqlAESpassword . "') , ' ', AES_DECRYPT(C.name,'" . Settings::$mySqlAESpassword . "')) as vkladatel,
                (SELECT GROUP_CONCAT(CONCAT(AES_DECRYPT(clients.surname, '" . Settings::$mySqlAESpassword . "'),' ', AES_DECRYPT(clients.name, '" . Settings::$mySqlAESpassword . "')) ORDER BY 1 ASC SEPARATOR ', ')
                    FROM relationdepositsallowedclients 				 
                    LEFT JOIN clients ON clients.id = relationdepositsallowedclients.clientId				 
                    WHERE DepositId = D.id) AS allowedClients,

                al.displayName as userSurname,
                CONCAT(SUBSTRING(D.note,1,80),'...') as note,
                D.paymentDate,
                D.lastEditDate

            FROM deposits AS D

            LEFT JOIN clients AS C ON C.id = D.clientId			            
            LEFT JOIN adminLogin AS al ON al.id = D.userId

            WHERE 

            (
                (SELECT 
                    deposits.amount 
                    FROM deposits WHERE deposits.Id = D.id)							
                                         - 
                (SELECT IFNULL (SUM(visits.prepaid),0) 
                    FROM visits WHERE visits.depositId = D.id)			
            )> IF(:alsoZero = true, -100000, 0)
            
           ";

    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":alsoZero", $alsoZero, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    ?>		

    <div class="row" style="margin-bottom: 10px;">
        <div class="col-lg-3 col-lg-offset-9 ">
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="alsoZero" <?= $alsoZero ? "checked" : "" ?>> Včetně vkladů s nulovým zůstatkem
                </label>
            </div>
        </div>
    </div> 
    
    <div class="row">
        <div class="col-md-12">                        
            <table class="table table-bordered table-striped" id="tableDeposits">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 8%;">Datum záznamu</th>  
                        <th style="width: 8%;">Zaplaceno dne</th>						
                        <th style="width: 5%;">Předplatné</th>
                        <th style="width: 5%;">Zbývá</th>									
                        <th style="width: 13%;">Vkladatel</th>						
                        <th style="width: 15%;">Oprávněné osoby</th>						
                        <th style="width: 10%;">Zadal</th>
                        <th style="width: 16%;">Poznámka</th>
                        <th style="width: 10%;">LastEditDate</th>
                        <th style="width: 5%;">Akce</th>
                    </tr>
                </thead>
                <tbody>					
                    <?php if (count($results) > 0): ?>
                        <?php foreach ($results as $result): ?>                                
                            <tr data-id="<?= $result->id ?>">
                                <td class="text-center"><?= $result->id ?></td>
                                <td class="text-center" data-order="<?= $result->date ?>"><?= (new DateTime($result->date))->format("j. n. Y") ?></td>
                                <td class="text-center" data-order="<?= $result->paymentDate ?>"><?= empty($result->paymentDate) ? "<b>Čekáme na platbu</b>" : (new DateTime($result->paymentDate))->format("j. n. Y") ?></td>
                                <td class="text-right"><?= number_format($result->amount, 0, ",", " ") ?></td>
                                <td class="text-right"><?= number_format($result->credit, 0, ",", " ") ?></td>                                    
                                <td class="text-left"><?= $result->vkladatel ?></td>
                                <td class="text-left"><?= $result->allowedClients ?></td>
                                <td class="text-left"><?= $result->userSurname ?></td>
                                <td class="text-left"><?= $result->note ?></td>								
                                <td class="text-center" data-order="<?= $result->lastEditDate ?>"><?= empty($result->lastEditDate) ? NULL : (new DateTime($result->lastEditDate))->format("j. n. Y H:s") ?></td>
                                <td class="text-center" ><a href="#" data-type="viewCerpani"><span class="glyphicon glyphicon-list" title="Zobrazit detail čerpání kreditu"></span></a></td>                                    
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center">Nenalezeny žádné předplacené vklady a jejich čerpání.</td></tr>    
                    <?php endif; ?>                    														
                </tbody>
            </table>
        </div>
    </div>  
</div>

<!-- modální okno na přehled čerpání depozita-->
<div class="modal fade" tabindex="-1" role="dialog" id="cerpani-modal">
    <div class="modal-dialog modal-lg" role="document">                    
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title" id="myModalLabel">Přehled čerpání vkladu</h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped" id="tableDeposits">
                            <thead>
                                <tr>                                    
                                    <th style="width: 10%;">Datum</th>
                                    <th style="width: 20%;">Čerpal</th>
                                    <th style="width: 25%;">Cvičení</th>
                                    <th style="width: 10%;">Částka</th>
                                    <th style="width: 15%;">Instruktor</th>
                                    <th style="width: 10%;">Zbylý kredit</th>
                                    <th style="width: 20%;">ID čerpání</th> 								
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center">Nebylo nalezeno žádné čerpání vkladu.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>                
            </div>
        </div>        
    </div>
</div>
        
<script>
    $(document).ready(function() {
        var prehledDeposit;
        if ($("#tableDeposits td").length > 1) {
            prehledDeposit = $("#tableDeposits").DataTable({                    
                "order": [[4, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
                },
                "responsive": false,                    
                "fixedHeader": true,
                "pageLength": 100
            });
        };
        
        $("#tableDeposits").on("click", "a[data-type='viewCerpani']", function(event) {           
            var tr = $(this).closest("tr");
            var id = tr.attr("data-id");
            		
            $.ajax({
                url: "getCerpani.php?_=" + new Date().getTime(),
                method: "POST",
                data: {id: id},
                dataType: "json",
                success: function(response) {
                    $("#cerpani-modal tbody tr").remove();
                    if (response.length > 0) {
                        $.each(response, function(i, obj) {
                            var tr = $("<tr></tr>");
                            $.each(obj, function(key, value) {
                                var td = $("<td></td>");
                                if (key === "prepaid") {
                                    td.css("text-align", "right");
                                }
                                if (key === "Credit") {
                                    td.css("text-align", "right");
                                }
                                td.html(value);
                                tr.append(td);
                            });
                            $("#cerpani-modal tbody").append(tr);
                        });
                    } else {
                        var tr = $("<tr></tr>");
                        var td = $("<td colspan='7' class='text-center'></td>");
                        td.html("Nebylo nalezeno žádné čerpání vkladu.");
                        tr.append(td);
                        $("#cerpani-modal tbody").append(tr);
                    }
		
                    $("#cerpani-modal").modal("show");
                }
            });
            event.preventDefault();
        });
        
        $("#alsoZero").change(function() {
                if ($(this).is(":checked")) {
                    document.location = "viewAllDeposits?alsoZero";
                } else {
                    document.location = "viewAllDeposits";
                }
            });
        
    });        
</script>
</body>
</html>
