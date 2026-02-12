<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once "../header.php";
require_once "../php/class.settings.php";
require_once "../php/PHPMailer/PHPMailer.php";
require_once "../php/PHPMailer/Exception.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}          

//dotaz na již vyplněná data o klientovi
$query = "  SELECT                
                AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "') AS name,
                AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') AS email,
                AES_DECRYPT(r.phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                r.city,
                r.street,
                r.zip,
                CONCAT(DAY(r.birthday), '. ', MONTH(r.birthday), '. ', YEAR(r.birthday)) AS birthday,
                r.birthday AS birthdayFormatted,
                r.birthnumber, 
                r.sex,
                r.LRname,
                r.LRsurname,
                r.LRemail,
                r.LRphoneFather,
                r.LRphoneMother,
                r.LRcity,
                r.LRstreet,
                r.LRzip,
                r.insuranceCompany,
                r.attName,
                r.attExt,
                r.attSize,
                r.entryNote,
                r.service,
                r.hour,
                r.minute,
                al.displayName,
                s.name as serviceName,
                r.note,
                r.internalNote,
                r.date
            FROM reservations r
            
            LEFT JOIN adminLogin al ON al.id = r.personnel
            LEFT JOIN services s ON s.id = r.service
            
            WHERE
                deleteHash = :hash";
			
$stmt = $dbh->prepare($query);
$stmt->bindParam(":hash", htmlentities($_POST["hash"]), PDO::PARAM_STR);
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_OBJ);

//poslat e-mail, že klient začíná vyplňovat dotazník
$mail = new PHPMailer\PHPMailer\PHPmailer();
$mail->Host = "localhost";
$mail->SMTPKeepAlive = true;
$mail->CharSet = "utf-8";
$mail->IsHTML(true);

$mail->SetFrom("info@fyzioland.cz", "Fyzioland");
$mail->ClearAllRecipients();
$mail->AddBCC("rezervace@fyzioland.cz");
$mail->Subject = "Klient zahájil vyplňování dotazníku";

$mail->Body = "<html><head></head><body style='padding: 10px;'>";
$mail->Body .= "Vážená kolegyně, vážený kolego,<br><br>";
$mail->Body .= "následující klient zahájil vyplňování senzorického dotazníku:<br><br>";

$mail->Body .= "<table style='border-collapse: collapse; border: 1px solid #65921b; max-width: 800px;'>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Jméno a příjmení</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . $client->name . " " . $client->surname. "</td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Datum a čas rezervace</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . (new DateTime())->createFromFormat("Y-m-d", $client->date)->format("j. n. Y") . " " . $client->hour . ":" . str_pad($client->minute, 2, "0", STR_PAD_LEFT) . "</b></td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Terapeut/terapeutka</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($client->displayName) . "</b></td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-bottom: 1px solid white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Zvolená služba</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'><b>" . htmlspecialchars($client->serviceName) . "</b></td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Poznámka klienta k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($client->note) . "</td></tr>";
$mail->Body .= "<tr><td style='background-color: #81ae37; color: white; border-top: 1px solid white; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>Interní poznámka k&nbsp;rezervaci</td><td style='border: 1px solid #65921b; padding-left: 10px; padding-right: 10px; padding-top: 3px; padding-bottom: 3px;'>" . nl2br($client->internalNote) . "</td></tr>";
$mail->Body .= "</table>";
$mail->Body .= "<br>";

$mail->Body .= "Automatický email systému Fyzioland.";      

$mail->Body .= "<br><br>";        

$mail->Body .= "<table style='border-collapse: collapse;'><tr>";
$mail->Body .= "<td style='vertical-align: middle;'><img src='cid:fyzioland_logo' width='90' style='width: 90px' alt='Fyzioland'></td>";
$mail->Body .= "<td style='padding-left: 15px; vertical-align: middle; font-size: 13px;'>";
$mail->Body .= "<b>Fyzioland s.r.o.</b><br>";
$mail->Body .= "Kašovická 1608/4, 104 00 Praha 22 - Uhříněves<br>";
$mail->Body .= "E-mail: <a href='rezervace@fyzioland.cz'>rezervace@fyzioland.cz</a><br>";
$mail->Body .= "Tel.: <a href='tel:+420 775 910 749'>+420 775 910 749</a>";
$mail->Body .= "</td>";
$mail->Body .= "</tr></table>";

