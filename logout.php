<?php
session_start();
session_unset();
session_destroy();
// Redirecționează utilizatorul către pagina principală publică
header("Location: acasa.php");
exit();
?>