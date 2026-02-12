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
            <form action="registraceAction.php" method="post" role="register">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <h3>Registrace do rezervačního systému pro skupinová cvičení</h3>
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
                        <?php unset($_SESSION["registerSuccess"]); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label for="login" class="control-label">Váš e-mail (*): </label>
                            <input class="form-control" type="email" id="email" name="email" value="" autofocus required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="password" class="control-label">Heslo (*): </label>
                            <input class="form-control" type="password" pattern=".{6,}" title="Heslo musí obsahovat alespoň 6 znaků" id="password" name="password" value="" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="passwordAgain" class="control-label">Heslo znovu (*): </label>
                            <input class="form-control" type="password" pattern=".{6,}" title="Heslo musí obsahovat alespoň 6 znaků" id="passwordAgain" name="passwordAgain" value="" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="name" class="control-label">Jméno (*): </label>
                            <input class="form-control" type="text" id="name" name="name" value="" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="surname" class="control-label">Příjmení (*): </label>
                            <input class="form-control" type="text" id="surname" name="surname" value="" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label for="phoneNumber" class="control-label">Telefonní číslo (*): </label>
                            <input class="form-control" type="text" id="phoneNumber" name="phoneNumber" value="" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <div class="form-group">
                            <button type="submit" name="submit" value="submit" class="btn btn-info">Registrovat se</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group form-inline">
                            <label for="personalDetailsAgreement">
                                <input type="checkbox" name="personalDetailsAgreement" id="personalDetailsAgreement" value="1" required>
                                V souladu s&nbsp;Nařízením Evropského parlamentu a&nbsp;Rady (EU) 2016/679 (GDPR) v&nbsp;platném znění souhlasím se zpracováváním osobních údajů správcem Fyzioland s.r.o.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group form-inline">
                            <label for="emailingAgreement">
                                <input type="checkbox" name="emailingAgreement" id="emailingAgreement" value="1">
                                Souhlasím se zasíláním informativních a&nbsp;reklamních e-mailů od společnosti Fyzioland s.r.o.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 text-right small">
                        Pole označená hvězdičkou (*) jsou povinná.
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#phoneNumber").mask("+420 999 999 999");
        
        $("#email").blur(function() {
            var emailField = $(this);
            
            if (emailField.val() === "") {
                return;
            }
            
            $.ajax({
                url: "emailAvailable.php",
                method: "post",
                data: { "email": emailField.val() },
                dataType: "json",
                success: function(data) {
                    response = data.response;
                    
                    $("#helpBlockEmail").remove();
                    $("#name, #surname, #phoneNumber").removeProp("disabled");
                    if (response === "0") {
                        emailField.parent().addClass("has-error");
                        emailField.after('<span id="helpBlockEmail" class="help-block">Tato e-mailová adresa je již používána, zvolte prosím jinou. V případě, že se jedná o Vaši e-mailovou adresu, <a href="login">prihlašte se</a> nebo si zkuste <a href="requestResetHesla">obnovit</a> zapomenuté heslo.</span>');
                    } else if (response === "2") {
                        emailField.parent().removeClass("has-error");
                        emailField.after('<span id="helpBlockEmail" class="help-block">Tato e-mailová adresa je již na základě Vašeho souhlasu evidovaná v našem systému. Nastavte si prosím heslo pro registraci.</span>');
                        $("#name, #surname, #phoneNumber").prop("disabled", "disabled");
                    } else {
                        emailField.parent().removeClass("has-error");
                    }
                }
            });
        });
        
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