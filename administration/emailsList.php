<?php

$pageTitle = "FL - hromadný mailing";

require_once "checkLogin.php";

if ($resultAdminUser->isSuperAdmin !== "1") {
    header("Location: viewGroupReservations");
}

require_once "../header.php";

$query = "  SELECT
                e.id,
                e.subject,
                e.body,
                e.dateSent,
                e.state
            FROM emails AS e
            ORDER BY e.id DESC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

?>

<?php include "menu.php" ?>

<div class="container-fluid" style="margin-top: 10px;">
    <div class="col-lg-10 col-lg-offset-1">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2>
                    Přehled e-mailů 
                </h2>
            </div>
        </div>

        <?php
            if (isset($_SESSION["messageBox"])) {
        ?>
        <div class="alert <?= $_SESSION["messageBox"]->getClass() ?> in fade text-center">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= $_SESSION["messageBox"]->getText() ?>
        </div>
        <?php
            }
            unset($_SESSION["messageBox"]);
        ?>

        <div class="row">
            <div class="col-md-12 text-right">
                <h4><a class="btn btn-success" href="emailsEdit.php">Vytvořit nový e-mail</a></h4>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered table-hover table-striped" id="emails">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Předmět</th>
                            <th>Tělo</th>
                            <th class="text-center">Stav</th>
                            <th class="text-center">Datum odeslání</th>
                            <th class="text-center">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $result): ?>
                        <tr style="height: 50px;" data-id="<?= $result->id ?>">
                            <td style="vertical-align: middle;">
                                <?=$result->id?>
                            </td>
                            <td style="vertical-align: middle;">
                                <?= htmlentities($result->subject); ?>
                            </td>
                            <td style="vertical-align: middle;">
                                <?= mb_substr(strip_tags($result->body), 0, 200); ?>
                            </td>
                            <td style="vertical-align: middle;" class="text-center">
                                <?= htmlentities($result->state); ?>
                            </td>
                            <td style="vertical-align: middle;" class="text-center">
                                <?php
                                    if (empty($result->dateSent)) {
                                        echo "Neodesláno";
                                    } else {
                                        echo (new DateTime($result->dateSent))->format("j. n. Y H:i:s");
                                    }
                                ?>
                            </td>
                            <td style="vertical-align: middle;" class="text-center">
                                <a class="btn btn-info" href="emailsEdit.php?id=<?= $result->id ?>">Edit</a>
                                <a class="btn btn-info" href="emailsEdit.php?id=<?= $result->id ?>&akce=kopie">Kopie</a>
                                <a class="btn btn-danger" href="emailsDelete.php?id=<?= $result->id ?>" name="buttonDelete">Smazat</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $.fn.modal.Constructor.prototype.enforceFocus = function() {};

        $(".message-box").delay(500).fadeIn().delay(4000).fadeOut();
        
        /*
        $("#emails").DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Czech.json"
            }
        });
        */
        
        $("#emails a[name='buttonDelete']").click(function(event) {
            if (!confirm("Skutečně si přejete smazat tento e-mail?")) {
                event.preventDefault();
            }
        });
    });
</script>