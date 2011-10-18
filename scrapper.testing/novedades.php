<?php
require_once '../session_check.php';
require_once '../library.ucursos.scrapper.php';
$scrapper = new NovedadesScrapper(session_id());
if (!$scrapper->fetch('https://www.u-cursos.cl/ingenieria/2/novedades_institucion/')) {
    if (!$scrapper->is_connected()) {
        die("connection error");
    }
    else if ($scrapper->is_authorized()) {
        die("error");
    }
    else {
        header('Location: ../logout.php');
        die;
    }
}

$value = $scrapper->getNovedades();

?>
<!DOCTYPE HTML>
<html>
<body class="main">
<pre>
    <?php print_r($value); ?>
</pre>
</body>
</html>