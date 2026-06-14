<?php
session_start();
session_unset();
session_destroy();

// Redirecționează utilizatorul către pagina principală publică cu mesaj de succes
header("Location: acasa.php?action=logout_success");
exit();
?>