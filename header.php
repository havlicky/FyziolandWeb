<?php 

require_once "php/class.messagebox.php";
require_once "php/class.settings.php";

session_start();

$absolutePath = "/";
if (!isset($pageTitle)) {
    $pageTitle = "Fyzioterapie, ergoterapie | Fyzioland";
}
if (!isset($pageKeywords)) {
    $pageKeywords = "fyzioterapie, ergoterapie, dětská ergoterapie, ergoterapie v domácím prostředí, Uhříněves, rehabilitace, jóga, Kolář, DNS, jizvy, tejpování";
}
if (!isset($pageDescription)) {
    $pageDescription = "Fyzioterapie pro dospělé, děti a sportovce, ergoterapie pro děti a dospělé, skupinová cvičení, školení a konzultace";
}


try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

?>
<!doctype html>
<html lang="cs">
    <head>
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-KBVK2RK');</script>
        <!-- End Google Tag Manager -->
        
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-109159911-1"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'UA-109159911-1');
        </script>
        
        <title><?= $pageTitle ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        
        <meta name="robots" content="index,follow">
        <meta name="keywords" content="<?= $pageKeywords ?>">
        <meta name="description" content="<?= $pageDescription ?>">

        <link rel="stylesheet" href="<?= $absolutePath ?>css/bootstrap_3.3.7.min.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/Blogger Sans/BloggerSans.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/slick-1.8.0.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/slick-theme-1.8.0.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/custom.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/jquery-ui.css">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/dt-1.10.16/fh-3.1.3/r-2.2.0/datatables.min.css"/>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/css/select2.min.css" rel="stylesheet" />
        
        <script src="<?= $absolutePath ?>js/jquery-1.12.4.min.js"></script>
        <script src="<?= $absolutePath ?>js/jquery-ui.min.js"></script>
        <script src="<?= $absolutePath ?>js/bootstrap_3.3.7.min.js"></script>
        <script src="<?= $absolutePath ?>js/slick-1.8.0.min.js"></script>
        <script src="<?= $absolutePath ?>js/modernizr-webp.js"></script>
        <script src='https://www.google.com/recaptcha/api.js'></script>
        <script type="text/javascript" src="https://cdn.datatables.net/v/bs/dt-1.10.16/fh-3.1.3/r-2.2.0/datatables.min.js"></script>
        <script type="text/javascript" src="<?= $absolutePath ?>js/jquery.maskedinput.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/js/select2.min.js"></script>
        <script src="<?= $absolutePath ?>js/moment.min.js"></script>
        <script src="//cdn.ckeditor.com/4.9.2/full/ckeditor.js"></script>

        <link rel="apple-touch-icon" sizes="57x57" href="<?= $absolutePath ?>img/favicon/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="<?= $absolutePath ?>img/favicon/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="<?= $absolutePath ?>img/favicon/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="<?= $absolutePath ?>img/favicon/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="<?= $absolutePath ?>img/favicon/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="<?= $absolutePath ?>img/favicon/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="<?= $absolutePath ?>img/favicon/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="<?= $absolutePath ?>img/favicon/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="<?= $absolutePath ?>img/favicon/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="192x192"  href="<?= $absolutePath ?>img/favicon/android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $absolutePath ?>img/favicon/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="<?= $absolutePath ?>img/favicon/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= $absolutePath ?>img/favicon/favicon-16x16.png">
        <link rel="manifest" href="<?= $absolutePath ?>img/favicon/manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="<?= $absolutePath ?>img/ms-icon-144x144.png">
        <meta name="theme-color" content="#ffffff">
        
        
    </head>
    <body data-spy="scroll" data-target="#navbar" data-offset="50">
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KBVK2RK"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        
        <?php 
            if (isset($_SESSION["messageBox"])) {
                echo "<div class='message-box " . $_SESSION["messageBox"]->getClass() . "' data-delay='" . $_SESSION["messageBox"]->getDelay() . "'>";
                echo $_SESSION["messageBox"]->getText(); 
                echo "</div>";
                
                unset($_SESSION["messageBox"]);
            }
        ?>
        