$mail->AddEmbeddedImage("../img/Logo.png", "fyzioland_logo", "fyzioland_logo.png");


$mail->Send();  

if ($client->service == '12') { 
    //při kontrolním vyšetření se nevyplňují osobní údaje a přechází se rovnou na stránku s dotazníkem
    echo "<script type='text/javascript'>document.location = \"dotaznik.php?hash=" . $_POST["hash"] . "\";</script>";
    }
?>

<div class="container" style="height: 100%">
    <h1>Než se pustíte do dotazníku</h1>
    <p> Na začátku Vás požádáme o vyplnění potřebných údajů pro vedení naší interní zdravotnické dokumentace. </p>
    <p> <b>Pokud nám nyní vyplníte tyto údaje, ušetříme společně spoustu času na vstupním vyšetření a tento čas plně věnujeme skutečnému vyšetření - tedy Vám a Vašemu dítěti namísto papírování.</b></p>
    
    <p> Chceme Vás ubezpečit, že s&nbsp;poskytnutými informacemi nakládáme vždy plně v&nbsp;souladu s&nbsp;Nařízením Evropského parlamentu a&nbsp;Rady (EU) 2016/679 (GDPR) v&nbsp;platném znění. Vyplněním údajů souhlasíte se zpracováváním osobních údajů správcem Fyzioland s.r.o.</p>

    <form method="post" action="addPersData.php" enctype="multipart/form-data">   
        <input type="hidden" id="hash" name="hash" value="<?= htmlentities($_POST["hash"]) ?>" data-form="true">                              
        <div class="row">
            <div class="col-sm-6">
                <h3>KLIENT - DÍTĚ</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="name">Jméno</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Jméno" value="<?= $client->name ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="surname">Příjmení</label>
                    <input type="text" class="form-control" id="surname" name="surname" placeholder="Příjmení" value="<?= $client->surname ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>  
            </div>            
        </div>				
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="street">Ulice a číslo popisné</label>
                    <input type="text" class="form-control" id="street" name="street" placeholder="Ulice" value="<?= $client->street ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="city">Město</label>
                    <input type="text" class="form-control" id="city" name="city" placeholder="Město" value="<?= $client->city ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="zip">PSČ</label>
                    <input type="text" class="form-control" id="zip" name="zip" placeholder="PSČ" value="<?= $client->zip ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
        </div>                
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="sex">Pohlaví</label>
                    <select class="form-control" id="sex" name="sex" data-form="true" required>
                        <option value="">&lt;nevybráno&gt;</option>
                        <option value="F">žena</option>
                        <option value="M">muž</option>
                    </select>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="birthNumber">Rodné číslo</label>
                    <input type="text" class="form-control" id="birthnumber" name="birthnumber" placeholder="Rodné číslo" value="<?= $client->birthnumber ?>" data-form="true" required>							
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="birthday">Datum narození</label>
                    <input type="text" class="form-control" id="birthday" name="birthday" placeholder="Datum narození" value="<?= $client->birthday ?>" data-form="true" required>
                    <input type="hidden" class="form-control" id="birthdayformatted" name="birthdayformatted" placeholder="Datum narození" value="<?= $client->birthdayFormatted ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="insurancecompany">Zdavotní pojišťovna klienta</label>
                    <select class="form-control" id="insurancecompany" name="insurancecompany" data-form="true">
                    <option value=""></option>
                        <?php
                                $query = "SELECT id, kod, nazev FROM enum_insurancecompanies ORDER BY kod";
                                $stmt = $dbh->prepare($query);
                                $stmt->execute();
                                $results = $stmt->fetchAll(PDO::FETCH_OBJ);

                                foreach ($results as $result) {
                                    echo "<option value='{$result->id}'>";
                                    echo "{$result->kod} - {$result->nazev}";
                                    echo "</option>";
                                }
                            ?>
                    </select>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <br>
                <h3>ZÁKONNÝ ZÁSTUPCE</h3>
            </div>
        </div>        

        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="lrname">Jméno</label>
                    <input type="text" class="form-control" id="lrname" name="lrname" placeholder="Jméno" value="<?= $client->LRname ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="lrsurname">Příjmení</label>
                    <input type="text" class="form-control" id="lrsurname" name="lrsurname" placeholder="Příjmení" value="<?= $client->LRsurname ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>  
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="lremail">E-mailová adresa</label>
                    <input type="email" class="form-control" id="lremail" name="lremail" placeholder="E-mailová adresa" value="<?= $client->LRemail ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="lrphonemother">Telefonní kontakt - MATKA</label>
                    <input type="tel" class="form-control" id="lrphonemother" name="lrphonemother" placeholder="Telefonní číslo" value="<?= $client->LRphoneMother ?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="lrstreet">Ulice a číslo popisné</label>
                    <input type="text" class="form-control" id="lrstreet" name="lrstreet" placeholder="Ulice" value="<?= $client->LRstreet?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="lrcity">Město</label>
                    <input type="text" class="form-control" id="lrcity" name="lrcity" placeholder="Město" value="<?= $client->LRcity?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="lrzip">PSČ</label>
                    <input type="text" class="form-control" id="lrzip" name="lrzip" placeholder="PSČ" value="<?= $client->LRzip?>" data-form="true" required>
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="lrphonefather">Telefonní kontakt - OTEC</label>
                    <input type="tel" class="form-control" id="lrphonefather" name="lrphonefather" placeholder="Telefonní číslo" value="<?= $client->LRphneFather?>" data-form="true">
                    <span class="help-block" data-role="errorHelper"></span>
                </div>
            </div>            
        </div>	
                        
        <div class="row">
            <div class="col-sm-12">
                <br>
                <h3>Přílohy - lékařská zpráva s doporučením na rehabilitaci (povoleny jsou pouze přípony pdf, jpg, png a zip)</h3>
                <b>Důležitá poznámka</b>: Lze vložit pouze jeden soubor. Chcete-li vložit více lékařaských zpráv, zabalte je prosím pomocí programu zip do jednoho souboru a tento zabalený soubor zde vložte.
                <br><br>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="file">Soubor ke vložení</label>
                    <input type="file" class="form-control" name="file" id="modal-file" value="<?= $client->attName?>">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-12">
                <br>
                <h3>Popis obtíží (s čím přicházíte)</h3>
            </div>
        </div>
        
        <div class="row">    
            <div class="col-md-12">
                <div class="form-group">                        
                        <textarea class="form-control" rows="8" id="entrynote" name="entrynote" placeholder="Napiště prosím volným textem s čím přicházíte a co od terapie očekáváte"> <?= $client->entryNote?></textarea>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 col-md-offset-4 text-center">
                <div class="form-group">
                    <button class="btn btn-primary" type="submit" name="submit" id="submitButton" style="margin-top: 10px;">Uložit osobní údaje a přejít na vyplnění senzorického dotazníku </button>
                </div>
            </div>
        </div>
                      
    </form>
