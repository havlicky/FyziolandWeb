<?php 

require_once "php/class.messagebox.php";
require_once "php/class.settings.php";

$absolutePath = "/new/";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;
    
    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

session_start();

?>
<!doctype html>
<html lang="cs">
    <head>
        <title>Fyzioland.cz</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

        <link rel="stylesheet" href="<?= $absolutePath ?>css/bootstrap_3.3.7.min.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/Blogger Sans/BloggerSans.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/slick-1.8.0.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/slick-theme-1.8.0.css">
        <link rel="stylesheet" href="<?= $absolutePath ?>css/custom.css">

        <script src="<?= $absolutePath ?>js/jquery-1.12.4.min.js"></script>
        <script src="<?= $absolutePath ?>js/bootstrap_3.3.7.min.js"></script>
        <script src="<?= $absolutePath ?>js/slick-1.8.0.min.js"></script>
        <script src="<?= $absolutePath ?>js/modernizr-webp.js"></script>
        <script src='https://www.google.com/recaptcha/api.js'></script>

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
        <?php 
            if (isset($_SESSION["messageBox"])) {
                echo "<div class='message-box'>";
                echo $_SESSION["messageBox"]->getText(); 
                echo "</div>";
                
                unset($_SESSION["messageBox"]);
            }
        ?>
        