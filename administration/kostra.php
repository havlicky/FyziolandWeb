<?php

require_once '../header.php';

?>

<label>
    <input type="checkbox" id="test" checked> Zobrazit
</label>
<br><br>

<img src="../img/skeleton-front.jpg" alt="Kostra zepředu">
<img src="../img/skeleton-sipka.png" style="position: relative; margin-top: -220px; margin-left: -20px;" id="sipka">

<script>
    $(document).ready(function() {
        $("#test").change(function() {
            $("#sipka").toggle();
        });
    });
</script>