<?php
$pageTitle = "FL - Smazání rezervace";

require_once "header.php";
require_once "php/class.settings.php";
require_once "php/class.messagebox.php";

$messageBox = new MessageBox();

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

/*
echo($_GET["user"]);
echo($_GET["sendEmail"]);
echo($_GET["returnTo"]);
die();
*/

//dotaz na detaily rezervace, která má být smazána
$query = "  SELECT                    
                    CONCAT
                        (
                        DAY(r.date), '. ',
                        MONTH(r.date), '. ',
                        YEAR(r.date)
                        ) as dateFormatted,
                    DATE_FORMAT(CAST(CONCAT(r.hour, ':', r.minute, ':00') AS TIME), '%H:%i') as timeFrom,
                    r.date,
                    r.hour,
                    r.minute,
                    CONCAT(AES_DECRYPT(r.surname, '" . Settings::$mySqlAESpassword . "'),', ', AES_DECRYPT(r.name, '" . Settings::$mySqlAESpassword . "')) AS clientName,                    
                    a.displayName AS personnel,                    
                    r.service
                FROM reservations r
                LEFT JOIN adminLogin a ON a.id = r.personnel                
                WHERE
                     AES_DECRYPT(r.email, '" . Settings::$mySqlAESpassword . "') = :email AND
                    r.deleteHash = :deleteHash AND
                    r.active = 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":deleteHash", htmlentities($_GET["hsh"]), PDO::PARAM_STR);
    $stmt->bindValue(":email", urldecode($_GET["email"]), PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

if (empty($result->clientName) || empty($result->personnel)|| empty($result->timeFrom)) {
   $error = true; 
} else {
    $error = false;
    $reservationCancelDeadline = (new DateTime())->createFromFormat("Y-m-d H:i", $result->date . " " . $result->hour . ":" . str_pad($result->minute, 2, "0", STR_PAD_LEFT))->sub(new DateInterval("P2D"))->format("Y-m-d H:i");
    $now = (new DateTime())->format("Y-m-d H:i");
}

?>

<div class="container" style="height: 100%">
    <table style="width: 100%; height: 100%">
        <tr>
            <td style="vertical-align: middle; text-align: left;">
                <form method="POST" action="cancelReservationFinal.php">                                       
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
                                } else {
                            ?>
                            
                            <?php
                                if ($reservationCancelDeadline < $now) {                               
                            ?>
                            <div class="alert alert-danger text-center">
                                <b>Za zrušení rezervace Vám bude účtován storno poplatek ve výši 100% ceny terapie z důvodu rušení rezervace později než 48 hodin před zahájením terapie</b>. <br><br> Potvrzením zrušení rezervace se zavazujete k úhradě storno poplatku.
                            </div>
                            <?php
                                } else {
                            ?>
                                <div class="alert alert-success text-center">
                                    Rezervaci je možné bezplatně zrušit.
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
                                <input type="text" class="form-control" id="entryexamdate" name="entryexamdate" value="<?= $result->dateFormatted?>  <?= $result->timeFrom?>" placeholder="" >
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-sm-offset-3 well" style="margin-top: 20px;">
                            <h2 class="text-center" >
                                Důvod rušení rezervace
                            </h2>
                            
                            <div class="form-group" >               
                                <input type="radio" id="deletereason" name="deletereason" value="Nemoc klienta" required>
                                <label for="nemocklient">Nemoc</label><br>                                                                                                                    

                                <input type="radio" id="deletereason" name="deletereason" value="Termín se klientovi nehodí" required>
                                <label for="nehodi">Termín se mi nakonec nehodí</label><br>                

                                <input type="radio" id="deletereason" name="deletereason" value="Klient již nemá zájem o služby" required>
                                <label for="nemazajem">Nemám již zájem o služby</label><br>                                               
                                
                                <input type="radio" id="deletereason" name="deletereason" value="Jiné" required>
                                <label for="orgfl">Jiné důvody</label><br>   

                            </div>
                        <?php
                            }
                        ?>
                            
                            <?php
                                if (!$error) {
                            ?>
                            <div class="form-group" style="text-align: center;">
                                <input type="hidden" name="hsh" value="<?= htmlentities($_GET["hsh"]) ?>">
                                <input type="hidden" name="email" value="<?= urldecode($_GET["email"]) ?>">
                                                                     
                                <button type="button" style="margin-top: 20px;" class="btn btn-danger" id ="zpet" name="zpet">Nechci rušit rezervaci</button>
                                <button type="submit" style="margin-top: 20px;" class="btn btn-success" name="submitquest">ZRUŠIT REZERVACI</button>
                                
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
    
    <?php        
    ?>
    
</div>
                    
<script>
    
    $("#zpet").on("click", function() {	                
        alert('Rezervace nebyla zrušena');
        location.href = 'index.php';
    });
    
    
</script>