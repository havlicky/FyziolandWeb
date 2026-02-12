<?php
$pageTitle = "Dotazník";
require_once "checkLogin.php";
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

//nnačtení osobních údajů
$query = "  SELECT                
                r.id as resid,
                DATE_FORMAT(r.date,'%d.%m.%Y')as date,
                r.date as dateFormatted,
                DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i')as time,
                al.shortcut,
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
                r.active
            FROM reservations r
            
            LEFT JOIN adminLogin al ON al.id = r.personnel
            
            WHERE
                deleteHash = :hash";
			
$stmt = $dbh->prepare($query);
$stmt->bindParam(":hash", htmlentities($_GET["hash"]), PDO::PARAM_STR);
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_OBJ);

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
    
    <?php
        if($client->active == 0) {
    ?>        
            <div class="row">
                <div class="col-lg-12 text-right">
                    <div class="form-group">
                        <label for="resid">Vyberte novou aktivní rezervaci, ke které chcete zkopírovat tento dotazník</label>
                        <select name="resid" id="resid" class="form-control" style="display: inline-block; width: auto;"> 
                            <option value="<?= $client->resid ?>"><?= $client->date . ' ' . $client->time . ' (' . $client->shortcut . ') ' . $client->name . ' ' . $client->surname ?></option>
                            <option value="">----------------------------------------</option>
                            <?php
                                $query = "  SELECT
                                                r.id,
                                                CONCAT(
                                                    DATE_FORMAT(r.date,'%d.%m.%Y'), ' ',
                                                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i'), ' (',
                                                    al.shortcut, ') ',
                                                    AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "'), ' ',
                                                    AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'), ' '
                                                )as rezervace
                                            FROM reservations r
                                            LEFT JOIN adminLogin al ON al.id=r.personnel                                            
                                            WHERE
                                                r.active = 1 AND
                                                r.service = :service AND
                                                r.date >=:date
                                            ORDER BY r.date ASC, r.hour ASC
                                            ";
                                $stmt = $dbh->prepare($query);
                                $stmt->bindParam(":date", $client->dateFormatted, PDO::PARAM_STR);
                                $stmt->bindParam(":service", $client->service, PDO::PARAM_INT);
                                $stmt->execute();
                                $results = $stmt->fetchAll(PDO::FETCH_OBJ);
                            ?>
                            <?php foreach ($results as $result): ?>
                                <option value="<?= $result->id ?>"><?= $result->rezervace ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
    <?php
        }
    ?>
    <h1>Osobní údaje klienta</h1>
    <hr>
    
    <div class="row">
        <div class="col-sm-6">
            <h3>KLIENT - DÍTĚ</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-3">
            <div class="form-group">
                <label for="name">Jméno</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Jméno" value="<?= $client->name ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="surname">Příjmení</label>
                <input type="text" class="form-control" id="surname" name="surname" placeholder="Příjmení" value="<?= $client->surname ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>  
        </div>            
    </div>				
    <div class="row">
        <div class="col-sm-3">
            <div class="form-group">
                <label for="street">Ulice a číslo popisné</label>
                <input type="text" class="form-control" id="street" name="street" placeholder="Ulice" value="<?= $client->street ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="city">Město</label>
                <input type="text" class="form-control" id="city" name="city" placeholder="Město" value="<?= $client->city ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="zip">PSČ</label>
                <input type="text" class="form-control" id="zip" name="zip" placeholder="PSČ" value="<?= $client->zip ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
    </div>                
    <div class="row">
        <div class="col-sm-3">
            <div class="form-group">
                <label for="sex">Pohlaví</label>
                <select class="form-control" id="sex" name="sex" data-form="true" readonly>
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
                <input type="text" class="form-control" id="birthnumber" name="birthnumber" placeholder="Rodné číslo" value="<?= $client->birthnumber ?>" data-form="true" readonly>							
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="birthday">Datum narození</label>
                <input type="text" class="form-control" id="birthday" name="birthday" placeholder="Datum narození" value="<?= $client->birthday ?>" data-form="true" readonly>
                <input type="hidden" class="form-control" id="birthdayformatted" name="birthdayformatted" placeholder="Datum narození" value="<?= $client->birthdayFormatted ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="insurancecompany">Zdavotní pojišťovna klienta</label>
                <select class="form-control" id="insurancecompany" name="insurancecompany" data-form="true" readonly>
                <option value=""></option>
                    <?php
                            $query = "SELECT id, kod, nazev FROM enum_insurancecompanies ORDER BY kod";
                            $stmt = $dbh->prepare($query);
                            $stmt->execute();
                            $resultsInsComp = $stmt->fetchAll(PDO::FETCH_OBJ);

                            foreach ($resultsInsComp as $result) {
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
                <input type="text" class="form-control" id="lrname" name="lrname" placeholder="Jméno" value="<?= $client->LRname ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="lrsurname">Příjmení</label>
                <input type="text" class="form-control" id="lrsurname" name="lrsurname" placeholder="Příjmení" value="<?= $client->LRsurname ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>  
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="lremail">E-mailová adresa</label>
                <input type="email" class="form-control" id="lremail" name="lremail" placeholder="E-mailová adresa" value="<?= $client->LRemail ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="lrphonemother">Telefonní kontakt - MATKA</label>
                <input type="tel" class="form-control" id="lrphonemother" name="lrphonemother" placeholder="Telefonní číslo" value="<?= $client->LRphoneMother ?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-3">
            <div class="form-group">
                <label for="lrstreet">Ulice a číslo popisné</label>
                <input type="text" class="form-control" id="lrstreet" name="lrstreet" placeholder="Ulice" value="<?= $client->LRstreet?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="lrcity">Město</label>
                <input type="text" class="form-control" id="lrcity" name="lrcity" placeholder="Město" value="<?= $client->LRcity?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="lrzip">PSČ</label>
                <input type="text" class="form-control" id="lrzip" name="lrzip" placeholder="PSČ" value="<?= $client->LRzip?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label for="lrphonefather">Telefonní kontakt - OTEC</label>
                <input type="tel" class="form-control" id="lrphonefather" name="lrphonefather" placeholder="Telefonní číslo" value="<?= $client->LRphneFather?>" data-form="true" readonly>
                <span class="help-block" data-role="errorHelper"></span>
            </div>
        </div>            
    </div>	

    <div class="row">
        <div class="col-sm-12">
            <br>
            <h3>Přílohy - lékařská zpráva s doporučením na rehabilitaci (povoleny jsou pouze přípony pdf, jpg, png a zip)</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="file">Soubor ke vložení</label>
                <input type="text" class="form-control" name="file" id="modal-file" value="<?= $client->attName?>" readonly>
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
                    <textarea class="form-control" rows="8" id="entrynote" name="entrynote" readonly placeholder="Napiště prosím volným textem s čím přicházíte a co od terapie očekáváte"> <?= $client->entryNote?> </textarea>
            </div>
        </div>        
    </div>   
    
    <br><br>
    <div class="row">
        <h1>Dotazník senzorického vnímání</h1>
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
</div>
   
<script> 
    $(document).ready(function() {                
        
        //změna přiřazení ID rezervace
        $("select[name='resid']").change(function() {
            var newResId = $("#resid").val();
            var originalHash = '<?= $_GET["hash"] ?>';            
            $.ajax({
               url: "dotaznikZmenaIDresQualityCheck.php",
               data: {                                     
                   "originalHash": originalHash
               },
               method: "post",
               dataType: "json",
               success: function(response) {
                    if (response.clientDataInReservation == 'KO'){
                        alert('POZOR! Původní rezervace, ze které chcete kopírovat informace o klientovi do nové rezervace neobsahuje žádná data o klientovi! Údaje NEBUDOU ZKOPÍROVÁNY.')
                    }
                    if (response.pocetVyplnenychOtazek == 0){
                        alert('POZOR. Půdovní rezervace, ze které chcete kopírovat do nové rezervace, neobsahuje žádnou vyplněnou otázku v dotazníku! Údaje NEBUDOU ZKOPÍROVÁNY.')
                    }
                    if (response.clientDataInReservation == 'OK' && response.pocetVyplnenychOtazek > 0){
                        if (confirm('Zkopírovat údaje o klientovi a odpovědi na otázky z původní zrušené rezervace na nově vybranou aktivní rezervaci?')) {   
                            $.ajax({
                                url: "dotaznikZmenaIDres.php",
                                data: {                  
                                    "newResId": newResId,
                                    "originalHash": originalHash
                                },
                                method: "post",
                                dataType: "text",
                                success: function(response) {
                                    alert('Údaje o klientovi a otázky v dotazníku byly zkopírovány do nové aktivní rezervace (původní zrušená rezervace zůstala beze změny)')
                                },
                                error: function(xhr, msg) {                    
                                     window.alert("nastala chyba: " + msg + " . Nepokračujte prosím dále v přenesení dotazníku. Kontaktujte Jiřího Havlického.");
                                }
                            });
                        }
                    }
               },
               error: function(xhr, msg) {                    
                    window.alert("nastala chyba: " + msg + " . Nepokračujte prosím dále ve vyplnění dotazníku. Zdá se, že něco nám nefunguje a Vaše odpovědi by nebyly zaznamenány. Kontaktujte nás prosím na info@fyzioland.cz neho tel. čísle 775 910 749. Za způsobené komplikace se Vám omlouváme.");
               }
            });
        });
        
        var hash = "<?= htmlentities($_GET["hash"]) ?>";
        $.ajax({
               url: "../dotaznik/getQuestions.php",
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
                        $("select[name='" + idOtazky + "']" ).prop('disabled',true);
                        
                    });
               },
               error: function(xhr, msg) {                    
                    window.alert("nastala chyba: " + msg + " . Nepokračujte prosím dále ve vyplnění dotazníku. Zdá se, že něco nám nefunguje a Vaše odpovědi by nebyly zaznamenány. Kontaktujte nás prosím na info@fyzioland.cz neho tel. čísle 775 910 749. Za způsobené komplikace se Vám omlouváme.");
               }
        });
    
        //nastavení hodnot comboboxů dle výsledku sql dotazu na tabulce reservations   
        var sex =  "<?= $client->sex?>"; 
        $("#sex").val(sex);
        var insurancecompany =  "<?= $client->insuranceCompany?>"; 
        $("#insurancecompany").val(insurancecompany);    
        
        
    });    
</script>

<?php include("../footer.php"); ?>  
    
    