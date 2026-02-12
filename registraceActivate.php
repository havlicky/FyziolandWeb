<?php
include "header.php";

if (isset($_SESSION["loggedUserId"])) {
    echo "<script>document.location = '/';</script>";
    die();
}

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

if (isset($_GET["email"]) && isset($_GET["hsh"])) {
    $query = "SELECT id, activated FROM clients WHERE AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') = :email AND activationHash = :activationHash LIMIT 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":email", $_GET["email"], PDO::PARAM_STR);
    $stmt->bindParam(":activationHash", $_GET["hsh"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($result === FALSE) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Vložené údaje nejsou správné, opakujte prosím kliknutí na odkaz v e&#8209;mailu.";
    } else if (intval($result->activated) === 1) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Aktivace tohoto účtu již byla provedena. Můžete se přihlásit <a href='login'>zde</a>";
    } else {
        $query = "UPDATE clients SET activated = 1 WHERE id = :id";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":id", $result->id, PDO::PARAM_STR);
        $stmt->execute();
        
        $_SESSION["registerSuccess"] = "<span class='glyphicon glyphicon-ok' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerSuccess"] .= "Aktivace Vašeho účtu byla úspěšně provedena, děkujeme. Můžete se přihlásit <a href='login'>zde</a>";
    }
}

?>

<div class="container-fluid">
    <div class="row" id="stranka-login">
        <div class="col-md-12" id="title-logo">
            <a href="/" title="Na hlavní stránku">
                <img src="<?= $absolutePath ?>img/titulni-logo.png" title="Fyzioland">
            </a>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-8 col-lg-offset-2" style="padding-top: 40px; padding-bottom: 40px;">
            
            <?php if (isset($_SESSION["registerError"])): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-danger" role="alert"><?= $_SESSION["registerError"] ?></div>
                    <?php unset($_SESSION["registerError"]); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION["registerSuccess"])): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-success" role="alert"><?= $_SESSION["registerSuccess"] ?></div>
                    <?php unset($_SESSION["registerSuccess"]); ?>
                </div>
            </div>
            <?php endif; ?>
                
            
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        
    });
</script>
<div class="container-fluid">
    
<?php
include "footer.php";
?>