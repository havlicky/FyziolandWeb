<?php
$pageTitle = "Handle | Fyzioland";
$pageKeywords = "handle, ergoterapie";
$pageDescription = "Handle";
include "header.php";
?>

<script type="text/javascript">
	/* <![CDATA[ */
	var seznam_retargeting_id = 57208;
	/* ]]> */
</script>
<script type="text/javascript" src="//c.imedia.cz/js/retargeting.js"></script>

<div class="container-fluid">
    <div id="navbar">
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container">
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#top-menu" aria-expanded="false">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">
                        <img alt="Fyzioland" src="img/Logo.png" class="hidden-lg hidden-md hidden-sm" id="brand-logo">
                    </a>
                </div>

                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse" id="top-menu">
                    <ul class="nav navbar-nav">                        
                        <li><a href="#ergo-dospeli">Handle</a></li>
                        <li><a href="#" data-nav="no" data-toggle="modal" data-target="#cenik-modal">Ceník</a></li>
                        <li><a href="#kontakty">Kontakt</a></li>
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div><!-- /.container-fluid -->
        </nav>
    </div>
    <div id="vyrovnani-menu"></div>
        
    <div class="row" id="dospeli-odkaz1">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3 col-md-offset-2 text-center" id="title-objednavky">
                    <div>
                        Objednávky:
                        <a href="tel:+420 775 910 749" rel="nofollow">+420&nbsp;775&nbsp;910&nbsp;749</a>
                    </div>
                </div>
                <div class="col-md-2 text-center hidden-sm hidden-xs" id="title-logo">
                    <a href="index" title="Na hlavní stránku">
                        <img src="img/titulni-logo.png" title="Fyzioland">
                    </a>
                </div>
                <div class="col-md-3 text-center" id="title-online">
                    <div style="background-color: grey;">
                        <a href="rezervace">Objednávky on&#8209;line</a>
                    </div>                    
                </div>
            </div>
        </div>
        <div class="row" id="dospeli-hlavni-nadpis">
            <div class="col-lg-12">
                Nabídka našich služeb v&nbsp;oblasti ergoterapie pro děti<br>
                <div class="underline"></div>
            </div>
        </div>
    </div>

    <div class="col-md-12 vypln"></div>
    
    <div class="row" id="ergo-dospeli">
        <div class="row bubliny" style="padding-bottom: 10px;">
            <div class="col-lg-12 text-center">
                <h2><b>Handle</b></h2>
                <div class="underline black"></div>
            </div>
             <div class="col-lg-4 col-lg-offset-4" id="ergo-text">                       
                HANDLE je neinvazivní přístup k&nbsp;terapii neurovývojových odlišností jako např. PAS, poruch učení, poruch pozornosti, ADHD, poruch vývoje řeči, poruch paměti a&nbsp;chování, DMO a&nbsp;mnoho dalších.<br><br>    
                Tento přístup bere chování jako způsob komunikace a&nbsp;snaží se pochopit, co dané chování znamená. Například neposedné dítě nemusí být vůbec zlobivé, ale jeho aktivita může ukazovat zvýšenou hmatovou senzitivitu. Špatná úprava písma  může značit nízké svalové napětí dítěte apod.<br><br>
                HANDLE hledá příčiny těchto obtíží a&nbsp;snaží se jednoduchými technikami pomoci dítěti či dospělému zlepšit funkce nervového systému i&nbsp;proces učení.
                Vychází také z poznatků, že nervový systém je schopný se neustále adaptovat.<br><br>
                HANDLE vytváří pro každého klienta jednoduchý a&nbsp;individuální program rytmických pohybových aktivit. Tyto aktivity jsou neurologicky komplexní a&nbsp;časově nenáročné.                
            </div>
        </div>                

    <?php include "cenik.php"; ?>
<?php
include "footer.php";
?>