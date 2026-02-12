<?php

require_once "checkLogin.php";
require_once "../php/class.settings.php";

echo($_POST["akce"]);
echo($_POST["type"]);

// odškrtnutí checkboxu
if ($_POST["akce"] =='0') {
    if ($_POST["type"] == 'ucast') {
        $stmt = $dbh->prepare("UPDATE attendance SET ucast = NULL WHERE client = :client AND ge = :ge");
    }
    if ($_POST["type"] == 'omluva') {
        $stmt = $dbh->prepare("UPDATE attendance SET omluva = NULL WHERE client = :client AND ge = :ge");
    }
    if ($_POST["type"] == 'nechodi') {
        $stmt = $dbh->prepare("UPDATE attendance SET nechodi = NULL WHERE client = :client AND ge = :ge");
    }
    $stmt->bindParam(":ge", $_POST["ge"], PDO::PARAM_INT);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
}

// zaškrnutí checkboxu
if ($_POST["akce"] =='1') {
    // zjistím, zda existuje už žádnam o daném klinetovi v tabulce attendance
    $stmt = $dbh->prepare("SELECT COUNT(client) FROM attendance WHERE client = :client AND ge = :ge");
    $stmt->bindParam(":ge", $_POST["ge"], PDO::PARAM_INT);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
    $pocet = $stmt->fetch(PDO::FETCH_NUM);
    echo($pocet[0]);
    
    if ($_POST["type"] == 'ucast') {
        if ($pocet[0] == '1') {$stmt = $dbh->prepare("UPDATE attendance SET ucast = '1' WHERE client = :client AND ge = :ge");}
        if ($pocet[0] == '0') {$stmt = $dbh->prepare("INSERT INTO attendance (client, ge, ucast) VALUES (:client, :ge, '1')");}
    }
    if ($_POST["type"] == 'omluva') {        
        if ($pocet[0] == '1') {$stmt = $dbh->prepare("UPDATE attendance SET omluva = '1' WHERE client = :client AND ge = :ge");}
        if ($pocet[0] == '0') {$stmt = $dbh->prepare("INSERT INTO attendance (client, ge, omluva) VALUES (:client, :ge, '1')");}
    }
    if ($_POST["type"] == 'nechodi') {        
        if ($pocet[0] == '1') {$stmt = $dbh->prepare("UPDATE attendance SET nechodi = '1' WHERE client = :client AND ge = :ge");}
        if ($pocet[0] == '0') {$stmt = $dbh->prepare("INSERT INTO attendance (client, ge, nechodi) VALUES (:client, :ge, '1')");}
    }
    $stmt->bindParam(":ge", $_POST["ge"], PDO::PARAM_INT);
    $stmt->bindParam(":client", $_POST["client"], PDO::PARAM_STR);
    $stmt->execute();
}
