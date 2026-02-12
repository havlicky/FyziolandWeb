<?php

require_once "php/class.settings.php";
require_once "php/PHPMailer/PHPMailer.php";
require_once "php/PHPMailer/Exception.php";
session_start();

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

if (isset($_POST["submit"])) {
    if (empty($_POST["password"]) || empty($_POST["passwordAgain"])) {
        $_SESSION["resetError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["resetError"] .= "Všechna pole jsou povinná a musí být vyplněna.";
        
        header("Location: resetHesla");
        die();
    }
    
    if ($_POST["password"] !== $_POST["passwordAgain"]) {
        $_SESSION["resetError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["resetError"] .= "Heslo a jeho potvrzení se musí shodovat.";
        
        header("Location: resetHesla");
        die();
    }
    
    if (empty($_POST["email"]) || empty($_POST["hash"])) {
        $_SESSION["resetError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["resetError"] .= "Neplatné údaje. Otevřete tuto stránku prosím znovu z e&#8209;mailu s odkazem na obnovení hesla.";
        
        header("Location: resetHesla");
        die();
    }
    
    $query = "SELECT id FROM clients WHERE AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') = :email AND recoveryHash = :recoveryHash";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(":email", urldecode($_POST["email"]), \PDO::PARAM_STR);
    $stmt->bindValue(":recoveryHash", $_POST["hash"], \PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_OBJ);
    if ($result === FALSE) {
        $_SESSION["registerError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["registerError"] .= "Obnova hesla nemohla být provedena, protože byly změněny automaticky generované vstupní údaje. Opakujte prosím pokus kliknutí man odkaz v e&#8209;mailu.";
        
        header("Location: resetHesla");
        die();
    }
    
    $query = "UPDATE clients SET password = :password, recoveryHash = NULL, activated = 1 WHERE id = :id";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue("password", password_hash($_POST["password"], PASSWORD_DEFAULT), PDO::PARAM_STR);
    $stmt->bindValue(":id", $result->id, PDO::PARAM_STR);
    
    $stmt->execute();
}

$_SESSION["resetSuccess"] = "<span class='glyphicon glyphicon-ok' aria-hidden='true' style='margin-right: 10px;'></span>";
$_SESSION["resetSuccess"] .= "Vaše přihlašovací heslo bylo v pořádku změněno. Přihlásit se s použitím nových údajů můžete <a href='login'>zde</a>";

header("Location: resetHesla");
die();



