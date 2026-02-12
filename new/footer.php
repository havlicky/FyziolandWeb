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
                            Olivova 2585/45, Říčany<br>
                            E: jitka.havlicka@fyzioland.cz<br>
                            M:  +420 775 910 749
                        </div>
                        <div class="ikony">
                            <a href="mailto:jitka.havlicka@fyzioland.cz"><img src="<?= $absolutePath ?>img/kontakty-ikona-mail.png" title="E-mail"></a>
                            <img src="<?= $absolutePath ?>img/kontakty-ikona-tel2.png" title="Telefon">
                        </div>
                    </div>
                    <img src="<?= $absolutePath ?>img/vodorovna-cara.png" alt="" class="cara">
                    <div class="col-md-4">
                        <div class="nadpis">
                            <img src="<?= $absolutePath ?>img/kontakty-kde-nas-najdete.png" title="Kde nás najdete">
                            Kde nás najdete
                        </div>
                        <iframe class="mapka" title="Mapa" id="googlemapa" src="" width="270" height="270" frameborder="0" style="border:0" allowfullscreen></iframe>
                    </div>
                    <img src="<?= $absolutePath ?>img/vodorovna-cara.png" alt="" class="cara">
                    <div class="col-md-4">
                        <div class="nadpis">
                            <img src="<?= $absolutePath ?>img/kontakty-odkazy.png" title="Odkazy">
                            Jak se k nám dostanete
                        </div>
                        <ul class="odkazy">
                            <li>10 minut pěšky z Masarykova náměstí <br> (ulicí Olivova přímo z náměstí)</li>
                            <li>5 minut pěšky z Komenského náměstí <br> (ulici Třebízského do ulice Olivova)</li>
                            <li>6 minut autem z dálnice D1 <br> (sjezd Říčany 12km D1)</li>
                            <li>8 minut autem z Uhříněvsi <br> (po ulici Přátelství)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="posun-nahoru" title="Návrat nahoru"></div>
    </body>

    <script>
        $(document).ready(function () {
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
                autoplaySpeed: 6000,
                arrows: false,
                pauseOnHover: false
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

            $("#slideshow-2").slick({
                infinite: true,
                slidesToShow: 3,
                slidesToScroll: 1,
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
                        arrows: false,
                        autoplay: true
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
                arrows: true,
                pauseOnHover: false,
                arrows: false,
                dots: true
            });
            
            $("#container-zpravy").on("init", function(slick) {
                $("#container-zpravy").css("visibility", "visible");
            });
            
            $("#container-zpravy").slick({
                infinite: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 3000,
                initialSlide: <?= rand(0,3) ?>,
                swipe: false
            });
            
            $("#googlemapa").attr("src", "https://api.mapy.cz/frame?params=%7B%22x%22%3A14.665412555701181%2C%22y%22%3A49.99042622481753%2C%22base%22%3A%2227%22%2C%22layers%22%3A%5B%5D%2C%22zoom%22%3A16%2C%22url%22%3A%22https%3A%2F%2Fmapy.cz%2Fs%2F28vnA%22%2C%22mark%22%3A%7B%22x%22%3A%2214.66727937317556%22%2C%22y%22%3A%2249.98919841211993%22%2C%22title%22%3A%22ulice%20Olivova%202585%2F45%2C%20%C5%98%C3%AD%C4%8Dany%22%7D%2C%22overview%22%3Afalse%7D&amp;width=400&amp;height=280&amp;lang=cs");
            
            $("#chci-spolupracovat-modal form").submit(function(event) {
                $(".g-recaptcha").removeClass("redBorder");
                if ($("#recaptcha").val() !== "1") {
                    $(".g-recaptcha").addClass("redBorder");
                    event.preventDefault();
                }
            });
            
            $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();
        });
        
        recaptchaCallback = function (parameter) {
            $("#recaptcha").val(1);
        };
    </script>
</html>