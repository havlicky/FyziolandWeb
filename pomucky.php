<?php
$pageTitle = "Ergoterapie | Fyzioland";
$pageKeywords = "ergoterapie,dysgrafie,dysfázie,DMO,PAS";
$pageDescription = "Ergoterapie pro děti na zlepšení jemné motoriky ruky a podporu psychomotorického vývoje s využitím prvků senzorické integrace";
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
                        <li><a href="#ergo-dospeli">Pomůcky na domácí použití</a></li>
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
                Nabídka našich služeb v oblasti pomůcek na domácí použití<br>
                <div class="underline"></div>
            </div>
        </div>
    </div>

    <div class="col-md-12 vypln"></div>
    
    <div class="row" id="ergo-dospeli">
        <div class="row bubliny" style="padding-bottom: 10px;">
            <div class="col-lg-12 text-center">
                <h2><b>Pomůcky na domácí použití</b></h2>
                <div class="underline black"></div>
            </div>        
            <div class="row" style="margin-bottom: 25px">
                <div class="col-lg-4 col-lg-offset-4" id="ergo-text">                    
                    Tuto stránku pro Vás aktuálně připravujeme.<br>
                    <b>Již brzy - děkujeme za pochopení.</b>
                </div>    
                <div class="col-lg-4 col-lg-offset-4" id="ergo-text">
                    <img src="img/StrankaSePripravuje.jpg">
                </div>                
            </div>
        </div>              
    </div>
 
    <?php include "cenik.php"; ?>
<?php
include "footer.php";
?>