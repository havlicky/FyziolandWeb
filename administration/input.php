<?php

include_once '../header.php';

$query = "  SELECT
                id,
                name,
                surname,
                email,
                phone
            FROM clients
            ORDER BY surname, name";
$stmt = $dbh->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

?>
        <div class="container-fluid">
            <select id="searchField" name="state">
                <?php
                    foreach ($results as $result) {
                ?>
                <option value="<?= $result->id ?>" data-email="<?= $result->email ?>"><?= $result->surname . ", " . $result->name ?></option>
                <?php
                    }
                ?>
            </select>
        </div>
        <script>
            function formatState (item) {
                item = $(item.element);
                return $("<div>" + item.text() + "<br><small>" + item.attr("data-email") + "</small></div>");  
            };
            
            $(document).ready(function() {
                $("#searchField").select2({
                    templateResult: formatState
                });
            });
        </script>
    </body>
</html>