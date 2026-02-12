<?php

session_start();
require_once "../php/class.settings.php";

try {
    $dsn = "mysql:dbname=" . Settings::$dbName . ";host=" . Settings::$dbServer . ";charset=utf8";
    $user = Settings::$dbLogin;
    $password = Settings::$dbPassword;

    $dbh = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die();
}

$error = false;
if (isset($_POST["submitLogin"])) {
    //to do
    //ověřit login a vytvořit a uložit cookie
    if (!empty($_POST["login"]) && !empty($_POST["password"])) {
        $query = "SELECT password FROM adminLogin WHERE login = :login";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":login", $_POST["login"], PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (password_verify($_POST["password"], $result->password)) {
            unset($_COOKIE["login"], $_COOKIE["loginName"]);
            
            // vytvoření cookie s příznakem o přihlášení a platností do půlnoci daného dne
            $cookieHash = bin2hex(openssl_random_pseudo_bytes(25));
            $cookieExpiry = time() + (23 - intval(date("H"))) * 3600 + (59 - intval(date("i"))) * 60 + (59 - intval(date("s")));
            setcookie("login", $cookieHash, $cookieExpiry, "/", "fyzioland.cz", TRUE, TRUE);
            setcookie("loginName", $_POST["login"], $cookieExpiry, "/", "fyzioland.cz", TRUE, TRUE);
            
            // uložení cookie do db pro pozdější ověřování přihlášení
            $query = "UPDATE adminLogin SET loginCookieValue = :loginCookieValue, loginCookieExpiry = :loginCookieExpiry WHERE login = :login";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":loginCookieValue", $cookieHash, PDO::PARAM_STR);
            $stmt->bindValue(":loginCookieExpiry", date("Y-m-d H:i:s", $cookieExpiry), PDO::PARAM_STR);
            $stmt->bindParam(":login", $_POST["login"], PDO::PARAM_STR);
            $stmt->execute();
            
            // uložení úspěšného přihlášení do logu
            $query = "INSERT INTO adminLoginLog (login, IPaddress, success) VALUES (:login, :IPaddress, 1)";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":login", $_POST["login"], PDO::PARAM_STR);
            $stmt->bindParam(":IPaddress", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
            $stmt->execute();
            
            // úspěšné přihlášení, přesměrování na přehled rezervací
            header("Location: viewReservations");
            die();
        } else {
            // uložení neúspěšného přihlášení do logu
            $query = "INSERT INTO adminLoginLog (login, IPaddress, success) VALUES (:login, :IPaddress, 0)";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(":login", $_POST["login"], PDO::PARAM_STR);
            $stmt->bindParam(":IPaddress", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
            $stmt->execute();
            
            $error = true;
        }
    } else {
        $error = true;
    }  
}

require_once "../header.php";

?>

        <div class="container" style="height: 100%">
            <table style="width: 100%; height: 100%">
                <tr>
                    <td style="vertical-align: middle; text-align: left;">
                        <form method="POST">
                            <div class="row">
                                <div class="col-sm-6 col-sm-offset-3 text-center">
                                    <img src="../img/Logo.png">
                                    <br><br>
                                </div>
                                <div class="col-sm-6 col-sm-offset-3 well">
                                    <?php
                                        if ($error) {
                                            $error = false;
                                    ?>
                                    <div class="alert alert-danger text-center">
                                        Došlo k chybě při přihlašování. Opakujte prosím přihlášení.
                                    </div>
                                    <?php
                                        }
                                    ?>
                                    <div class="form-group">
                                        <label for="login">Login:</label>
                                        <input type="text" class="form-control" id="login" name="login" placeholder="Login" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="password">Heslo:</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Heslo" required>
                                    </div>
                                    <div class="form-group" style="text-align: center;">
                                        <button class="btn btn-success" name="submitLogin">Přihlásit se do administrace</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>