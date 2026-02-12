<div class="container-fluid fh-fixedHeader">
    <div id="navbar">
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container-fluid"  style="border-bottom: 1px solid #82bb25;">
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#top-menu" aria-expanded="false">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">
                        <img alt="Fyzioland" src="../img/Logo.png" class="hidden-lg hidden-md hidden-sm" id="brand-logo">
                    </a>
                </div>

                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse" id="top-menu">
                    <ul class="nav navbar-nav">
                        <?php if ($resultAdminUser->isSuperAdmin === "1"): ?>
                            <li><a href="viewClients">Klienti</a></li>
                            <!--<li><a href="spravaSlotu">Sloty</a></li>-->
                            <li><a href="emailsList">E-maily</a></li>
                            <li><a href="WL">WL</a></li>
                            <li><a href="reports">Reporty</a></li>
                        <?php endif; ?>
                        <?php if ($resultAdminUser->isTerapist === "1" || $resultAdminUser->isSuperAdmin === "1"): ?>                                                    
                            
                            <li><a href="spravaSlotuKlient">Sloty klienti</a></li>
                            <!--<li><a href="spravaSlotuKlient2">Sloty klienti (new)</a></li>-->
                            <li><a href="viewReservations">Indiv. obj. (den)</a></li>
                            <li><a href="viewReservationsWeek">Indiv. obj. (týden)</a></li>                            
                        <?php endif; ?>
                        <?php if ($resultAdminUser->isErgo === "1" || $resultAdminUser->isFyzio === "1" || $resultAdminUser->isSuperAdmin === "1"): ?>                        
                            <li><a href="viewReservationsRoomAlocation.php">MÍSTNOSTI</a></li>                                                       
                        <?php endif; ?>
                        <?php if ($resultAdminUser->isSuperAdmin === "1"): ?>                                                    
                            <li><a href="viewReservationsMonth">ERGO všichni (měsíc)</a></li>                            
                        <?php endif; ?>    
                        <?php if ($resultAdminUser->isErgo === "1" || $resultAdminUser->isSuperAdmin === "1"): ?>                        
                            <li><a href="viewVstupKontrolALL.php">Vstupní/Kontrolní</a></li>
                            
                        <?php endif; ?>   
                        <li><a href="viewGroupReservations">Skupinová cvičení</a></li>                                                
                        <?php if ($resultAdminUser->isSuperAdmin === "1"): ?>                            
                            <li><a href="viewAllDeposits.php">Předplatné</a></li>                            
                            <li><a href="viewAllReservations.php">Všechny</a></li>
                            <li><a href="viewCancelledReservations.php">Zrušené</a></li>
                        <?php endif; ?>
                        <?php if ($resultAdminUser->viewWL === "1" || $resultAdminUser->isSuperAdmin === "1"): ?>
                            <li><a href="viewWaitingList.php">WL(původní)</a></li>                            
                        <?php endif; ?>
                                             
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div><!-- /.container-fluid -->
        </nav>
    </div>
    <div id="vyrovnani-menu"></div>
</div>

<div style="position: fixed; top: 55px; right: 10px;">
    <p class="alert-success" style="padding-left: 10px;">
		Přihlášený uživatel: <b><?= $resultAdminUser->displayName ?></b>
		<a class="btn" href="logout.php">Odhlásit</a>
	</p>
</div>