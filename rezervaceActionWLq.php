<?php
$pageTitle = "Rezervace z čekací listiny";

require_once "header.php";
require_once "php/class.messagebox.php";
require_once "php/class.settings.php";

session_start();
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

if (empty($_GET["hsh"])) {
    $messageBox->addText("Požadovaná stránka není samostatně přístupná, využijte prosím odkaz v e-mailu.");
    $messageBox->setClass("alert-danger");

    $_SESSION["messageBox"] = $messageBox;
    header("Location: rezervace");
    die();
} else {
    $query = "  SELECT                    
                    CONCAT(AES_DECRYPT(c.name, '" . Settings::$mySqlAESpassword . "'), ' ',  AES_DECRYPT(c.surname, '" . Settings::$mySqlAESpassword . "')) AS clientName,                                       
                    DATE_FORMAT(l.WLdate, '%d.%m.%Y') as date,
                    TIME_FORMAT(l.WLtime, '%H:%i') as timeFrom,                    
                    adminLogin.displayName AS personnel,
                    s.name as serviceName
                FROM logWL AS l
                LEFT JOIN adminLogin ON adminLogin.id = l.therapist
                LEFT JOIN clients c ON c.id = l.client
                LEFT JOIN services s ON l.service = s.id
                WHERE                    
                    l.hash = :hash ";
    $stmt = $dbh->prepare($query);    
    $stmt->bindParam(":hash", $_GET["hsh"], PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (count($results) === 0) {
        $error = true;
    } else {
        $result = $results[0];
        $error = false;
    }
    
}
?>

<div class="container" style="height: 100%">
    <table style="width: 100%; height: 100%">
        <tr>
            <td style="vertical-align: middle; text-align: left;">
                <form method="POST" action="rezervaceActionWL.php">                                       
                    <div class="row">
                        <div class="col-sm-6 col-sm-offset-3 text-center">
                            <img src="../img/Logo.png">
                            <br><br>
                            <h2>Vytvoření rezervace z čekací listiny </h2>
                        </div>
                        <div class="col-sm-6 col-sm-offset-3 well">
                            
                            <?php
                                if ($error == true || empty($result->clientName) || empty($result->personnel)|| empty($result->timeFrom)) {
                                $error = true;
                            ?>
                            <div class="alert alert-danger text-center">
                                V databázi se nenachází žádný odpovídající požadavek na čekací listině. <br> Kontaktujte nás prosím na info@fyzioland.cz nebo tel. čísle 775 910 749.
                            </div>
                            <?php
                                }
                            ?>
                            <?php
                                if (!$error) {
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
                            <div class="form-group">                                
                                <label for="service">Služba:</label>
                                <input type="text" class="form-control" id="entryexamdate" name="service" value="<?= $result->serviceName?> " placeholder="" >
                            </div>
                            
                                
                            <div class="form-group" style="text-align: center;">
                                <input type="hidden" name="hsh" value="<?= htmlentities($_GET["hsh"]) ?>">                                
                                <button type="button" style="margin-top: 20px;" class="btn btn-danger" id ="zpet" name="zpet">Nemám zájem</button>
                                <button type="submit" style="margin-top: 20px;" class="btn btn-success" name="submitquest">Chci vytvořit rezervaci</button>                                
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
        var returnTo = "<?= $_POST["returnTo"] ?>";
        alert('Rezervace nebyla vytvořena');
        location.href = 'index.php';
    });
    
    
</script>