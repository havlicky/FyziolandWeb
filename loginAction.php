<?php

require_once "php/class.settings.php";
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
    if (empty($_POST["login"]) || empty($_POST["password"])) {
        $_SESSION["loginError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
        $_SESSION["loginError"] .= "Login i heslo musí být vyplněny.";
    } else {
        $query = "  SELECT
                        id,
                        AES_DECRYPT(name, '" . Settings::$mySqlAESpassword . "') AS name,
                        AES_DECRYPT(surname, '" . Settings::$mySqlAESpassword . "') AS surname,
                        AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') AS email,
                        AES_DECRYPT(phone, '" . Settings::$mySqlAESpassword . "') AS phone,
                        password,
                        activated
                    FROM clients
                    WHERE AES_DECRYPT(email, '" . Settings::$mySqlAESpassword . "') = :email";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":email", $_POST["login"], \PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        
        if ($result === FALSE || !password_verify($_POST["password"], $result->password)) {
            $_SESSION["loginError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
            $_SESSION["loginError"] .= "Chybná kombinace přihlašovacích údajů. Opakujte prosím přihlášení.";
        } else if ($result->activated === "0") {
            $_SESSION["loginError"] = "<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true' style='margin-right: 10px;'></span>";
            $_SESSION["loginError"] .= "Tento účet ještě nebyl aktivován. Proveďte prosím jeho aktivaci kliknutím na odkaz v e-mailu, který Vám byl zaslán po registraci.";
        } else {
            $_SESSION["loggedUserId"] = $result->id;
            $_SESSION["loggedUserName"] = htmlentities($result->name);
            $_SESSION["loggedUserSurname"] = htmlentities($result->surname);
            $_SESSION["loggedUserEmail"] = htmlentities($result->email);
            $_SESSION["loggedUserPhone"] = htmlentities($result->phone);
            
            $query = "UPDATE clients SET lastLoginDate = NOW() WHERE id = :id";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":id", $result->id, PDO::PARAM_STR);
            $stmt->execute();
            
            // úspěšné přihlášení
            if (isset($_SESSION["redirectAfterLogin"])) {
                header("Location: " . $_SESSION["redirectAfterLogin"]);
                unset($_SESSION["redirectAfterLogin"]);
            } else {
                header("Location: rezervace-skupinova-cviceni");
            }
            die();
        }
        
    }
}

header("Location: login");
die();



