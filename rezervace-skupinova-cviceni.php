<?php
$pageTitle = "Skupinová cvičení - rezervace | Fyzioland";
$pageKeywords = "jóga, ranní protažení, zdravotní cvičení, fyziotrénink, kompenzační cvičení";
$pageDescription = "Skupinová cvičení pro děti i dospělé v malých skupinkých pod vedení fyzioterapeutů se sportovním zaměřením";
include "header.php";

if (isset($_GET["date"])) {
    if (!$date = (new DateTime)->createFromFormat("Y-m-d", $_GET["date"])) {
        $date = (new DateTime());
    }
} else {
    $date = (new DateTime()); 
}

?>

<div class="container-fluid">
    <div class="row skupiny" id="rezervace-title">
        <div class="col-md-12" id="title-logo">
            <a href="/" title="Na hlavní stránku">
                <img src="<?= $absolutePath ?>img/titulni-logo.png" title="Fyzioland">
            </a>
        </div>
    </div>
    

    <div class="row">
        <div class="col-lg-10 col-lg-offset-1">
            <?php if (isset($_SESSION["registerError"])): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-danger text-center" role="alert"><?= $_SESSION["registerError"] ?></div>
                    <?php unset($_SESSION["registerError"]); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION["registerSuccess"])): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-success text-center" role="alert"><?= $_SESSION["registerSuccess"] ?></div>
                    <?php unset($_SESSION["registerSuccess"]); ?>
                </div>
            </div>
            <!-- Měřicí kód Sklik.cz -->
            <iframe width="0" height="0" frameborder="0" scrolling="no" src="//c.imedia.cz/checkConversion?c=100045609&amp;color=ffffff&amp;v="></iframe>
            <?php else: ?>
            <script type="text/javascript">
                /* <![CDATA[ */
                var seznam_retargeting_id = 57208;
                /* ]]> */
            </script>
            <script type="text/javascript" src="//c.imedia.cz/js/retargeting.js"></script>
            <?php endif; ?>                                   
                       
            <div class="row" id="proc-si-vybrat-prave-nas" style="margin-top: 10px;">
                <div class="col-md-12 text-center nadpis">
                    AKTUALITY<br>
                    <div class="underline"></div>
                </div>
                
                <div class="col-md-6 text-center small">                                                                                                               
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Kompenzační cvičení pro sportovce (děti 11+ let) - 2. pololetí, úterý od 16:15, délka 55 minut, Mgr. Lenka Šťovíčková - ergoterapeutka, 17 lekcí od 3.2.2026, cena: 5&nbsp;490 Kč
                        </div>
                        <p>Kompenzační cvičení pro sportovce (11+ let). Cvičení se skládá z protahovacích cviků, které uvolní přetěžované svaly sportem a posilovacích cviků. Cvičení slouží i jako prevence sportovních úrazů. Vhodné pro fotbalisty, tenisty, hokejisty a další sportovce. 2. pololetí začínáme v úterý 3.2.2026! Leták s bližšími informace ke stažení: <b><a href="\files\1_Kompenzacni_cviceni_2026_01.pdf">ZDE</a></b> 
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Zdravě Hravě (děti 9+ let) - 2. pololetí, středa od 16:15, délka 55 minut, Mgr. Lenka Šťovíčková - ergoterapeutka, 17 lekcí cena: 5&nbsp;490 Kč
                        </div>
                        <p>Zábavné zdravotní cvičení hrou určené pro správné držení těla a&nbsp;rozvoj pohybových dovedností dětí ve věku od 9 let. 2. pololetí začínáme již ve středu 4.2.2026! Leták s bližšími informace ke stažení: <b><a href="\files\3_Hrave_zdrave_2026_01-9+.pdf">ZDE</a></b> 
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Cvičení SMS, úterý od 18:00, délka 45 minut, Mgr. Kristýna Nowaková - fyzioterapeutka, cena: 200 Kč
                        </div>
                        <p> SMS systém je cvičení pro pevnou a pružnou páteř bez bolestí pomocí pružných lan.
                    </div>
                </div>               
            
                <div class="col-md-6 text-center small">                                                                        
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Zdravě Hravě (děti 6-8 let) - 2. pololetí, středa od 15:15, délka 55 minut, Mgr. Lenka Šťovíčková - ergoterapeutka, 17 lekcí cena: 5&nbsp;490 Kč
                        </div>
                        <p>Zábavné zdravotní cvičení hrou určené pro správné držení těla a&nbsp;rozvoj pohybových dovedností dětí ve věku od 6 do 8 let. 2. pololetí začínáme již ve středu 4.2.2026! Leták s bližšími informace ke stažení: <b><a href="\files\2_Hrave_zdrave_2026_01_6_8.pdf">ZDE</a></b> 
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Zdravá záda, středa od 18:00, délka 55 minut, Mgr. Kristýna Nowaková - fyzioterapeutka, cena: 200 Kč
                        </div>
                        <p> Sedavé zaměstnání, jednostranný pohyb, těhotenství a&nbsp;mateřství, nesprávné cvičení, stres. Důvody, proč se ve svém těle necítíte dobře, mohou být různé. Na lekci se naučíte pozorně vnímat své tělo, odstraníte nevhodné pohybové návyky, protáhnete přetěžované svaly a&nbsp;posílíte střed těla a&nbsp;hluboký svalový stabilizační systém. Zdravotní cvičení zaměřující se na prevenci bolesti zad a&nbsp;krční páteře a&nbsp;správné posílení CORE.
                    </div>                                                            
                </div> 
                <!--
                <div class="col-md-6 text-center small">                                                                        
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Metoda vědomé stopy pohybu (děti 5-9 let), čtvrtek od 15:00, délka 45 minut, Petra Smutná, 15 lekcí cena: 4&nbsp;990 Kč
                        </div>
                        <p>Metoda vědomé stopy pohybu se skládá ze cvičení s&nbsp;prvky tchaj-ťi a&nbsp;malování vodou na velké archy papíru a&nbsp;slouží pro rozvoj grafomotoriky, jemné motoriky, hrubé motoriky a&nbsp;pozornosti. Rozvíjí kreativitu, prostorové a&nbsp;estetické vnímání, vede k&nbsp;soustředěnému opakování pohybů, učí koordinovat pohyb s&nbsp;dechem. 1. pololetí začínáme ve&nbsp;čtvrtek 25.9.2025! Leták s bližšími informace ke stažení: <b><a href="\files\4_MVSP_2025_09.pdf">ZDE</a></b> 
                    </div>                                                   
                </div>
                -->
            </div>
        </div>
    </div>
    
    <div class="row modra" style="padding: 20px;">
        <div class="col-lg-10 col-lg-offset-1"  id="rezervace-telo">
            
            <div class="row" id="rezervace-nadpis">
                Rezervace na skupinová cvičení<br>
                <div class="underline"></div>
            </div>
            
            <div class="row">
                <?php if (isset($_SESSION["loggedUserId"])): ?>
                <div class="loggedBar text-right">
                    Jste přihlášeni jako: <?= $_SESSION["loggedUserName"] ?> <?= $_SESSION["loggedUserSurname"] ?> | <a href="logout?backTo">Odhlásit se</a>
                </div>
                <?php else: ?>
                <div class="loggedBar text-center">
                    <a href="login">Přihlásit se</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="row" id="hlavicka-rezervaci">
                <div class="col-xs-4 text-left">
                    <a href="#" id="previousWeekButton">
                        <h4>
                            <span class="glyphicon glyphicon-chevron-left"></span>
                            Předchozí období
                        </h4>
                    </a>
                </div>
                <div class="col-xs-4 text-center">
                    <a href="<?= $absolutePath ?>rezervace-skupinova-cviceni" id="currentWeekButton">
                        <h4>
                            Aktuální období pro rezervace
                        </h4>
                    </a>
                </div>
                <div class="col-xs-4 text-right">
                    <a href="#" id="nextWeekButton">
                        <h4>
                            Následující období
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </h4>
                    </a>
                </div>
            </div>

            <div class="skupinova-cviceni-kalendar"></div>
        </div>
    </div>
    
    <div class="row" style="padding: 20px;">
        <div class="col-lg-10 col-lg-offset-1">

            <div class="row" id="proc-si-vybrat-prave-nas" style="margin-top: 0px;">
                <div class="col-lg-10 col-lg-offset-1">
                    <div class="col-md-12 text-center nadpis">
                        Důležitá pravidla<br>
                        <div class="underline"></div>
                    </div>
                    <div class="col-md-12 text-center small">
                        <div class="col-md-6">
                            <div class="duvod-proc-nas">
                                <div class="nadpis">
                                    Minimální počet účastníků
                                </div>
                                <p>U&nbsp;každého skupinového cvičení je uveden datum a&nbsp;čas, do kdy musí být přihlášen alespoň minimání počet účastníků, aby se cvičení konalo. V&nbsp;případě zrušení skupinového cvičení jsou všichni přihlášení uživatelé informováni minimálně 1&nbsp;hodinu před zrušením lekce formou SMS (v&nbsp;případě, že se lekce koná dříve než v&nbsp;10:00 dopoledne, je informační SMS zasílána nejpozději do 21:00 předchozího dne).</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="duvod-proc-nas">
                                <div class="nadpis">
                                    Zrušení rezervace a&nbsp;storno podmínky
                                </div>
                                <p>Pokud se klient nedostaví na skupinové cvičení, na které má vytvořenou rezervaci, a&nbsp;zároveň se neodhlásí ze skupinového cvičení prostřednictvím rezervačního systému minimálně 2&nbsp;hodiny před konáním skupinového cvičení, je klient povinen uhradit 100% storno poplatek odpovídající ceně lekce dle ceníku společnosti Fyzioland při následující návštěvě.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 vypln"></div>
                    <div class="col-md-12 text-center podnadpis">

                    </div>
                 </div>
            </div>
        </div>
    </div>

    <div class="modal fade"  id="rezervace-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="<?= $absolutePath ?>rezervace-skupinova-cviceniAction" method="post" id="reservationsForm">
                <div class="modal-content skupiny">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Přihlášení na skupinové cvičení</h4>
                    </div>
                    <?php if (!isset($_SESSION["loggedUserId"])): ?>
                    <div class="modal-body">
                        <p style="font-size: 16pt; text-align: center;">Abyste si mohli rezervovat volné místo na skupinové cvičení,<br>tak se prosím <a href="login" data-type="loginBackTo">zde přihlašte</a> do našeho rezervačního systému. <br><br>Pokud od nás nemáte zatím údaje k&nbsp;přihlášení do systému,<br>tak se prosím nejprve <a href="registrace"> zde zaregistrujte.</a></p>
                    </div>
                    <div class="modal-footer"></div>
                    <?php else: ?>
                    <div class="modal-body">
                        <div class="row" data-semestral="true">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="courseLessonsCount">Počet lekcí v kurzu: </label>
                                    <input class="form-control" type="text" id="courseLessonsCount" value="" readonly>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="courseFirstLesson">První lekce: </label>
                                    <input class="form-control" type="text" id="courseFirstLesson" value="" readonly>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="courseLastLesson">Poslední lekce: </label>
                                    <input class="form-control" type="text" id="courseLastLesson" value="" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="excerciseTitle">Název cvičení: </label>
                                    <input class="form-control" type="text" id="excerciseTitle" value="" readonly>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="name">Datum a čas: </label>
                                    <input class="form-control" type="text" id="dateAndTimeOfReservation" value="" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="name">Jméno: </label>
                                    <input class="form-control" type="text" name="name" id="name" value="<?= $_SESSION["loggedUserName"] ?>" readonly>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="surname">Příjmení: </label>
                                    <input class="form-control" type="text" name="surname" id="surname" value="<?= $_SESSION["loggedUserSurname"] ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="email">E-mailová adresa: </label>
                                    <input class="form-control" type="email" name="email" id="email" value="<?= $_SESSION["loggedUserEmail"] ?>" readonly>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="phone">Telefonní číslo: </label>
                                    <input class="form-control" type="tel" name="phone" id="phone" value="<?= $_SESSION["loggedUserPhone"] ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="note">Poznámka pro lektora: </label>
                            <textarea class="form-control" name="note" id="note" autofocus></textarea>
                        </div>
                        <input type="hidden" id="groupExcerciseId" name="groupExcerciseId" value="">
                        <input type="hidden" id="backTo" name="backTo" value="<?= $date->format("Y-m-d") ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                        <button type="submit" class="btn btn-primary" name="submit">Závazně se přihlásit</button>
                    </div>
                    <?php endif; ?>
                </div><!-- /.modal-content -->
            </form>
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    
    <div class="modal fade"  id="rezervace-modal-odhlaseni" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="<?= $absolutePath ?>rezervace-skupinova-cviceniAction" method="post" id="reservationsForm">
                <div class="modal-content skupiny">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Odhlášení se ze skupinového cvičení</h4>
                    </div>
                     <?php if (!isset($_SESSION["loggedUserId"])): ?>
                    <div class="modal-body">
                        <p style="font-size: 16pt; text-align: center;">Abyste se mohli přihlásit na skupinové cvičení,<br>tak se prosím <a href="login">přihlašte</a> nebo <a href="registrace">registrujte.</a></p>
                    </div>
                    <div class="modal-footer"></div>
                    <?php else: ?>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="excerciseTitle">Název cvičení: </label>
                                    <input class="form-control" type="text" id="excerciseTitleLogout" value="" readonly>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="name">Datum a čas: </label>
                                    <input class="form-control" type="text" id="dateAndTimeOfReservationLogout" value="" readonly>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="groupExcerciseIdLogout" name="groupExcerciseId" value="">
                        <input type="hidden" id="backToLogout" name="backTo" value="<?= $date->format("Y-m-d") ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                        <button type="submit" class="btn btn-primary" name="logout">Odhlásit se</button>
                    </div>
                    <?php endif; ?>
                </div><!-- /.modal-content -->
            </form>
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    
    <div class="modal fade chci-vedet-vice-modal" tabindex="-1" role="dialog" id="joga-modal" aria-labelledby="JogaModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="myModalLabel">Hatha jóga workshop 16.&nbsp;6.&nbsp;2018 - Adéla Bergrová</h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <p>
                                Tímto Vás srdečně zveme na krásné sobotní dopoledne s&nbsp;jógou, kde budeme praktikovat jógové ásány (pozice), ukážeme si práci s&nbsp;dechem (pránájáma) a&nbsp;seznámíme Vás podrobněji se zdravotními účinky jednotlivých ásán. Budeme praktikovat ásány na posílení pánevního dna, stimulace lymfatického systému a&nbsp;další. V&nbsp;případě hezkého počasí cvičíme venku na zahradě.  
                            </p>
                        </div>
                    </div>
                                        
                    <div class="row">
                        <div class="col-md-6">
                            <img src="img/joga-workshop.jpg" alt="Jóga workshop">
                        </div>
                        <div class="col-md-6">
                            <p>
                                <b>Hatha jóga</b> je klasická jóga s&nbsp;dlouhou historií a&nbsp;je jedna z&nbsp;nejrozšířenějších v&nbsp;západním světě.
                            </p>
                            <p>
                                Hatha jóga je souhrn <b>fyzických, dechových a&nbsp;očistných technik</b>, které vedou k&nbsp;celkovému <b>pročištění a&nbsp;posílení</b> těla.
                            </p>
                            <p>
                                Workshop je <b>vhodný pro mírně pokročilé či začátečníky</b> všech věkových kategorií.
                            </p>
                            
                        </div>
                    </div>
                    <div class="row">
 
                        <div class="col-md-12">
                            <p>
                                <b>Program workshopu:</b> úvodní protažení, seznámení a&nbsp;procvičování sestavy Pozdrav Slunci A. Jógové pozice (ásány) na posílení a&nbsp;protažení těla, pránájáma (práce s&nbsp;dechem), jógová relaxace.
                            </p>
                        </div> 
                        <div class="col-md-12">
                            <p>
                                <b>Termín konání:</b> 16.&nbsp;6.&nbsp;2018 10:00 až 13:00
                            </p>
                            <p>
                                <b>Přihlášky do:</b> 11.&nbsp;6.&nbsp;2018
                            </p>
                            
                            <p>
                                <b>Cena:</b> 550 Kč za osobu
                            </p>
                            <p>
                                <b>Platební podmínky:</b> Platba předem do 5&nbsp;dnů od registrace na účet č.&nbsp;2401198774/2010, do poznámky pro příjemce uveďte prosím jméno a&nbsp;příjmení účastníka.
                            </p>
                            <p>
                                V případě zájmu si prosím rezervujte volné místo v našem rezervačním systému na skupinová cvičení (workshop najdete v rezervačním kalendáři dne 16.&nbsp;6.&nbsp;2018).
                            </p>
                        </div>   
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                </div>
            </div>
        </div>
    </div>
    
