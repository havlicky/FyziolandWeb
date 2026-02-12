<?php
include "header.php";
?>

<div class="container-fluid">
    <div id="navbar">
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container-fluid">
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
                        <li><a href="#fyzioterapie">Fyzioterapie</a></li>
                        <li><a href="#cviceni">Doplňkové služby</a></li>
                        <li><a href="#nase-vize">Naše poslání</a></li>
                        <li><a href="#proc-si-vybrat-prave-nas">Proč nás</a></li>
                        <li><a href="#hledate-konkretni-metodiku">Metodiky</a></li>
                        <li><a href="#diagnozy">Diagnózy</a></li>
                        <li><a href="#nas-tym">Zakladatelé</a></li>
                        <li><a href="#reference">Reference</a></li>
                        <li><a href="#spoluprace-pro-sportovni-kluby">Spolupráce</a></li>
                        <li><a href="#" data-nav="no" data-toggle="modal" data-target="#kariera-modal">Kariéra</a></li>
                        <li><a href="#" data-nav="no" data-toggle="modal" data-target="#faq-modal">FAQ</a></li>
                        <li><a href="#" data-nav="no" data-toggle="modal" data-target="#cenik-modal">Ceník</a></li>
                        <li><a href="#kontakty">Kontakt</a></li>
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div><!-- /.container-fluid -->
        </nav>
    </div>
    <div id="vyrovnani-menu"></div>

    <div class="row" id="title">
        <div id="title-kontakty">
            <div class="col-md-3 col-md-offset-2 text-center" id="title-objednavky">
                <div>
                    Objednávky:
                    <a href="tel:+420 775 910 749" rel="nofollow">+420&nbsp;775&nbsp;910&nbsp;749</a>
                </div>
            </div>
            <div class="col-md-2 text-center hidden-sm hidden-xs" id="title-logo">
                <img src="img/titulni-logo.png" title="Fyzioland">
            </div>
            <div class="col-md-3 text-center" id="title-online">
                <div>
                    <!--<a href="rezervace/index">On-line OBJEDNÁVKA</a>-->
                    Objednávky:
                    <a href="mailto:rezervace@fyzioland.cz" rel="nofollow">rezervace@fyzioland.cz</a>
                </div>
            </div>
        </div>
        <div id="slideshow-title">
            <div class="slideshow-item" data-webp="img/titulni-1.webp" data-no-webp="img/titulni-1.jpg" data-position="center center"></div>
            <div class="slideshow-item" data-webp="img/titulni-2.webp" data-no-webp="img/titulni-2.jpg" data-position="center bottom"></div>
        </div>
        <div id="container-zpravy">
            <div>Přijďte na terapii a&nbsp;cvičení dle Prof. Koláře (DNS). <br> Naši terapeuti mají s&nbsp;touto terapií dlouholetou praxi a&nbsp;bohaté zkušenosti.</div>
            <div>Nabídku doplňkových služeb jsem pro Vás rozšířili o&nbsp;indickou antistresovou masáž hlavy. Přijte k&nbsp;nám zrelaxovat a&nbsp;načerpat novou energii.</div>
            <div>Připravujeme pro Vás novou pobočku v&nbsp;Praze Uhříněvesi v&nbsp;moderních a inspirativních prostorách. Otevíráme v&nbsp;dubnu 2018.</div>
            <div>Nabízíme cvičení a&nbsp;terapii pro nejrůznější diagnózy. Více informací najdete na našem webu v&nbsp;sekci Fyzioterapie.</div>
            <div>U nás na terapii nečekáte.<br>Nabízíme první terapii do 48 hodin od objednání.</div>
            <div>Šetříme Váš čas a&nbsp;staráme se o&nbsp;Vaše pohodlí. Proto již brzy spustíme on-line objednávání na našem webu.</div>
            <div>Hledáte-li informaci, zda náš tým terapeutů ovládá konkrétní léčebnou metodiku, podívejte se do sekce Metodiky na našem webu.</div>
        </div>
    </div>
    <div class="row" id="fyzioterapie">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center">
                Fyzioterapie a rekondice<br>
                <div class="underline"></div>
            </div>
            <div class="col-md-3 col-md-offset-1">
                <a href="dospeli">
                    <img src="img/dospeli-kruh.png" title="Dospělí">
                </a>
                <div>
                    Dospělí
                </div>
                <div class="hidden-md hidden-lg vypln"></div>
            </div>
            <div class="col-md-4">
                <a href="deti">
                    <img src="img/deti-kruh.png" title="Děti">
                </a>
                <div>
                    Děti
                </div>
                <div class="hidden-md hidden-lg vypln"></div>
            </div>
            <div class="col-md-3">
                <a href="sportovci">
                    <img src="img/sportovci-kruh.png" title="Sportovci">
                </a>
                <div>
                    Sportovci
                </div>
                <div class="hidden-md hidden-lg vypln"></div>
            </div>
        </div>
    </div>
    <div class="row" id="cviceni">
            <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center">
                Doplňkové služby<br>
                <div class="underline"></div>
            </div>
            <div class="hidden-md hidden-lg vypln"></div>
            <div class="col-md-4">
                <div class="col-md-10 col-md-offset-1 green-panel">
                    <img src="img/cviceni-alternativni-terapie.png" data-mouse-action-image="img/cviceni-alternativni-terapie-modra.png" title="Alternativní terapie">
                    <div class="nadpis">
                        Alternativní terapie
                    </div>
                    <div class="telo">
                        Kromě fyzioterapie postavené na západním přístupu k&nbsp;léčbě nabízíme také vybrané alternativní techniky od indické antistresivé masáže hlavy až po reflexní terapii chodidel. Přijte k&nbsp;nám zrelaxovat a&nbsp;načerpat novou energii.
                    </div>
                    <!--<div class="arrow"></div>-->
                    <div class="vypln"></div>
                </div>
                <div class="hidden-md hidden-lg vypln"></div>
            </div>
            <div class="col-md-4">
                <div class="col-md-10 col-md-offset-1 green-panel">
                    <img src="img/cviceni-skupinova-cviceni.png" data-mouse-action-image="img/cviceni-skupinova-cviceni-modra.png" title="Skupinová cvičení">
                    <div class="nadpis">
                        Skupinová cvičení (připravujeme)
                    </div>
                    <div class="telo">
                        Přiďte k&nbsp;nám protáhnout i&nbsp;posílit Vaše tělo na skupinovém cvičení s&nbsp;nejrůznějšími prvky léčebních fyzioterapeutických metodik s&nbsp;využitím speciálích lan, terabandů a&nbsp;dalších užitečných pomůcek.
                    </div>
                    <!--<div class="arrow"></div>-->
                    <div class="vypln"></div>
                </div>
                <div class="hidden-md hidden-lg vypln"></div>
            </div>
            <div class="col-md-4">
                <div class="col-md-10 col-md-offset-1 green-panel">
                    <img src="img/cviceni-pomucky.png" data-mouse-action-image="img/cviceni-pomucky-modra.png" title="Pomůcky">
                    <div class="nadpis">
                        Pomůcky
                    </div>
                    <div class="telo">
                        Všechny fyzioterapeutické pomůcky a&nbsp;doplňky, se kterými pracují naši terapeuti při individuálních i&nbsp;skupinových cvičeních, si u&nbsp;nás můžete zakoupit za velmi výhodných podmínek.
                    </div>
                    <!--<div class="arrow"></div>-->
                    <div class="vypln"></div>
                </div>
                <div class="hidden-md hidden-lg vypln"></div>
            </div>
        </div>
    </div>
    <div class="row" id="nase-vize">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center">
                Naše poslání<br>
                <div class="underline"></div>
            </div>
            <div class="col-md-12 text-center small">
                Pomáháme pohybem Vašemu tělu ke zdraví.
            </div>
        </div>
    </div>
    <div class="row" id="proc-si-vybrat-prave-nas">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center nadpis">
                Proč si vybrat právě nás?<br>
                <div class="underline"></div>
            </div>
            <div class="col-md-12 text-center small">
                <div class="col-md-6">
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Rychlá pomoc od potíží
                        </div>
                        <p>První termín terapie Vám nabídneme do 48 hodin od zavolání.</p>
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Časově neomezená péče
                        </div>
                        <p>Věnujeme každému klientovi právě tolik času a&nbsp;péče, kolik skutečně potřebuje. Počet ani délka jednotlivých terapií není ničím omezena a&nbsp;je průběžně přizpůsobována na základě dohody terapueta s&nbsp;klientem.</p>
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Optimální volba fyzioterapeutického přístupu
                        </div>
                        <p>Náš tým fyzioterapeutů využívá širokou škálu různých léčebných přístupů a&nbsp;díky tomu Vám navrhne optimální řešení Vašich aktuních nebo chronických pohybových potíží.</p>
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Časová flexibilita
                        </div>
                        <p>Pro ty z&nbsp;Vás, kteří jste časově velmi vytížení, nabízíme termíny terapií v&nbsp;brzkých ranních i&nbsp;pozdn&ích večerních hodinách, případně také o&nbsp;víkednech a&nbsp;státních svátcích.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Unikátní skupinová cvičení
                        </div>
                        <p>Naše skupinová cvičení jsou unikátní svými léčebnými prvky jednotlivých fyzioterapeutických metodik.</p>
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Alternativní terapie
                        </div>
                        <p>Doplňkové alternativní terapie dotváří komplexní nabídku služeb v&nbsp;oblasti péče o&nbsp;tělo formou zdravotního cvičení, rekondičních aktivit a&nbsp;relaxace.</p>
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Profesionální přístup
                        </div>
                        <p>Členové našeho týmu jsou špičkoví odbornící ve svém oboru. Vážíme si každého našeho klienta a&nbsp;jednáme proto s&nbsp;profesionální péčí. Vaše potřeby a&nbsp;přání jsou pro nás na prvním místě.</p>
                    </div>
                    <div class="duvod-proc-nas">
                        <div class="nadpis">
                            Moderní prostory a&nbsp;špičkové vybavení
                        </div>
                        <p>Jsme špičkově vybavené soukromé zdravotnické zařízení působicí v&nbsp;moderních a&nbsp;inspirativních prostorách. Dostanete s&nbsp;k&nbsp;nám pohodlně autem a&nbsp;bezproblémově u&nbsp;nás také zdarma zaparkujete.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-12 vypln"></div>
            <div class="col-md-12 text-center podnadpis">
      
            </div>
         <!--
            <div class="col-md-12 text-center">
                <div id="slideshow-1">
                    <div>
                        <img src="img/slideshow-1-1.png" title="Obrázek 1">
                    </div>
                    <div>
                        <img src="img/slideshow-1-2.png" title="Obrázek 2">
                    </div>
                    <div>
                        <img src="img/slideshow-1-3.png" title="Obrázek 3">
                    </div>
                    <div>
                        <img src="img/slideshow-1-4.png" title="Obrázek 4">
                    </div>
                </div>
            </div>
        -->
         </div>
    </div>
    <div class="row" id="hledate-konkretni-metodiku">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center">
                Naši terapeuti používají při fyzioterapii následující metodiky:<br>
                <div class="underline"></div>
            </div>
            <div class="col-md-12 text-center small">
                <div class="col-md-6">
                    <div class="metodika metodika-1">
                        <div class="nadpis">
                            Dynamická neuromuskulární stabilizace dle prof. Koláře (DNS)
                        </div>
                        <p>Cvičením dle DNS dochází k&nbsp;ideálnímu posílení svalů těla. Cvičení je vhodné napříč téměř všemi věkovými kategoriemi - pro děti od 4&nbsp;let, adolescenty, dospělé, sportovce i&nbsp;seniory. Pomocí DNS je možné úspěšně řešit problémy s&nbsp;krční, hrudní, bederní páteří, výhřezy ploténky, vadné držení těla, břišní diastáza, pooperační stavy, zlepšení svalové kondice u&nbsp;sportovců a&nbsp;mnoho dalšího.</p>
                    </div>
                    <div class="metodika metodika-2">
                        <div class="nadpis">
                            Spirální dynamická stabilizace dle MUDr. Smíška (SMS)
                        </div>
                        <p>Jedná se o&nbsp;cvičení se speciálním lanem. Toto cvičení zpevňuje a&nbsp;posiluje svaly těla a&nbsp;tím zlepšuje jeho stabilitu. Cvičení je vhodné pro řešení řady problémů s&nbsp;páteří a&nbsp;meziobratlovmi ploténkami nebo se skoliózou páteře.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="metodika metodika-3">
                        <div class="nadpis">
                            Míčkování (dle Zdeňky Jebavé) 
                        </div>
                        <p>Metoda používá měkké molitanové míčky, kterými terapeut uvolňuje hrudník a&nbsp;zlepšuje tím dýchání. Metoda je vhodná pro astmatiky, alergiky, děti s&nbsp;chronickou rýmou a&nbsp;chronickým kašlem. Metoda je vhodná i&nbsp;pro domácí aplikaci dětem po zaškolení rodičů našimi terapeuty.</p>
                    </div>
                    <div class="metodika metodika-4">
                        <div class="nadpis">
                            Měkké a&nbsp;mobilizační techniky (dle prof. Karla Lewita) 
                        </div>
                        <p>Metoda uvolňuje přetížené svaly a&nbsp;pomáhá kloubům lépe fungovat.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row" id="diagnozy">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center nadpis">
                Diagnózy<br>
                <div class="underline"></div>
            </div>
            <div class="col-md-12 text-center small">
                <p>Škála diagnóz, které lze řešit pomocí fyzioterapeutických technik a&nbsp;metodik, je velmi široká. Jedná se o&nbsp;tradiční bolesti a&nbsp;zatuhlost krku, zad, bolesti kyčlí, nohou, kolen, chodidel a&nbsp;mnoho dalšího. Nabízíme léčbu preventivní, pooperační i&nbsp;poúrazovou. Pro dětské klienty nabízíme například cvičení pro vadné držení těla, bolesti hlavy, ploché nohy či potíže s&nbsp;motorikou. Pro sportovce nabízíme kompenzační cvičení speciálně připravené pro jednotlivé sporty. Nově se také intenzivně věnujeme uvolňování jizev po všech typech operací.</p>
                <p>Více informací o&nbsp;jednotlivých diagnózách naleznete najdete v&nbsp;sekci fyzioterapie pro jednotlivé skupiny klientů (dospělí, děti, sportovci).</p>
                <p>Nenašli jste požadované informace o&nbsp;konkrétní diagnóze a&nbsp;nejste si proto jisti, zda Vám umíme nabídnout terapii pro Vaši diagnózu? Neváhejte nás kontaktovat, rádi Vám poradíme, zda a&nbsp;jak Vám umíme pomoci.</p>
            </div>
        </div>
    </div>
    <div class="row" id="nas-tym">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center">
                Zakladatelé<br>
                <div class="underline"></div>
            </div>
            <div class="col-md-12 text-center">
                <div id="slideshow-2">
                    <div class="col-md-6 text-center right-border">
                        <img src="img/doktor_1.png" title="Doktor 1">
                        <p class="doktor">Mgr. Jitka Havlická</p>
                        <p class="pozice">Spoluzakladatelka a&nbsp;vedoucí fyzioterapeutka</p>
                        <p>
                            Jitka se celoživotně věnuje fyzioterapii. Úspěšně absolvovala magisterské studium v&nbsp;oboru fyzioterapie a&nbsp;celou řadu odborných kurzů. Jitka má bohaté zkušenosti s&nbsp;širokou škálou diagnóz, jak z&nbsp;ambulantní, tak lůžkové fyzioterapeutické péče.
                            <span class="chci-vedet-vice" data-toggle="modal" data-target="#doktor-1-modal">Chci vědět více.</span>
                        </p>
                    </div>
                    <div class="col-md-6 text-center">
                        <img src="img/doktor_3.png" title="Ing. Jiří Havlický, Ph.D.">
                        <p class="doktor">Ing. Jiří Havlický, Ph.D.</p>
                        <p class="pozice">Spoluzakladatel a&nbsp;ředitel</p>
                        <p>
                            Jiří vystudoval doktorát v&nbsp;oblasti financí a&nbsp;řízení finančních rizik. Věnuje se převážně strategickému rozvoji společnosti a&nbsp;jejímu finančnímu řízení.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade chci-vedet-vice-modal" tabindex="-1" role="dialog" id="doktor-1-modal" aria-labelledby="Doktor1Modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="myModalLabel">Mgr. Jitka Havlická</h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img src="img/doktor_1-plny.jpg" alt="Doktor 1">
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>Vzdělání</strong>
                            </p>
                            <p>2003 - 2005 Univerzita Palackého v&nbsp;Olomouci, studijní obor: Klinická kineziologie a&nbsp;kinezioterapie, titul: Mgr.</p>                            
                            <p>1998 - 2001 Univerzita Palackého v&nbsp;Olomouci, studijní obor: Léčebná rehabilitace a&nbsp;fyzioterapie, titul: Bc.</p>
                            <p>
                                <strong>Praxe</strong>
                            </p>
                            <p>2000 - 2003 Krátkodobé stáže<br>
                               2001 - 2003 Rehabilitační centrum, Opava<br>
                               2003 - 2005 Fakultní nemocnice, Olomouc<br>
                               2005 - 2006 City Med, Praha<br>
                               2006 - 2017 Nemocnice, Říčany<br>
                               2017 - doposud Fyzioland
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <p>
                                <strong>Hlavní zaměření</strong>
                            </p>
                            <p>Jitka ovládá profesionálně širokou škálu fyzioterapeutických technik a&nbsp;metodicky vede tým fyzioterapeutů. V&nbsp;posledních letech se věnuje převážně aplikaci metodik DNS profesora Koláře a&nbsp;SMS dle MUDr. Smíška.</p>
                            
                            <p>
                                <strong>Přehled absolvovaných kurzů</strong>
                            </p>
                            <p>
                                <table class="table-kurzy">
                                    <tr>
                                        <td>2017</td>
                                        <td>Indická antistresová masáž hlavy (Robert Al Aref)</td>
                                    </tr>
                                    <tr>
                                        <td>2017</td>
                                        <td>Dětská noha (PhDr. P.&nbsp;Vondrašová, Ph.D.)</td>
                                    </tr>
                                    <tr>
                                        <td>2016</td>
                                        <td>Dynamická neuromuskulární stabilizace (prof. P.&nbsp;Kolář)</td> 
                                    </tr>
                                    <tr>
                                        <td>2016</td>
                                        <td>Kalceotické (ortotické) zajištění nohou</td>
                                    </tr>
                                    <tr>
                                        <td>2015</td>
                                        <td>Diagnostika a&nbsp;kinezioterapie u idiopatické skoliozy (I.&nbsp;Pallová, Ph.D.)</td>
                                    </tr>
                                    <tr>
                                        <td>2015</td>
                                        <td>Diagnostika a&nbsp;terapie kolenního kloubu (Bartosz Rutowicz, Ph.D.)</td>
                                    </tr>
                                    <tr>
                                        <td>2014</td>
                                        <td>Nespecifické mobilizace (Z.&nbsp;Jebavá)</td>
                                    </tr>
                                    <tr>
                                        <td>2014</td>
                                        <td>Míčková facilitace pro fyzioterapeuty (Z.&nbsp;Jebavá)</td>
                                    </tr>
                                    <tr>
                                        <td>2011</td>
                                        <td>Maxtaping (V.&nbsp;Szlaurová, Dis.)</td>
                                    </tr>
                                    <tr>
                                        <td>2011</td>
                                        <td>SM systém I, II, skoliozy (MUDr. Z.&nbsp;Smíšková, MUDr. R.&nbsp;Smíšek)</td>
                                    </tr>
                                    <tr>
                                        <td>2011</td>
                                        <td>Course of balance rehabilitation (Anne Shumway-Cook, M.&nbsp;Woolacott)</td>
                                    </tr>
                                    <tr>
                                        <td>2010</td>
                                        <td>Bazální programy a&nbsp;podprogramy ve fyzioterapii na neurofyziologickém podkladě (J.&nbsp;Čápová)</td>
                                    </tr>
                                    <tr>
                                        <td>2008</td>
                                        <td>Kineziologie dolní končetiny a&nbsp;nohy, terapeutické postupy (Mgr. K.&nbsp;Máčková)</td>
                                    </tr>
                                    <tr>
                                        <td>2008</td>
                                        <td>Bobath koncept (Bc. H.&nbsp;Kafková)</td>
                                    </tr>
                                    <tr>
                                        <td>2007</td>
                                        <td>Diagnostické a&nbsp;terapeutické postupy využívané při stabilizaci páteře (Mgr. M.&nbsp;Veverková)</td>  
                                    </tr>
                                    <tr>
                                        <td>2006</td>
                                        <td>Diagnostika a&nbsp;terapie funkčních poruch pohybové soustavy (Mgr. V.&nbsp;Verchozinová)</td>
                                    </tr>
                                    <tr>
                                        <td>2003</td>
                                        <td>Mikrosystém nohy (Mgr. Stanislav Zapletal)</td>
                                    </tr>
                                    <tr>
                                        <td>2002</td>
                                        <td>Posturální terapie na bázi vývojové kineziologie (Jarmila Čápová)</td>
                                    </tr>
                                </table>
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
    <div class="row" id="reference">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center">
                Reference (řekli o nás)<br>
                <div class="underline"></div>
            </div>
            <div class="col-md-12 text-center reference-div reference-1">
                <div class="nadpis">
                    Alfons B.
                </div>
                <p>
                    Navštěvuji Fyzioland pravidelně už skoro rok. Od té doby, co jsem začal chodit k terapeutce Jitce se mi nejprve rychle výrazně snížily mé bolesti a&nbsp;dnes už žádné pohybové problémy prakticky nemám. Chodím dál na fyzioterapii a&nbsp;učím se starat dál o&nbsp;své tělo pohybovými cvičeními tak, abych předešel dalším potenciálním problémům a&nbsp;bolestem... Moc děkuji.
                </p>
            </div>
            <div class="col-md-12 text-center reference-div reference-2">
                <div class="nadpis">
                    Jaroslav J.
                </div>
                <p>
                    Dlouho jsme hledali profesionální fyzioterapii pro naši dceru, která ve věnuje intenzivně baletu. Vyzkoušeli jsme hodně terapeutů, ale až Jitka z&nbsp;Fyziolandu dokázala naší dceři účinně pomáhat vhodným cvičením od bolesti. Od té doby navštěvujeme Jitku pravidelně a&nbsp;jsme za to moc rádi.
                </p>
            </div>
            <div class="col-md-12 text-center reference-div reference-3">
                <div class="nadpis">
                    Kateřina Č.
                </div>
                <p>
                  Poté co jsem začala podnikat se mi projevovaly různé bolesti zad při větší zátěži. Zkusila jsem navštívit fyzioterapii u&nbsp;Jitky a&nbsp;byla jsem velmi mile překvapena, jak mi dokázala během relativně krátké doby účinně pomoci. Jitko děkuji ... 
                </p>
            </div>
        </div>
    </div>
    <div class="row" id="spoluprace-pro-sportovni-kluby">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="col-md-12 text-center">
                Spolupráce pro sportovní kluby<br>
                <div class="underline"></div>
            </div>
            <div class="col-md-6 text-center">
                <div id="slideshow-3">
                    <div>
                        <img src="img/fotka-spoluprace-1.jpg" title="Spolupráce pro sportovní kluby">
                    </div>
                    <div>
                        <img src="img/fotka-spoluprace-2.jpg" title="Spolupráce pro sportovní kluby">
                    </div>
                    <div>
                        <img src="img/fotka-spoluprace-3.jpg" title="Spolupráce pro sportovní kluby">
                    </div>
                    <div>
                        <img src="img/fotka-spoluprace-4.jpg" title="Spolupráce pro sportovní kluby">
                    </div>
                </div>
            </div>
            <div class="col-md-6 popis small">
                <p>Pro sportovní kluby, jejich členy nebo individuální sportovce sestavujeme individuální programy v&nbsp;podobě kompenzačních cvičení připravené na míru pro jednotlivé sporty.</p>
                <p>Každý sport klade jinou zátěž na svalový a&nbsp;kloubový aparát lidského těla. Většina sportů vede často k&nbspjednostranné zátěži vybraných svalových skupin, což může vést ke&nbsp;vzniku pohybových problémů a&nbspbolestí v&nbsppozdějším věku. Cílem individuálních kompenzačních cvičení je doplnit vhodným cvičením pohybové aktivity sportovců tak, aby vyvažovaly (kompenzovaly) jednostrannou zátěž těla z&nbsp;daného sportu a&nbsp;zajistily zdravý a&nbsppřirozený výoj aktivně sportujících klientů.</p>
                <p>Kompenzační cvičení působí také jako účinný nástroj prevence sportovních úrazů.</p>
                <p>Nabízíme možnost spolupráce našeho fyzioterapeuta přímo v prostorách Vašeho sportovního klubu nebo terapii a&nbspcvičení v&nbsp;naších cvičebnách.</p>
                               
                <button class="btn chci-spolupracovat" data-toggle="modal" data-target="#chci-spolupracovat-modal">Mám zájem o&nbsp;spolupráci</button>
            </div>
        </div>
    </div>
    <div class="modal fade" tabindex="-1" role="dialog" id="chci-spolupracovat-modal" aria-labelledby="ChciSpolupracovatModal">
        <div class="modal-dialog" role="document">
            <form action="sportovni-kluby" method="post">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h3 class="modal-title" id="myModalLabel">Spolupráce se sportovními kluby</h3>
                    </div>
                    <div class="modal-body">
                        <p>Pokud byste s&nbsp;námi rádi navázali spolupráci na úrovni sportovních klubů, vyplňte prosím formulář níže a&nbsp;my Vás budeme v&nbsp;krátké době kontaktovat.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jmeno">Jméno</label>
                                    <input type="text" class="form-control" id="jmeno" name="jmeno" placeholder="Vaše jméno" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="prijmeni">Příjmení</label>
                                    <input type="text" class="form-control" id="prijmeni" name="prijmeni" placeholder="Vaše příjmení" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">E-mail</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="E-mailová adresa" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telefon">Telefon</label>
                                    <input type="tel" class="form-control" id="telefon" name="telefon" placeholder="Telefonní kontakt" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="zamereni">Zaměření sportovního klubu</label>
                                    <input type="text" class="form-control" id="zamereni" name="zamereni" placeholder="Zaměření" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="poznamka">Doplňující informace pro nás</label>
                                    <textarea class="form-control" rows="4" id="poznamka" name="poznamka" placeholder="Textová poznámka" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-center">
                            <div class="g-recaptcha" data-sitekey="6LcR5TQUAAAAAEbcz2NRs8gTzUpwuqwFsEBlt2o2" data-callback="recaptchaCallback"></div>
                            <input type="hidden" id="recaptcha" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                        <button type="submit" class="btn btn-success" name="odeslat">Odeslat formulář</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade kariera-modal" tabindex="-1" role="dialog" id="kariera-modal" aria-labelledby="KarieraModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="myModalLabel">Aktuálně nabízíme tyto otevřené pracovní pozice</h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="pozice">
                                Fyzioterapeut, Fyzioterapeutka
                            </div>
                            <div>
                                <div class="nadpis">
                                    <p>Rozšiřujeme náš tým a hledáme fyzioterapeutky nebo fyzioterapeuty, kteří dělají rádi svoji práci, dělají ji dobře a očekávají za to adekvátní finanční ohodnocení a uznání.<br></p>
                                    <p> OD KANDIDÁTŮ OČEKÁVÁME:</p>
                                </div>
                                <ul>
                                    <li>Proklientský přístup</li>
                                    <li>Časovou flexibilitu</li>
                                    <li>Příjemné vystupování</li>
                                    <li>Chuť na sobě stále pracovat</li>
                                    <li>Praxi v oboru v délce alespoň 2 roky</li>
                                </ul>
                            </div>
                            <div>
                                <div class="nadpis">
                                    NAŠIM BUDOUCÍM KOLEGŮM NABÍZÍME:
                                </div>
                                <ul>
                                    <li>Pestrou práci v příjemném kolektivu</li>
                                    <li>Odpovídající finanční ohodnocení</li>
                                    <li>Finanční bonusy za dobře odvedenou práci</li>
                                    <li>Finanční příspěvky na vzdělávání</li>
                                    <li>Práci v nových a moderních prostorách</li>
                                    <li>Možnost podílet se na celkovém směřování firmy</li>
                                   
                                </ul>
                                <div class="nadpis">
                                    <p>OČEKÁVANÝ TERMÍN NÁSTUPU: 4/2018</p>
                                    <p>MÍSTO VÝKONU PRÁCE: Praha Uhříněves</p>
                                    <p>Máte-li zájem pošlete nám prosím Váš životopis na adresu nabor@fyzioland.cz a my se Vám brzy ozveme. Těšíme se na Vás. Tým Fyzioland.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade faq-modal" tabindex="-1" role="dialog" id="faq-modal" aria-labelledby="FAQModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="myModalLabel">Často kladené otázky (FAQ)</h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="otazka">
                                Potřebujete poukaz od lékaře?
                            </div>
                            <div class="odpoved">
                                Ne, poukaz od lékaře u&nbsp;nás nepotřebujete. Nabídneme Vám odpovídající péči na Vaše potíže i&nbsp;bez žádanky od lékaře. Pokud Vám však lékař předepsal fyzioterpii nebo rehabilitace, určitě si vemte poukaz s&nbsp;sebou.
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="otazka">
                                Proč nemáme smlouvy se zdravotními pojišťovnami?
                            </div>
                            <div class="odpoved">
                                Současný systém veřejného zdravotního pojištění neumožňuje poskytovat špičkové služby, které našim klientům účinně pomáhají. Důvodem je skutečnost, že zdravotní pojišťovna striktně předepisuje délku i&nbsp;počet možných terapií. Jak délka jedné terapie, tak jejich celkový počet jsou dle našich dlouholetých zkušeností v&nbsp;drtivé většině případů nevyhovující a&nbsp;nedostačující. Proto poskytujeme péči za úhradu a&nbsp;díky tomu v&nbsp;té nejvyšší možné kvalitě, kterou od nás naši klieti očekávají.
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="otazka">
                                Jak dlouhá je čekací doba na terapii?
                            </div>
                            <div class="odpoved">
                                U nás na terapii nečekáte. Nabídneme Vám první termín terapie do 48 hodin od objednání.
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="otazka">
                                Je možné se objednat i mimo standardní pracovní dobu?
                            </div>
                            <div class="odpoved">
                                Ano. Jsme velmi časově flexibilní a&nbsp;snažíme se Vám vycházet vstříc. Nabízíme terapii v&nbsp;brzkých ranních i&nbsp;večerních hodinách, případně také o&nbsp;víkendech nebo státních svátcích. Neváhejte nás kontaktovat a&nbsp;určitě najdeme pro Vás vhodný termín.
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="otazka">
                                Je možné platit kartou?
                            </div>
                            <div class="odpoved">
                                Bohužel v&nbsp;současné chvíli to možné není. Ale pracujeme na tom a&nbsp;brzy Vám platby kartou umožníme. Nyní je možné platit v&nbsp;hotovosti při ukončení terapie nebo na fakturu, kterou Vám náš personál vystaví. Děkujeme za pochopení.
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="otazka">
                                Lze u nás zaparkovat a kolik to stojí?
                            </div>
                            <div class="odpoved">
                                Ano, parkování Vám nabízíme zdarma přímo před naší budovou.
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="otazka">
                                Nenašli jste odpověď na Váš dotaz?
                            </div>
                            <div class="odpoved">
                                Neváhejte nás kontaktovat telefonicky na čísle + 420 775 910 749 nebo na emailem na dotazy@fyzioland.cz.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade cenik-modal" tabindex="-1" role="dialog" id="cenik-modal" aria-labelledby="CenikModal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" id="myModalLabel">Ceník našich služeb</h3>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 nad-cenikem">
                            Dbáme na to, aby náš ceník byl jednoduchý a&nbsp;srozumitelný. Záleží nám na tom, abyste vždy přesně věděli za jaké služby a&nbsp;kolik budete platit.
                        </div>
                    </div>
                    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingSix">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" href="#collapseSix" aria-expanded="true" aria-controls="collapseSix">
                                        <div class="row">
                                            <div class="col-xs-7">
                                                Členství
                                            </div>
                                            <div class="col-xs-5 cena">
                                                zdarma
                                            </div>
                                        </div>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseSix" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSix">
                                <div class="panel-body">
                                    Na rozdíl od konkurence neúčtujeme žádné jednorázové ani pravidelné poplatky za možnost využívat našich služeb. Naši terapeuti Vám rádi poradí ohledně počtu, délky a&nbsp;zaměření jednotlivých terapií dle Vašich potřeb a&nbsp;přání. U nás vždy platíte pouze za konkrétní služby, které Vám poskytneme.
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingSeven">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" href="#collapseSeven" aria-expanded="true" aria-controls="collapseSeven">
                                        <div class="row">
                                            <div class="col-xs-7">
                                                Kinezilogický rozbor a&nbsp;individuální fyzioterapeutický plán
                                            </div>
                                            <div class="col-xs-5 cena">
                                                1 200 Kč
                                            </div>
                                        </div>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseSeven" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSeven">
                                <div class="panel-body">
                                    Kinezilogický rozbor slouží ke zjištění chybných pohybových zvyků (stereotypů), které mohou být příčinou aktuálních potíží nebo mohou být problematické v&nbsp;delším horizontu. Naši terapeuti Vám navrhnou vhodný plán fyzioterarapie nebo rekondičních cvičení, který povede ke zmírnění nebo úplnému odstranění Vašich potíží.
                                </div>
                            </div>
                        </div>       
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingOne">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        <div class="row">
                                            <div class="col-xs-7">
                                                Fyzioterapie nebo rekondiční terapie (délka terapie 55 minut)
                                            </div>
                                            <div class="col-xs-5 cena">
                                                700 - 900 Kč
                                            </div>
                                        </div>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                                <div class="panel-body">
                                    Výsledná cena terapie závisí na zvoleném přístupu k&nbsp;léčbě nebo rekondičního cvičení. Terapeut volí přístup vždy po dohodě s&nbsp;klientem na základě jeho konkrétních potíží a&nbsp;cílů, kterých má být terapií dosaženo. Nejčastější cena za 55 minut terapie je 800 Kč. Ve výjimečných případech poskytujeme také délku terapie 30 minut za cenu 450 Kč.
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingTwo">
                                <h4 class="panel-title">
                                    <a class="collapsed" role="button" data-toggle="collapse" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        <div class="row">
                                            <div class="col-xs-7">
                                                Příplatek za terapii o&nbsp;víkendech a&nbsp;státních svátcích
                                            </div>
                                            <div class="col-xs-5 cena">
                                                200 Kč
                                            </div>
                                        </div>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                                <div class="panel-body">
                                    Pro ty z Vás, kteří jste velmi časově vytížení, nabízíme možnost terapie o&nbsp;víkendech nebo státních svátcích na základě indivduální dohody za příplatek.
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingFour">
                                <h4 class="panel-title">
                                    <a class="collapsed" role="button" data-toggle="collapse" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        <div class="row">
                                            <div class="col-xs-7">
                                                Elektroléčba
                                            </div>
                                            <div class="col-xs-5 cena">
                                                150 Kč
                                            </div>
                                        </div>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                                <div class="panel-body">
                                    Tato položka je účtována pouze v případě, že je Vám v průběhu terapie poskytnuta na základě dohody s&nbsp;terapeutem elektroléčba.
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingFour">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" href="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
                                        <div class="row">
                                            <div class="col-xs-7">
                                                Tejpování (bez terapie)
                                            </div>
                                            <div class="col-xs-5 cena">
                                                200 - 350 Kč
                                            </div>
                                        </div>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
                                <div class="panel-body">
                                    Tato položka je účtována v případě, pokud máte zájem o&nbsp;tejpování vybrané části těla bez samotné fyzioterapie. Tejpování trvá 15 až 25 minut a&nbsp;v&nbsp;ceně je již zahrnut materiál (tejp). Výsledná cena v&nbsp;uvedeném rozmezí závisí na části těla, na které je tejpování aplikováno.
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingFive">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" href="#collapseFive" aria-expanded="true" aria-controls="collapseFive">
                                        <div class="row">
                                            <div class="col-xs-7">
                                                Tejp - materiál
                                            </div>
                                            <div class="col-xs-5 cena">
                                                2 Kč/cm
                                            </div>
                                        </div>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseFive" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive">
                                <div class="panel-body">
                                    Tato položka odpovídá materiálovým nákladům na tejpy, které Vám terapeut na základě dohody s&nbsp;Vámi v&nbsp;rámci fyzioterapie nebo rekondičního cvičení aplikuje. Samotná aplikace tejpů je v&nbsp;tomto případě již zahrnuta v&nbsp;ceně terapie.
                                </div>
                            </div>
                        </div>
                        <div class="row">
                        <div class="col-md-12 pod-cenikem">
                            <p>Bližší informace k jednotlivým položkám ceníku je dostupná po rozkliknutí dané položky</p>
                            <p>Platba probíhá v hotovosti při ukončení terapie. V případě zájmu je možné platit za naše služby také na fakturu. Připravujeme pro Vás rovněž možnost placení kartou.</p>
                            <p>V&nbsp;současné době nejsme plátci DPH.</p>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Zavřít</button>
                </div>
            </div>
        </div>
    </div>
    <!--
    <div class="row" id="chci-vice-informaci">
        <div class="col-md-5">
            <img src="img/chci-vice-informaci.png" title="Chci více informací">
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
                <input type="hidden" name="token-zeton" value="<?= bin2hex(random_bytes(16)); ?>">
            </form>
        </div>
    </div>
    -->

    <?php
    include "footer.php";
    ?>