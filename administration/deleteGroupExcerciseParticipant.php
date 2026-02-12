<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
$messageBox = new MessageBox();

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

session_start();

if (isset($_GET["id"])) {
    
    $query = "SELECT semestralCourse FROM groupExcercises WHERE id = (SELECT groupExcercise FROM groupExcercisesParticipants WHERE id = :id LIMIT 1)";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    $semestralCourse = $result->semestralCourse;
    $isPartOfSemestralCourse = !empty($result->semestralCourse);
    
    if ( !$isPartOfSemestralCourse ) {    
        $query = "  DELETE FROM groupExcercisesParticipants
                    WHERE id = :id";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);    
    } else {
        
        $query = "SELECT client as id FROM groupExcercisesParticipants WHERE id = :id";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $client = $stmt->fetch(PDO::FETCH_OBJ);
                
        $query = "  DELETE FROM groupExcercisesParticipants
                    WHERE client = :client AND groupExcercise IN (SELECT id FROM groupExcercises WHERE semestralCourse = :semetralCourse)";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":client", $client->id, PDO::PARAM_STR);        
        $stmt->bindParam(":semetralCourse", $semestralCourse, PDO::PARAM_INT);        
    }
    
    
    if ($stmt->execute()) {
        $messageBox->addText("Rezervace na skupinové cvičení byla v pořádku odstraněna.");
    } else {
        $messageBox->addText("Rezervaci na skupinové cvičení se nepodařilo odstranit.");
        $messageBox->setClass("alert-danger");
    }
}

$_SESSION["messageBox"] = $messageBox;
header("Location: viewGroupReservations.php");

