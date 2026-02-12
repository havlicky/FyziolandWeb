<?php
$pageTitle = "Konzultace, školení ergoterapie | Fyzioland";
$pageKeywords = "ergoterapie pro děti, školení, konzultace";
$pageDescription = "Školení a konzultace z oblasti dětské ergoterapie pro pedagogy, logopedy, asistenty pedagogů, psychology a pracovníky rané péče pracující s dětmi s neurovývojovými a senzorickými poruchami.";
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
                        <li><a href="#ergo-dospeli">Konzultace</a></li>
                        <li><a href="#ergo-dospeli">Školení</a></li>
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
                Nabídka našich služeb v&nbsp;oblasti konzultací a&nbsp;školení<br>
                <div class="underline"></div>
            </div>
        </div>
    </div>

    <div class="col-md-12 vypln"></div>
    
    <div class="row" id="ergo-dospeli">
        <div class="row bubliny" style="padding-bottom: 10px;">
            <div class="col-lg-12 text-center">
                <h2><b>Konzultace ve školce nebo škole</b></h2>
                <div class="underline black"></div>
            </div>        
            <div class="row" style="margin-bottom: 25px">
                <div class="col-lg-4 col-lg-offset-4" id="ergo-text">                                
                    Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec iaculis gravida nulla. Nullam justo enim, consectetuer nec, ullamcorper ac, vestibulum in, elit. Vivamus luctus egestas leo. Duis condimentum augue id magna semper rutrum. Maecenas lorem. Sed elit dui, pellentesque a, faucibus vel, interdum nec, diam. In sem justo, commodo ut, suscipit at, pharetra vitae, orci.<br><br>
                    Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec iaculis gravida nulla. Nullam justo enim, consectetuer nec, ullamcorper ac, vestibulum in, elit. Vivamus luctus egestas leo. Duis condimentum augue id magna semper rutrum. Maecenas lorem. Sed elit dui, pellentesque a, faucibus vel, interdum nec, diam. In sem justo, commodo ut, suscipit at, pharetra vitae, orci.<br><br>                                        
                </div>              
            </div>
        </div>              
    </div>

     <div class="row" id="skolení">    
        <div class="col-lg-12 text-center">
            <br>
            <h2><b>Seminář o&nbsp;dětské ergoterapie<b/></h2>
            <div class="underline black"></div>
        </div>
    </div>
    
    <?php include "cenik.php"; ?>
<?php
include "footer.php";
?>