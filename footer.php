            <div class="row" id="kontakty">
                <div class="col-lg-10 col-lg-offset-1">
                    <div class="col-md-4">
                        <div class="nadpis">
                            <img src="<?= $absolutePath ?>img/kontakty-domecek.png" title="Kontakt">
                            Kontakt
                        </div>
                        <img class="logo" src="<?= $absolutePath ?>img/kontakty-logo.png" title="Fyzioland">
                        <div class="adresa">
                            Provozovna Fyzioland s.r.o.<br>
                            Kašovická 1608/4, Praha 10 - Uhříněves<br>
                            E: <a href="mailto:info@fyzioland.cz">info@fyzioland.cz</a><br>
                            Fyzioterapie: <a href="tel:+420 775 910 749" rel="nofollow">+420 775 910 749</a><br>
                            Ergoterapie: <a href="tel:+420 608 856 278" rel="nofollow">+420 608 856 278</a><br>
                            Jiří Havlický, ředitel: <a href="tel:+420 606 769 852" rel="nofollow">+420 606 769 852</a>
                        </div>
                        <div class="ikony">
                            <a href="mailto:jitka.havlicka@fyzioland.cz"><img src="<?= $absolutePath ?>img/kontakty-ikona-mail.png" title="E-mail"></a>
                            <a href="tel:+420 775 910 749" rel="nofollow"><img src="<?= $absolutePath ?>img/kontakty-ikona-tel2.png" title="Telefon"></a>
                        </div>
                    </div>
                    <img src="<?= $absolutePath ?>img/vodorovna-cara.png" alt="" class="cara">
                    <div class="col-md-4">
                        <div class="nadpis">
                            <img src="<?= $absolutePath ?>img/kontakty-kde-nas-najdete.png" title="Kde nás najdete">
                            Kde nás najdete
                        </div>
                        <iframe style="border:none; margin-top: 15px; " src="https://frame.mapy.cz/s/fenuzavoso" width="270" height="270" frameborder="0"></iframe>
                        <!--<iframe class="mapka" title="Mapa" id="googlemapa" src="" width="270" height="270" frameborder="0" style="border:0" allowfullscreen></iframe>-->
                    </div>
                    <img src="<?= $absolutePath ?>img/vodorovna-cara.png" alt="" class="cara">
                    <div class="col-md-4">
                        <div class="nadpis">
                            <img src="<?= $absolutePath ?>img/kontakty-odkazy.png" title="Odkazy">
                            Jak se k nám dostanete
                        </div>
                        <ul class="odkazy">
                            <li>3 minuty pěšky z autobusové zastávky Lnářská</li>
                            <li>5 minut autem z dálnice D1 <br> (6. km D1, sjezd Průhonice/Uhříněves)</li>
                            <li>5 minut pěšky z náměstí Protifašistických bojovníků</li>
                            <li>8 minut autem z Říčan</li>
                            <li>10 minut pěšky z Nového náměstí</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="posun-nahoru" title="Návrat nahoru"></div>
    </body>

    <script>
        $(document).ready(function () {
            $("a[data-nav='no']").click(function(event) {
               event.preventDefault(); 
            });
            
            $('.modal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                button.one('focus', function (event) {
                    $(this).blur();
                });
            });
            
            var navbarHeight = $("#navbar nav").height();
            $("#vyrovnani-menu").height(navbarHeight);

            $("#cviceni .green-panel").mouseover(function () {
                var img = $(this).find("img");
                var src = img.attr("src");
                img.attr("src", img.attr("data-mouse-action-image"));
                img.attr("data-mouse-action-image", src);
            });
            $("#cviceni .green-panel").mouseout(function () {
                var img = $(this).find("img");
                var src = img.attr("src");
                img.attr("src", img.attr("data-mouse-action-image"));
                img.attr("data-mouse-action-image", src);
            });
            
            $(".posun-nahoru").click(function() {
                $('html, body').animate({
                    scrollTop: 0
                }, 800);
            });
            
            $(window).scroll(function() {
                if ($(window).scrollTop() === 0) {
                    $(".posun-nahoru").fadeOut();
                } else {
                    $(".posun-nahoru").fadeIn();
                }
            });

            $("#navbar a").on('click', function (event) {
                // automatické sbalení menu po kliknutní na něj, ale pouze v kompaktním zobrazení
                if ($("body").width() <= 767) {
                    $("#navbar .navbar-toggle").click();
                }

                if (this.hash !== "") {

                    event.preventDefault();
                    var hash = this.hash;

                    $('html, body').animate({
                        scrollTop: $(hash).offset().top - navbarHeight
                    }, 800, function () {
                        //window.location.hash = hash;

                    });
                }
            });
            
            $(".slideshow-item").each(function() {
                if ($("html").hasClass("webp")) {
                    $(this).css("background-image", "url(" + $(this).attr("data-webp") + ")");
                } else {
                    $(this).css("background-image", "url(" + $(this).attr("data-no-webp") + ")");
                }
                $(this).css("background-position", $(this).attr("data-position"));
            });
            
            $("#slideshow-title").on("init", function(slick) {
                $("#slideshow-title").css("visibility", "visible");
            });
            
            $("#slideshow-title").slick({
                infinite: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                swipe: false,
                autoplaySpeed: 5000,
                arrows: false,
                pauseOnHover: false
            });
            
            // navázání carouselu se zprávami, aby se točily společně
            $("#slideshow-title").on("beforeChange", function(event, slick, currentSlide, nextSlide){
                $("#container-zpravy").slick("slickNext");
            });

            $("#slideshow-1").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });
            
            $("#slideshow-SN").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                pauseOnHover: false,
                autoplaySpeed: 2000,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });
            
            $("#slideshow-SI").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                pauseOnHover: false,
                autoplaySpeed: 2000,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });
            
            $("#slideshow-NVT").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                pauseOnHover: false,
                autoplaySpeed: 2000,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });
            
            $("#slideshow-MABC").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                pauseOnHover: false,
                autoplaySpeed: 2000,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });
            
            $("#slideshow-GM").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                pauseOnHover: false,
                autoplaySpeed: 2000,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });                        

            $("#slideshow-2").slick({
                infinite: true,
                slidesToShow: 6,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 5000,
                pauseOnHover: true,
                responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                ]
            });
            
            $("#slideshow-3").slick({
                infinite: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                swipe: false,
                autoplaySpeed: 3000,
                pauseOnHover: false,
                arrows: false,
                dots: true
            });
            
             $("#slideshow-4").slick({
                infinite: true,
                slidesToShow: 2,
                slidesToScroll: 1,
                autoplay: true,
                swipe: false,
                autoplaySpeed: 3000,
                pauseOnHover: true,
                arrows: false,
                dots: true,
                responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                ]
            });
            
            $("#slideshow-5").slick({
                infinite: true,
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true,
                swipe: false,
                autoplaySpeed: 3000,
                pauseOnHover: true,
                arrows: false,
                dots: true,
                responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                ]
            });
            
             $("#slideshow-6").slick({
                infinite: true,
                slidesToShow: 5,
                slidesToScroll: 1,
                autoplay: true,
                swipe: false,
                autoplaySpeed: 3000,
                pauseOnHover: true,
                arrows: false,
                dots: true,
                responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 5,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                ]
            });
            
            $("#slideshow-detskaErgo").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                pauseOnHover: false,
                autoplaySpeed: 2000,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });
            
            $("#slideshow-detskaFyzio").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                pauseOnHover: false,
                autoplaySpeed: 2000,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });
            
            $("#slideshow-dospelaFyzio").slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                pauseOnHover: false,
                autoplaySpeed: 2000,
                responsive: [
                {
                    breakpoint: 1680,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true,
                        arrows: false
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
                ]
            });
            
            $("#container-zpravy").on("init", function(slick) {
                $("#container-zpravy").css("visibility", "visible");
            });
            
            var pocetZprav = $("#container-zpravy>div").length;
            var pocatecniZprava = Math.floor(Math.random()*(pocetZprav+1)) - 1;
            $("#container-zpravy").slick({
                infinite: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: false,
                initialSlide: pocatecniZprava,
                swipe: false,
                arrows: false,
                adaptiveHeight: true
            });
            
            $("#googlemapa").attr("src", "https://api.mapy.cz/frame?params=%7B%22x%22%3A14.599741056269647%2C%22y%22%3A50.02630433834023%2C%22base%22%3A%221%22%2C%22layers%22%3A%5B%5D%2C%22zoom%22%3A16%2C%22url%22%3A%22https%3A%2F%2Fmapy.cz%2Fs%2F2uQVq%22%2C%22mark%22%3A%7B%22x%22%3A%2214.599741056269647%22%2C%22y%22%3A%2250.02630433834023%22%2C%22title%22%3A%22Ka%C5%A1ovick%C3%A1%201608%2F4%2C%20Praha%22%7D%2C%22overview%22%3Afalse%7D&amp;width=400&amp;height=280&amp;lang=cs");
            
            $("#chci-spolupracovat-modal form").submit(function(event) {
                $(".g-recaptcha").removeClass("redBorder");
                if ($("#recaptcha").val() !== "1") {
                    $(".g-recaptcha").addClass("redBorder");
                    event.preventDefault();
                }
            });
            
            var messageBoxDelay = $(".message-box").attr("data-delay");
            $(".message-box").delay(500).fadeIn().delay(messageBoxDelay).fadeOut();
        });
        
        recaptchaCallback = function (parameter) {
            $("#recaptcha").val(1);
        };
    </script>
</html>