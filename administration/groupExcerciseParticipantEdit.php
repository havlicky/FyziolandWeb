<?php

require_once "../php/class.settings.php";
require_once "../php/class.messagebox.php";
require_once "checkLogin.php";

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

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

if (isset($_POST["clientId"]) && isset($_POST["groupExcerciseId"])) {
    
    $allowedColumns = array(    
    "note" => false,
    "paid" => false,
    "faPohoda" => false
    );

    if (!in_array($_POST["field"], array_keys($allowedColumns))) {
        die();
    } else {
        $crypted = $allowedColumns[$_POST["field"]];
    }
    
    // zjištění, zda se jedná o kurz nebo samostatné cvičení
    $query = "SELECT semestralCourse FROM groupExcercises WHERE id = :groupExcerciseId";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":groupExcerciseId", $_POST["groupExcerciseId"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    $semestralCourse = $result->semestralCourse;
    $isPartOfSemestralCourse = !empty($result->semestralCourse);
    
    // jednotlivé cvičení
    if ( !$isPartOfSemestralCourse ) {
        $query = "UPDATE groupExcercisesParticipants SET {$_POST["field"]} = ";
        if ($crypted) {
            $query .= "AES_ENCRYPT(:value, '" . Settings::$mySqlAESpassword . "')";
        } else {
            $query .= ":value";
        }
        $query .= " WHERE groupExcercise = :groupExcerciseId AND client = :clientId";

        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":value", empty($_POST["value"]) ? NULL : $_POST["value"], PDO::PARAM_STR);
        $stmt->bindParam(":groupExcerciseId", $_POST["groupExcerciseId"], PDO::PARAM_STR);
        $stmt->bindParam(":clientId", $_POST["clientId"], PDO::PARAM_STR);
        $stmt->execute();
                            
    // semestrální kurz    
    } else {                
        $query = "UPDATE groupExcercisesParticipants SET {$_POST["field"]} = ";
        if ($crypted) {
            $query .= "AES_ENCRYPT(:value, '" . Settings::$mySqlAESpassword . "')";
        } else {
            $query .= ":value";
        }
        $query .= " WHERE groupExcercise IN (SELECT id from groupExcercises AS ge WHERE ge.semestralCourse = :semestralCourse) AND client = :clientId";

        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":value", empty($_POST["value"]) ? NULL : $_POST["value"], PDO::PARAM_STR);
        $stmt->bindParam(":semestralCourse", $semestralCourse, PDO::PARAM_STR);
        $stmt->bindParam(":clientId", $_POST["clientId"], PDO::PARAM_STR);
        $stmt->execute();
    }        
}

