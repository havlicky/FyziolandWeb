<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

session_start();
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

//dotaz na detaily rezervace, ke které má být vyplěn vstupní dotazník
$query = "  SELECT                    
                    CONCAT
                        (
                        DAY(r.date), '. ',
                        MONTH(r.date), '. ',
                        YEAR(r.date)
                        ) as date,
                    CAST(CONCAT(r.hour, ':', r.minute) as time) as timeFrom,                    
                    CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ', AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')) AS clientName,                    
                    a.displayName AS personnel,
                    r.qFinished,
                    r.service
                FROM reservations r
                LEFT JOIN adminLogin a ON a.id = r.personnel                
                WHERE
                    r.deleteHash = :hash AND                                   
                    r.active = 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":hash", htmlentities($_GET["id"]), PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

$error = false;
?>

<div class="container" style="height: 100%">
    <table style="width: 100%; height: 100%">
        <tr>
            <td style="vertical-align: middle; text-align: left;">
                <form method="POST" action="personalData.php">                                       
                    <div class="row">
                        <div class="col-sm-6 col-sm-offset-3 text-center">
                            <img src="../img/Logo.png">
                            <br><br>
                        </div>
                        <div class="col-sm-6 col-sm-offset-3 well">
                            <?php
                                if (empty($result->clientName) || empty($result->personnel)|| empty($result->timeFrom)) {
                                $error = true;
                            ?>
                            <div class="alert alert-danger text-center">
                                Vaše rezervace nebyla nalezena nebo má chybné údaje. <br> Kontaktujte nás prosím na info@fyzioland.cz
                            </div>
                            <?php
                                }
                            ?>
                            <?php
                                if (!empty($result->qFinished)) {
                                $error = true;
                            ?>
                            <div class="alert alert-success text-center">
                                Dotazník byl již úspěsně vyplněn a odevzdán. Děkujeme.
                            </div>
                            <?php
                                }
                            ?>
                            <div class="form-group">
                                <?php
                                    //vstupní
                                    if ($result->service == '10') { 
                                    ?>
                                    <label for="clientres">Jméno klienta uvedené v rezervaci na vstupní vyšetření:</label>
                                <?php
                                    }
                                    // kontrolní
                                    if ($result->service == '12') { 
                                ?>
                                    <label for="clientres">Jméno klienta uvedené v rezervaci na kontrolní vyšetření:</label>
                                <?php
                                    }
                                ?>                                
                                <input type="text" class="form-control" id="clientres" name="clientres" value="<?= $result->clientName?>" placeholder="">
                            </div>
                            <div class="form-group">
                                <label for="therapist">Jméno terapeuta:</label>
                                <input type="text" class="form-control" id="therapist" name="therapist" value="<?= $result->personnel?>" placeholder="">
                            </div>
                            <div class="form-group">
                                <?php
                                    //vstupní
                                    if ($result->service == '10') { 
                                    ?>
                                    <label for="entryexamdate">Den a čas plánovaného vstupního vyšetření:</label>
                                <?php
                                    }
                                    // kontrolní
                                    if ($result->service == '12') { 
                                ?>
                                    <label for="entryexamdate">Den a čas plánovaného kontrolního vyšetření:</label>
                                <?php
                                    }
                                ?>
                                
                                <input type="text" class="form-control" id="entryexamdate" name="entryexamdate" value="<?= $result->date?>  <?= $result->timeFrom?>" placeholder="" >
                            </div>
                            <?php
                                if (!$error) {
                            ?>
                            <div class="form-group" style="text-align: center;">
                                <input type="hidden" name="hash" value="<?= htmlentities($_GET["id"]) ?>">
                                <button type="submit" class="btn btn-success" name="submitquest">Pokračujte pro vyplnění dotazníku</button>
                            </div>
                            <?php
                                }
                            ?>
                        </div>
                    </div>
                </form>
            </td>
        </tr>
    </table>
</div>