<script>
    var prvniDatum = moment("<?= $date->format("Y-m-d"); ?>", "YYYY-MM-DD");
    var predchoziObdobi = "";
    var dalsiObdobi = "";
    var pocetZobrazenychSloupcu = 7;
    
    var otevritPoNacteni = <?php
        if ( isset($_SESSION["redirectAfterLoginModal"]) ) {
            echo $_SESSION["redirectAfterLoginModal"];
            unset($_SESSION["redirectAfterLoginModal"]);
        } else {
            echo "''";
        }
    ?>;
        
    var otevritModal = "<?php
        if ( isset($_GET["modal"]) ) {
            echo $_GET["modal"];
        }
    ?>";
        
    $(document).ready(function() {
        if (otevritModal !== "") {
            var modal = $("#" + otevritModal);
            if (modal.length > 0) {
                modal.modal('show');
            }
        }
        
        
        $("#rezervace-telo").on("click", "a[data-type='event']", function(event) {
            event.preventDefault();
            if ($(this).hasClass("passed")) {
                return false;
            }
            
            var eventIsFull = ($(this).attr("data-occupancy") === $(this).attr("data-capacity"));
           
            var isPartOfSemestralCourse = $(this).data("semestralCourse") === "" ? false : true;
            if (isPartOfSemestralCourse) {
                $("#rezervace-modal [data-semestral='true']").show();
                
                var firstLesson = moment($(this).attr("data-semestral-course-first-group-excercise"));
                var lastLesson = moment($(this).attr("data-semestral-course-last-group-excercise"));
                
                $("#courseLessonsCount").val($(this).attr("data-semestral-course-group-excercises-count"));
                $("#courseFirstLesson").val(firstLesson.format("D. M. YYYY"));
                $("#courseLastLesson").val(lastLesson.format("D. M. YYYY"));
            } else {
                $("#rezervace-modal [data-semestral='true']").hide();
            }
            
            // je-li přihlášený uživatel registrovaný na toto skupinové cvičení
            if (parseInt($(this).data("userIsLogged")) === 0) {
                if (!eventIsFull) {
                    $("a[data-type='loginBackTo']").attr("href", "login?e=" + $(this).data("id"));
                
                    $("#excerciseTitle").val($(this).data("title"));
                    $("#dateAndTimeOfReservation").val($(this).data("dayAndMonth") + ", " + $(this).data("timeFrom") + " - " + $(this).data("timeTo"));
                    $("#groupExcerciseId").val($(this).data("id"));
                    $('#rezervace-modal').modal('show');
                }
            } else {
                $("#excerciseTitleLogout").val($(this).data("title"));
                $("#dateAndTimeOfReservationLogout").val($(this).data("dayAndMonth") + ", " + $(this).data("timeFrom") + " - " + $(this).data("timeTo"));
                $("#groupExcerciseIdLogout").val($(this).data("id"));
                $('#rezervace-modal-odhlaseni').modal('show');
            }
        });
        
        
        var minColumnWidth = 100;
        function zjistitPocetMaximalneZobrazenychSloupcu() {
            var calendarWidth = $(".skupinova-cviceni-kalendar").outerWidth();
            var numberOfShownColumns = Math.floor((calendarWidth - $(".skupinova-cviceni-kalendar th[data-role='days']").first().outerWidth()) / minColumnWidth);
            
            if (numberOfShownColumns >= 7) {
                pocetZobrazenychSloupcu =  7;
            } else {
                pocetZobrazenychSloupcu = numberOfShownColumns;
            }
            
            
        };
        
        function objektySePrekryvaji(obj1, obj2) {
            positionObj1 = obj1.position();
            obj1.left = positionObj1.left;
            obj1.right = obj1.left + obj1.outerWidth();
            obj1.top = positionObj1.top;
            obj1.bottom = obj1.top + obj1.outerHeight();
            
            positionObj2 = obj2.position();
            obj2.left = positionObj2.left;
            obj2.right = obj2.left + obj2.outerWidth();
            obj2.top = positionObj2.top;
            obj2.bottom = obj2.top + obj2.outerHeight();

            return !(obj1.right < obj2.left || obj1.left > obj2.right || obj1.bottom < obj2.top || obj1.top > obj2.bottom);
        };
        
        $.widget.bridge("tlp", $.ui.tooltip); // ošetření konfliktu s widgetem tooltip() v Bootstrap, používáme ten z jQuery UI
        function nacistCviceni(datum) {
            $.ajax({
                url: "skupiny.php",
                dataType: "html",
                method: "GET",
                data: {date: datum, numberOfShownDays: pocetZobrazenychSloupcu},
                success: function(data) {
                    $(".skupinova-cviceni-kalendar").replaceWith(data);
                    
                    $(".skupinova-cviceni-kalendar .item").each(function () {
                        var day = $(this).data("day");
                        var time = $(this).data("anchor-time-from");
                        var length = $(this).data("length");
                        var topOffset = $(this).data("top-offset");
                        var target = $(".skupinova-cviceni-kalendar .tableCell[data-day='" + day + "'][data-time='" + time + "']");
                        var ucastniciPodlePoctu;
                        if ( parseInt($(this).data("minimalAttendance")) === 1 ) {
                            ucastniciPodlePoctu = "účastník";
                        } else if ( parseInt($(this).data("minimalAttendance")) < 5 ) {
                            ucastniciPodlePoctu = "účastníci";
                        } else {
                            ucastniciPodlePoctu = "účastníků";
                        }

                        var position = target.parent().position();

                        $(this).css("top", position.top + topOffset * target.parent().outerHeight());
                        $(this).css("left", position.left);
                        $(this).outerWidth(target.parent().outerWidth());
                        $(this).outerHeight(target.parent().outerHeight() * length);
                        
                        var isPartOfSemestralCourse = $(this).data("semestralCourse") === "" ? false : true;
                        
                        var tooltipContent = "";
                        tooltipContent += "<p><b>" + $(this).data("title") + "</b></p>";
                        tooltipContent += "<p><span class='glyphicon glyphicon-calendar' aria-hidden='true'></span> <b>" + $(this).data("weekday") + ", " + $(this).data("dayAndMonth") + "<br><span class='glyphicon glyphicon-time' aria-hidden='true'></span> " + $(this).data("timeFrom") + " - " + $(this).data("timeTo") + "</b></p>";
                        tooltipContent += "<p>Lektor: <b>" + $(this).data("instructor") + "</b></p>";
                        if ( !isPartOfSemestralCourse ) {
                            tooltipContent += "<p>Cena za lekci: <b>" + $(this).data("price") + "</b></p>";
                        } else {
                            var pocetLekci = $(this).data("semestralCourseGroupExcercisesCount");
                            tooltipContent += "<p>Cena za kurz (" + pocetLekci + " lekcí): <b>" + $(this).data("price") + "</b></p>";
                        }
                        tooltipContent += "<p>Podmínky konání:<br><small>Lekce se koná, pokud se do <b>" + $(this).data("criticalTime") + "</b> přihlásí alespoň " + $(this).data("minimalAttendance") + "&nbsp;" + ucastniciPodlePoctu + ".</small></p>";
                        tooltipContent += "Popis lekce:<br><small>" + $(this).data("description") + "</small>";

                        $(this).tlp({
                            track: true,
                            content: tooltipContent,
                            tooltipClass: "skupinove-cviceni-tooltip"
                        });
                        $(this).show();
                    });
                    
                    if (otevritPoNacteni !== "") {
                        $("a.item[data-id='" + otevritPoNacteni + "']").click();
                        otevritPoNacteni = "";
                    }
                }
            });
            
            var hostname = window.location.hostname;
            if (hostname.substring(0,3) === "www") {
                history.replaceState(prvniDatum.format("YYYY-MM-DD"), 'Fyzioland - rezervace', 'https://www.fyzioland.cz/rezervace-skupinova-cviceni?date=' + prvniDatum.format("YYYY-MM-DD"));
            } else {
                history.replaceState(prvniDatum.format("YYYY-MM-DD"), 'Fyzioland - rezervace', 'https://fyzioland.cz/rezervace-skupinova-cviceni?date=' + prvniDatum.format("YYYY-MM-DD"));
            }
        };
        zjistitPocetMaximalneZobrazenychSloupcu();
        if (pocetZobrazenychSloupcu === 7) {
            prvniDatum = prvniDatum.startOf('isoWeek');
        }
        nacistCviceni(prvniDatum.format("YYYY-MM-DD"));
        vypocitatOkolniData(prvniDatum);
        
        var windowWidth = $(window).width();
        $(window).resize(function() {
            if ($(this).width() === windowWidth) {
                return false;
            }
            
            $(".skupinova-cviceni-kalendar").html("");
            zjistitPocetMaximalneZobrazenychSloupcu();
            nacistCviceni(prvniDatum.format("YYYY-MM-DD"), pocetZobrazenychSloupcu);   
            windowWidth = $(window).width();
            
            vypocitatOkolniData(prvniDatum);
        });
        
        $("#nextWeekButton").click(function(event) {
            event.preventDefault();
            
            prvniDatum = dalsiObdobi;
            nacistCviceni(dalsiObdobi.format("YYYY-MM-DD"), pocetZobrazenychSloupcu);
            vypocitatOkolniData(prvniDatum);
        });

        $("#previousWeekButton").click(function(event) {
            event.preventDefault();
            
            prvniDatum = predchoziObdobi;
            nacistCviceni(predchoziObdobi.format("YYYY-MM-DD"), pocetZobrazenychSloupcu);
            vypocitatOkolniData(prvniDatum);
        });

        $("#currentWeekButton").click(function(event) {
            event.preventDefault();
            
            if (pocetZobrazenychSloupcu >= 7) {
                datum = moment().day(1);
            } else {
                datum = moment();
            }
            
            prvniDatum = datum;
            
            nacistCviceni(prvniDatum.format("YYYY-MM-DD"), pocetZobrazenychSloupcu);
            vypocitatOkolniData(prvniDatum);
        });
        
        function vypocitatOkolniData(datum) {
            predchoziObdobi = datum.clone().subtract(pocetZobrazenychSloupcu, 'days');
            if ( predchoziObdobi < moment().startOf('isoWeek') ) {
                $("#previousWeekButton").hide();
            } else {
                $("#previousWeekButton").show();
            }
            dalsiObdobi = datum.clone().add(pocetZobrazenychSloupcu, 'days');
        }
        
    });
</script>

<?php

require "footer.php";

?>