</div> 

<script>
    $(document).ready(function() {
        $("#zip").mask("999 99");
        $("#lrzip").mask("999 99");
        $("#birthnumber").mask("999999/999?9");
        $("#phone").mask("+420 999 999 999");
        $("#lrphonemother").mask("+420 999 999 999");
        $("#lrphonefather").mask("+420 999 999 999");    
        $("#birthday").datepicker({
                yearRange: "-90:+0",
                changeMonth: true,
                changeYear: true,
                altField: "#birthdayformatted",
                altFormat: "yy-mm-dd",
                dayNames: ["neděle", "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota"],
                dayNamesMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
                firstDay: 1,
                dateFormat: "d. m. yy",
                monthNames: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"]
            });

        $("#birthnumber").blur(function() {
                if ($(this).val() !== "" && $("#sex").val() !== "") {
                    var year = $(this).val().substring(0,2);
                    if (parseInt(year) <= 30) {
                        year = "20" + year;
                    } else {
                        year = "19" + year;
                    }
                    var month = parseInt($(this).val().substring(2,4));
                    if ($("#sex").val() === "F") {
                        month = month - 50;
                    }
                    var day = parseInt($(this).val().substring(4,6));

                    // TO DO
                    if ($("#birthday").val() === "") {
                        $("#birthday").val(day + ". " + month + ". " + year);
                        $("#birthdayformatted").val(year + "-" + month + "-" + day);
                    }

                }
            });

        //nastavení hodnot comboboxů dle výsledku sql dotazu na tabulce reservations
        var sex =  "<?= $client->sex?>"; 
        $("#sex").val(sex);
        var insurancecompany =  "<?= $client->insuranceCompany?>"; 
        $("#insurancecompany").val(insurancecompany);        
    });           
</script>
<!--

<--    
    