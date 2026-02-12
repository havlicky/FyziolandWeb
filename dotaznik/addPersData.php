<?php

//require_once "../header.php";
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

$query = "  UPDATE reservations SET
                name = AES_ENCRYPT(:name, '" . Settings::$mySqlAESpassword . "'),
                surname = AES_ENCRYPT(:surname, '" . Settings::$mySqlAESpassword . "'),                                
                city = :city,
                street =:street,
                zip = :zip,
                birthday = :birthday,
                birthnumber = :birthnumber, 
                sex = :sex, 
                LRname = :LRname,
                LRsurname = :LRsurname, 
                LRemail = :LRemail,
                LRphoneFather = :LRphoneFather, 
                LRphoneMother = :LRphoneMother,
                LRcity = :LRcity,
                LRstreet = :LRstreet,
                LRzip = :LRzip,
                insuranceCompany = :insuranceCompany,
                entryNote = :entryNote
            
            WHERE
                deleteHash = :hash";
			
$stmt = $dbh->prepare($query);
$stmt->bindParam(":hash", htmlentities($_POST["hash"]), PDO::PARAM_STR);
$stmt->bindParam(":name", $_POST["name"], PDO::PARAM_STR);
$stmt->bindParam(":surname", $_POST["surname"], PDO::PARAM_STR);
$stmt->bindValue(":city", empty($_POST["city"]) ? NULL : $_POST["city"], PDO::PARAM_STR);
$stmt->bindValue(":zip", empty($_POST["zip"]) ? NULL : $_POST["zip"], PDO::PARAM_STR);
$stmt->bindValue(":street", empty($_POST["street"]) ? NULL : $_POST["street"], PDO::PARAM_STR);
$stmt->bindValue(":birthday", empty($_POST["birthdayformatted"]) ? NULL : $_POST["birthdayformatted"], PDO::PARAM_STR);
$stmt->bindValue(":birthnumber", empty($_POST["birthnumber"]) ? NULL : $_POST["birthnumber"], PDO::PARAM_STR);
$stmt->bindValue(":sex", empty($_POST["sex"]) ? NULL : $_POST["sex"], PDO::PARAM_STR);
$stmt->bindParam(":LRname", $_POST["lrname"], PDO::PARAM_STR);
$stmt->bindParam(":LRsurname", $_POST["lrsurname"], PDO::PARAM_STR);
$stmt->bindValue(":LRemail", empty($_POST["lremail"]) ? NULL : $_POST["lremail"], PDO::PARAM_STR);
$stmt->bindValue(":LRphoneMother", empty($_POST["lrphonemother"]) ? NULL : $_POST["lrphonemother"], PDO::PARAM_STR);
$stmt->bindValue(":LRphoneFather", empty($_POST["lrphonefather"]) ? NULL : $_POST["lrphonefather"], PDO::PARAM_STR);
$stmt->bindValue(":LRcity", empty($_POST["lrcity"]) ? NULL : $_POST["lrcity"], PDO::PARAM_STR);
$stmt->bindValue(":LRzip", empty($_POST["lrzip"]) ? NULL : $_POST["lrzip"], PDO::PARAM_STR);
$stmt->bindValue(":LRstreet", empty($_POST["lrstreet"]) ? NULL : $_POST["lrstreet"], PDO::PARAM_STR);
$stmt->bindValue(":insuranceCompany", empty($_POST["insurancecompany"]) ? NULL : $_POST["insurancecompany"], PDO::PARAM_INT);
$stmt->bindValue(":entryNote", empty($_POST["entrynote"]) ? NULL : $_POST["entrynote"], PDO::PARAM_STR);
$stmt->execute();

//uložení přílohy
if ($_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $query = "SELECT id FROM enum_attachmentallowedextensions WHERE extension = :extension";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(":extension", $extension, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    if (count($results) === 0) {
        echo "<script type='text/javascript'>alert('Soubor má nepovolený typ a nebyl uložen. Povoleny jsou pouze přípony pdf, jpg, png a zip.');</script>";                                                     
    } else {
        $extensionId = $results[0]->id;
        $query = "  UPDATE reservations SET
                    attName = :attName ,
                    attExt = :attExt,
                    attSize = :attSize            
            WHERE
                deleteHash = :hash";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(":hash", htmlentities($_POST["hash"]), PDO::PARAM_STR);       
        $stmt->bindValue(":attName", pathinfo($_FILES['file']['name'], PATHINFO_FILENAME), PDO::PARAM_STR);
        $stmt->bindParam(":attExt", $extensionId, PDO::PARAM_STR);
        $stmt->bindParam(":attSize", $_FILES['file']['size'], PDO::PARAM_INT);                	
        $stmt->execute();
        
        //najdu id rezervace dle hash
        $stmt = $dbh->prepare("SELECT id FROM reservations WHERE deleteHash = :hash");
        $stmt->bindParam(":hash", htmlentities($_POST["hash"]), PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);	         
        $fileId = $result->id;
        
        $stmt = $dbh->query("SELECT value FROM settings WHERE name = 'zipPassword'");
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        $zipPassword = $result->value;
        
        $zip = new ZipArchive();
        $zip_status = $zip->open("attachments/" . $fileId . ".zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($zip_status === true)
        {
            $zip->addFromString($_FILES['file']['name'], file_get_contents($_FILES['file']['tmp_name']));
            $zip->setEncryptionName($_FILES['file']['name'], ZipArchive::EM_AES_256, $zipPassword);
            $zip->close();         
            
            unlink($_FILES['file']['tmp_name']);
            
            echo "<script type='text/javascript'>alert('Uložení přílohy proběhlo v pořádku.');</script>"; 
            			
        } else {
            echo "<script type='text/javascript'>alert('1 - Došlo k chybě při ukládání souboru.');</script>";        
            
        }
    }
}

echo "<script type='text/javascript'>document.location = \"dotaznik.php?hash=" . $_POST["hash"] . "\";</script>"; 