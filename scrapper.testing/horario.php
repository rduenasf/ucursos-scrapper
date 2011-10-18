<?php
require_once '../session_check.php';
require_once '../library.ucursos.scrapper.php';
$scrapper = new HorarioScrapper(session_id());
if (!$scrapper->fetch('https://www.u-cursos.cl/usuario/117e2105085ec2458b73e2e28199585e/horario/')) {
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

$value = $scrapper->getHorario();

?>
<!DOCTYPE HTML>
<html>
<body class="main">
<pre>
    <?php print_r($value); ?>
</pre>
</body>
</html>