<?php
include "header.php";
?>

<div class="container">
           
    <div class="row" id="stranka-404">
        <div class="col-md-12" id="title-logo">
            <a href="index" title="Na hlavní stránku">
                <img src="<?= $absolutePath ?>img/titulni-logo.png" title="Fyzioland">
            </a>
        </div>
        Omlouváme se, ale požadovaná stránka nebyla nalezena. Pokračujte prosím kliknutím na naše logo výše, které Vás přenese na hlavní stránku webu.<br>
        <div class="underline"></div>
    </div>
    
    <div class="row" id="chci-vice-informaci">
        <div class="col-md-5">
            <img src="<?= $absolutePath ?>img/chci-vice-informaci.png" title="Chci více informací">
        </div>
        <div class="hidden-md hidden-lg vypln">

        </div>
        <div class="col-md-7">
            <form class="form-inline" action="#" id="kontaktni-formular">
                <div class="row">
                    <div class="col-md-7 col-md-offset-1 col-xs-6 ">
                        <input type="email" class="form-control" id="email" placeholder="E-mail" required>
                    </div>        
                    <div class="col-md-4 col-xs-6">
                        <button type="submit" class="btn">Kontaktujte mne</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php
include "footer.php";
?>