<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once "../header.php"; 
require_once "../php/class.settings.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$query = "  SELECT                
                q.id,
                q.label,
                q.order,
                q.category
            FROM questions q 
            
            WHERE
                q.active = 1";
			
$stmt = $dbh->prepare($query);
$stmt->execute();
$otazky = $stmt->fetchALL(PDO::FETCH_OBJ);

//$otazkyJson = file_get_contents("otazky.json");
//$otazky = json_decode($otazkyJson, false);

function generateSelect($id, $additionalComment = false) {
    $output = "<select name='{$id}' class='form-control'>";
    $output .= "<option value=''>&lt;Nevybráno&gt;</option>";
    $output .= "<option value='5'>5 - vždy</option>";
    $output .= "<option value='4'>4 - často</option>";
    $output .= "<option value='3'>3 - občas</option>";
    $output .= "<option value='2'>2 - zřídka</option>";
    $output .= "<option value='1'>1 - nikdy</option>";
    $output .= "<option value='0'>0 - nedokáži posoudit</option>";
    $output .= "</option>";
    $output .= "</select>";
    
    return $output;
}

//najdu id rezervace dle hash
$stmt = $dbh->prepare("SELECT id, service FROM reservations WHERE deleteHash = :hash");
$stmt->bindParam(":hash", htmlentities($_GET["hash"]), PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);	 

//zjistím již všechny uložené odpovědi na otázky
$query = "SELECT idq, ans FROM questionnaire where idres = :idres";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":idres", $result->id, PDO::PARAM_INT);
$stmt->execute();
$odpovezeneOtazky = $stmt->fetchALL(PDO::FETCH_OBJ);	 

?>
<style>
    .table th:nth-child(1) {
        width: 60%;
    }
    .selectMaChybu {
        border-color: red;
    }
</style>
<div class="container" >
    <div class="row">
        <?php
            //vstupní
            if ($result->service == '10') { 
            ?>
             <h1>Dotazník senzorického vnímání</h1>
        <?php
            }
            // kontrolní
            if ($result->service == '12') { 
        ?>
             <h1>Dotazník senzorického vnímání - kontrolní šetření</h1>
        <?php
            }
        ?>        
        <hr>
        <p>Prosím, zaškrtněte u každé otázky volbu, která nejlépe vystihuje frekvenci chování Vašeho dítěte v uvedených případech. Prosím zodpovězte všechny uvedené příklady. Pokud nelze, protože jste konkrétní situaci nevěnovali pozornost nebo si nejste jistí, zvolte volbu "nedokáži posoudit". Dotazník je níže rozdělen do několika částí. Prosíme o vyplnění všech otázek ve všech oblastech.</p>
        <p><b>Při vyplňování se prosím řiďte následujícími pravidly: </b><br><br><b>Vždy</b> = 100% času <br><b>Často</b> = cca 75% času <br><b>Občas</b> = cca  50% času <br><b>Zřídka</b> = cca 25% času <br><b>Nikdy</b> = 0% času </p>
        <p>Na vyplnění dotazníku si prosím vyhraďte 30-45 minut. Vaše odpovědi jsou pro nás důležité.</p>        
    </div>
    <div class="row">
        <br>
        <h1>SENZORICKÉ ZPRACOVÁNÍ</h1>
        <hr>
    </div>
     <div class="row">   
        <h2>Sluchové vnímání</h2>
        <hr>
    </div>    
    <table class="table table-bordered table-hover table-striped" id="pokus">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "senzSluch") {
                        continue;
                    }
            ?>
            <tr>                
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Zrakové vnímání</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "senzZrak") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Vestibulární vnímání</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "senzVestibular") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Taktilní vnímání</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "senzTaktilni") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Multismyslové vnímání</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "senzMultismysl") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Chuťové vnímání </h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "senzChut") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">
        <br>
        <h1>MODULACE</h1>
        <hr>
    </div>
    
    <div class="row">        
        <h2>Vnímání hlubokého čití</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "modCiti") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Modulace vestibulárního vnímání</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "modVestibular") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Modulace vnímání motorické aktivity</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "modMotor") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Modulace vnímání ve vztahu k emocionální odpovědi</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "modEmoce") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Chování a emocionální odpověď</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "modChovani") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    
    <div class="row">        
        <h2>Chování</h2>
        <hr>
    </div>
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Otázka</th>
                <th>Odpověď</th>
                <th style="visibility: hidden">uloženo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($otazky as $otazka):
                    if ($otazka->category !== "modChovaniOther") {
                        continue;
                    }
            ?>
            <tr>
                <td style="vertical-align: middle;"><?= $otazka->label ?></td>
                <td><?= generateSelect($otazka->id) ?></td>
                <td style="vertical-align: middle; display: none" class="text-success text-center">uloženo</td>
            </tr>
            <?php
                endforeach;
            ?>
        </tbody>
    </table>
    <div class="row">					
        <div class="col-md-4 col-md-offset-4 text-center">          
            <a href="#" id="odevzdat" name="odevzdat" class="btn btn-primary" type="button" style="margin-top: 10px; margin-bottom: 50px;" >Odeslat dotazník</a>            
        </div>
    </div>
