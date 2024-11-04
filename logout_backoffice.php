<?php
session_start();
session_destroy(); // Distruggi la sessione attuale
header('Location: backoffice_login.php'); // Reindirizza alla pagina di login del backoffice
exit;
?>