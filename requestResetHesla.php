<?php
include "header.php";

if (isset($_SESSION["loggedUserId"])) {
    echo "<script>document.location = '/';</script>";
    die();
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
            <form action="requestResetHeslaAction.php" method="post" role="register">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <h3>Požadavek na obnovení zapomenutého hesla</h3>
                    </div>
                </div>
                
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
                        <?php 
                            unset($_SESSION["registerSuccess"]);
                            echo "</div></div></div></form></div></div></div>";
                            include "footer.php";
                            die();
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label for="login" class="control-label">Vaše e-mailová adresa: </label>
                            <input class="form-control" type="email" id="email" name="email" value="" autofocus required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <div class="form-group">
                            <button type="submit" name="submit" value="submit" class="btn btn-info">Žádost o obnovení hesla</button>
                        </div>
                    </div>
                </div>
            </form>
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