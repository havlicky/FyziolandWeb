<?php
include "header.php";

if (isset($_SESSION["loggedUserId"])) {
    echo "<script>document.location = '/';</script>";
    die();
}

if (isset($_GET["e"])) {
    $_SESSION["redirectAfterLoginModal"] = intval($_GET["e"]);
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
        <div class="col-lg-6 col-lg-offset-3">
            <form action="loginAction.php" method="post" role="login">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <h3>Přihlášení do rezervačního systému Fyzioland</h3>
                    </div>
                </div>
                
                <?php if (isset($_SESSION["loginError"])): ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-danger" role="alert"><?= $_SESSION["loginError"] ?></div>
                        <?php unset($_SESSION["loginError"]); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION["logout"])): ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-success" role="alert">Byli jste úspěšně odhlášeni.</div>
                        <?php unset($_SESSION["logout"]); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="login">Váš e-mail: </label>
                            <input class="form-control" type="email" id="login" name="login" value="" autofocus required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="password">Heslo: </label>
                            <input class="form-control" type="password" id="password" name="password" value="" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <div class="form-group">
                            <button type="submit" name="submit" value="submit" class="btn btn-info">Přihlásit se</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <a href="registrace">Registrovat se</a> nebo <a href="requestResetHesla">obnovit zapomenuté heslo</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container-fluid">
    
<?php
include "footer.php";
?>