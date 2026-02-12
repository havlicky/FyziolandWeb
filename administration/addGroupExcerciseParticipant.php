<?php

require_once "checkLogin.php";
require_once "../php/class.messagebox.php";
require_once "../php/class.settings.php";
$messageBox = new MessageBox();

if (intval($resultAdminUser->isSuperAdmin) !== 1) {
    die();
}

session_start();

if (isset($_POST["clientId"]) && isset($_POST["groupExcerciseId"])) {
    $query = "SELECT semestralCourse FROM groupExcercises WHERE id = :groupExcerciseId";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":groupExcerciseId", $_POST["groupExcerciseId"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    $semestralCourse = $result->semestralCourse;
    $isPartOfSemestralCourse = !empty($result->semestralCourse);
    
    if ( !$isPartOfSemestralCourse ) {
        $query = "  INSERT INTO groupExcercisesParticipants (
                        groupExcercise,
                        client
                    ) VALUES (
                        :groupExcerciseId,
                        :clientId
                    )";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":groupExcerciseId", $_POST["groupExcerciseId"], PDO::PARAM_INT);
        $stmt->bindParam(":clientId", $_POST["clientId"], PDO::PARAM_STR);
    } else {
        $query = "  INSERT INTO groupExcercisesParticipants (
                        groupExcercise,
                        client
                    )
                    SELECT
                        ge.id,
                        :clientId
                    FROM groupExcercises AS ge
                    WHERE
                        ge.semestralCourse = :semestralCourse";
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(":clientId", $_POST["clientId"], PDO::PARAM_STR);
        $stmt->bindValue(":semestralCourse", $semestralCourse, PDO::PARAM_INT);
    }
    
    if ($stmt->execute()) {
        $messageBox->addText("Rezervace na skupinové cvičení byla v pořádku vytvořena.");
    } else {
        $messageBox->addText("Rezervaci na skupinové cvičení se nepodařilo vytvořit.");
        $messageBox->setClass("alert-danger");
    }
}

$_SESSION["messageBox"] = $messageBox;
//header("Location: viewGroupReservations.php");

