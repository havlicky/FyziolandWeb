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
    $query = "SELECT id FROM clients WHERE AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') = :email AND recoveryHash = :recoveryHash LIMIT 1";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":email", $_GET["email"], PDO::PARAM_STR);
    $stmt->bindParam(":recoveryHash", $_GET["hsh"], PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($result === FALSE) {
        $_SESSION["checkError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["checkError"] .= "Vložené údaje nejsou správné, opakujte prosím kliknutí na odkaz v e&#8209;mailu.";
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
        <div class="col-lg-8 col-lg-offset-2">
            <form action="resetHeslaAction.php" method="post" role="register">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <h3>Obnovení přihlašovacího hesla</h3>
                    </div>
                </div>
                
                <?php if (isset($_SESSION["checkError"])): ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-danger" role="alert"><?= $_SESSION["checkError"] ?></div>
                        <?php
                            unset($_SESSION["checkError"]); 
                            echo "</div></div></div></form></div></div></div>";
                            include "footer.php";
                            die();
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION["resetError"])): ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-success" role="alert"><?= $_SESSION["resetError"] ?></div>
                        <?php unset($_SESSION["resetError"]); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION["resetSuccess"])): ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-success" role="alert"><?= $_SESSION["resetSuccess"] ?></div>
                        <?php
                            unset($_SESSION["resetSuccess"]); 
                            echo "</div></div></div></form></div></div></div>";
                            include "footer.php";
                            die();
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="password" class="control-label">Zadejte nové heslo (*): </label>
                            <input class="form-control" type="password" pattern=".{6,}" title="Heslo musí obsahovat alespoň 6 znaků" id="password" name="password" value="" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="passwordAgain" class="control-label">Nové heslo znovu (*): </label>
                            <input class="form-control" type="password" pattern=".{6,}" title="Heslo musí obsahovat alespoň 6 znaků" id="passwordAgain" name="passwordAgain" value="" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <div class="form-group">
                            <button type="submit" name="submit" value="submit" class="btn btn-info">Změnit heslo</button>
                            <input type="hidden" name="email" value="<?= htmlentities($_GET["email"]) ?>">
                            <input type="hidden" name="hash" value="<?= htmlentities($_GET["hsh"]) ?>">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#passwordAgain").blur(function() {
            var passwordField = $("#password");
            var passwordAgainField = $("#passwordAgain");
                   
            $("#helpBlockPassword").remove();
            if (passwordField.val() !== passwordAgainField.val()) {
                passwordField.parent().addClass("has-error");
                passwordAgainField.parent().addClass("has-error");
                passwordAgainField.after('<span id="helpBlockPassword" class="help-block">Zadaná hesla se neshodují.</span>');
            } else {
                passwordField.parent().removeClass("has-error");
                passwordAgainField.parent().removeClass("has-error");
            }
             
        });
        
        $("form").submit(function(e) {
            if ( $( ".has-error" ).length > 0 ) {
                e.preventDefault();
            }
        });
    });
</script>
<div class="container-fluid">
    
<?php
include "footer.php";
?>