</div>
   
<script> 
    $(document).ready(function() {                
        
        var hash = "<?= htmlentities($_GET["hash"]) ?>";
        $.ajax({
               url: "getQuestions.php",
               data: {                  
                   "hash": hash
               },
               method: "post",
               dataType: "json",
               success: function(response) {
                    $.each(response, function(i, obj) {
                        var idOtazky = obj.idq;
                        var odpoved = obj.ans;    
                        $("select[name='" + idOtazky + "']" ).val(odpoved);
                    });
               },
               error: function(xhr, msg) {                    
                    window.alert("nastala chyba: " + msg + " . Nepokračujte prosím dále ve vyplnění dotazníku. Zdá se, že něco nám nefunguje a Vaše odpovědi by nebyly zaznamenány. Kontaktujte nás prosím na info@fyzioland.cz neho tel. čísle 775 910 749. Za způsobené komplikace se Vám omlouváme.");
               }
        });
        
        
        $("body").on("change", "select", function() {
            var select = $(this);
            var hash = "<?= htmlentities($_GET["hash"]) ?>";
            
            var idOtazky = select.attr("name");
            var odpoved = select.val();            
            
            var nextTd = select.parent().next();
            
            $.ajax({
               url: "addAnswer.php",
               data: {
                   "idq": idOtazky,
                   "ans": odpoved,
                   "hash": hash
               },
               method: "post",
               dataType: "text",
               success: function() {
                    nextTd.fadeIn().delay(1000).fadeOut();
               },
               error: function(xhr, msg) {                    
                    alert("nastala chyba: " + msg + " . Nepokračujte prosím dále ve vyplnění dotazníku. Zdá se, že něco nám nefunguje a Vaše odpovědi by nebyly zaznamenány. Kontaktujte nás prosím na info@fyzioland.cz neho tel. čísle 775 910 749. Za způsobené komplikace se Vám omlouváme.");
               }
            });
        });
    });
    
    $("#odevzdat").on("click", function() {							
        //tady musí proběhnout kontroly, zda je vše vyplněno a pokud ano, tak teprve přesměrovat stránku
        $("select").removeClass("selectMaChybu");
        var emptySelects = $("select").filter(function() { return $(this).val() == ""; });
        if (emptySelects.length > 0) {
            emptySelects.addClass("selectMaChybu");
            alert("Prosím vyplňte odpovědi na všechny otázky. Chybějící odpovědi jsou nyní zvýrazněny červeně. Děkujeme.");
        } else {
            
            var hash = "<?= htmlentities($_GET["hash"]) ?>";
            $.ajax({
               url: "finished.php",
               data: {"hash": hash},
               method: "post",
               dataType: "text",
               success: function() {
                    location.href = 'podekovani.php';
               },
               error: function(xhr, msg) {                    
                    alert("nastala chyba: " + msg + " . Nepokračujte prosím dále ve vyplnění dotazníku. Zdá se, že nám něco nefunguje. Kontaktujte nás prosím na info@fyzioland.cz neho tel. čísle 775 910 749. Za způsobené komplikace se Vám omlouváme.");
               }
            });
        }
    });
</script>

<?php include("../footer.php"); ?>  
    
    