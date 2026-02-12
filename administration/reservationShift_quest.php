<?php
$pageTitle = "FL - Přesun rezervace";

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

//dotaz na detaily rezervace, která má být přesunuta
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
                    r.id = :id AND                                   
                    r.active = 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", htmlentities($_GET["id"]), PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

$error = false;
?>

<div class="container" style="height: 100%">
    <table style="width: 100%; height: 100%">
        <tr>
            <td style="vertical-align: middle; text-align: left;">
                <form method="POST" action="reservationShift.php">                                       
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
                                                        
                            <div class="form-group">                                
                                <label for="clientres">Jméno klienta:</label>
                                <input type="text" class="form-control" id="clientres" name="clientres" value="<?= $result->clientName?>" placeholder="">
                            </div>
                            <div class="form-group">
                                <label for="therapist">Jméno terapeuta:</label>
                                <input type="text" class="form-control" id="therapist" name="therapist" value="<?= $result->personnel?>" placeholder="">
                            </div>
                            <div class="form-group">                                
                                <label for="entryexamdate">Den a čas rezervace:</label>
                                <input type="text" class="form-control" id="entryexamdate" name="entryexamdate" value="<?= $result->date?>  <?= $result->timeFrom?>" placeholder="" >
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-sm-offset-3 well" style="margin-top: 20px;">
                            <h2 class="text-center" >
                                Důvod přesunu rezervace
                            </h2>
                            
                            <div class="form-group" >               
                                <input type="radio" id="shiftreason" name="shiftreason" value="Nemoc klienta" required>
                                <label for="nemocklient">Nemoc klienta</label><br>                

                                <input type="radio" id="shiftreason" name="shiftreason" value="Nemoc terapeuta" required>
                                <label for="nemocterapeut">Nemoc terapeuta</label><br>   
                                
                                <input type="radio" id="shiftreason" name="shiftreason" value="Klient nedorazil" required>
                                <label for="klientnedorazil">Klient nedorazil</label><br>        

                                <input type="radio" id="shiftreason" name="shiftreason" value="Termín se klientovi nehodí" required>
                                <label for="nehodi">Termín se klientovi nehodí</label><br>                

                                <input type="radio" id="shiftreason" name="shiftreason" value="Nabídli jsme klientovi dřívější termín" required>
                                <label for="nemazajem">Nabídli jsme klientovi dřívější termín</label><br>                

                                <input type="radio" id="shiftreason" name="shiftreason" value="Organizační důvody na straně FL" required>
                                <label for="orgfl">Organizační důvody na straně FL</label><br> 
                                
                                <input type="radio" id="shiftreason" name="shiftreason" value="Jiné" required>
                                <label for="orgfl">Jiné důvody</label><br>   

                            </div>
                            
                            <?php
                                if (!$error) {
                            ?>
                            <div class="form-group" style="text-align: center;">
                                <input type="hidden" name="id" value="<?= htmlentities($_GET["id"]) ?>">
                                <input type="hidden" name="freeslotid" value="<?= $_GET["freeslotid"] ?>">
                                <input type="hidden" name="returnTo" value="<?= $_GET["returnTo"] ?>">
                                <input type="hidden" name="user" value="<?= $_GET["user"] ?>">        
                                <input type="hidden" name="sendEmail" value="<?= $_GET["sendEmail"] ?>">                                
                                <button type="button" style="margin-top: 20px;" class="btn btn-danger" id ="zpet" name="zpet">Nechci přesouvat rezervaci</button>
                                <button type="submit" style="margin-top: 20px;" class="btn btn-success" name="submitquest">Pokračujte pro přesunutí rezervace</button>
                                
                            </div>
                            <?php
                                }
                            ?>                        
                    </div>
                </form>
            </td>
        </tr>
    </table>
    
    <?php        
    ?>
    
</div>
                    
<script>
    
    $("#zpet").on("click", function() {	                
        var returnTo = "<?= $_GET["returnTo"] ?>";
        
        alert('Rezervace nebyla přesunuta');
        location.href = 'viewReservations.php?date=' + returnTo;
    });
    
    
</script>