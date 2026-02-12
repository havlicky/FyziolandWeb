<?php
$pageTitle = "Orofaciální stimulace | Fyzioland";
$pageKeywords = "orofaciální stimulace, ergoterapie, poruchy příjmu potravy";
$pageDescription = "Orofacilání stimulace";
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
                        <li><a href="#ergo-dospeli">Orofaciální stimulace</a></li>
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
                Nabídka našich služeb v oblasti ergoterapie pro děti<br>
                <div class="underline"></div>
            </div>
        </div>
    </div>

    <div class="col-md-12 vypln"></div>
    
    <div class="row" id="ergo-dospeli">
        <div class="row bubliny" style="padding-bottom: 10px;">
            <div class="col-lg-12 text-center">
                <h2><b>Orofaciální stimulace</b></h2>
                <div class="underline black"></div>
            </div>
             <div class="col-lg-4 col-lg-offset-4" id="ergo-text">                       
                Tato terapie působí na oblast obličeje a&nbsp;úst, jejímž cílem je zlepšení orientace jazyka v&nbsp;ústech, 
                vyrovnání napětí a&nbsp;citlivosti, harmonizace (snížení spastických svalů a&nbsp;povzbuzení ochablých svalů), 
                navození příjemných podnětů, povzbuzení žvýkacích svalů, zmírnění slinění, zvýšení pohyblivosti jazyka, snížení bolestivých pocitů při sycení a&nbsp;další.            
            </div>
        </div>
        
        <div class="row" style="margin-top: 25px;">    
            <div class="col-lg-12 text-center">
                <h2><b>Při orofaciální stimulaci se zaměřujeme na:</b></h2>
                <div class="underline black"></div>
            </div>
        </div>
        
        <div class="row" id="dospeli-odkaz2">
            <div class="col-lg-10 col-lg-offset-1">
                <div class="col-md-3">
                     <img src="img/EgroAdults_1.jpg"  style="width: 230px;" title="Ergoterapie">
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="nadpis">zlepšení příjmu potravy a&nbsp;tekutin</p>                            
                            <p class="nadpis">rozvoj hlasu, předřečových a&nbsp;řečových dovedností</p>                                                                                    
                        </div>
                        <div class="col-md-6">
                            <p class="nadpis">prohloubené a&nbsp;fyziologicky správné dýchání</p>  
                            <p class="nadpis">celkové zklidnění při jemné masáži celého obličeje</p>                                                      
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                     <img src="img/EgroAdults_2.jpg"  style="width: 230px;" title="Ergoterapie">
                </div>
            </div>
        </div>

       <div class="row">    
            <div class="col-lg-12 text-center">
                <h2><b>Pro koho je orofacilání stimulace vhodná</b></h2>
                <div class="underline black"></div>
            </div>
        </div>
        
        <div class="row" id="dospeli-odkaz2">
           <div class="col-lg-4 col-lg-offset-4" id="ergo-text">     
            Orofaciální stimulace je vhodná u&nbsp;osob s&nbsp;poruchami v&nbsp;obličejové – faciální a&nbsp;ústní – orální oblasti všech věkových skupin.
               
            </div>
        </div>
        
        <div class="row" id="dospeli-odkaz2">
          <div class="col-lg-4 col-lg-offset-4" id="ergo-text">     
           Naše terapeutky jsou certifikované na provádění orofaciální stimulace.
           </div>
        </div>
    </div>

    <?php include "cenik.php"; ?>
<?php
include "footer.php";
?>