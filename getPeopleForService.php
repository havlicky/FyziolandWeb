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

$query = "  SELECT
                a.id,
                a.displayName
            FROM adminLogin AS a
            WHERE
                EXISTS (SELECT id FROM relationPersonService WHERE person = a.id AND service = :service AND web = 1) AND
                a.active = 1 AND
                webRes = 1
            ORDER BY a.displayName";
$stmt = $dbh->prepare($query);
$stmt->bindParam(":service", $_POST["service"], PDO::PARAM_INT);
$stmt->execute();
$resultPeople = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<option value="">Kdokoli</option>
<?php if (count($resultPeople) > 0): ?>
    <?php foreach ($resultPeople as $resultPerson): ?>
    <option value="<?= $resultPerson->id ?>"><?= htmlentities($resultPerson->displayName) ?></option>
    <?php endforeach; ?>
<?php endif; ?>