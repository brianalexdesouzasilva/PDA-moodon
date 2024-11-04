<?php
session_start();
require_once 'db_connection.php';

$agente_id = $_SESSION['agente_id'] ?? null;

if ($agente_id) {
    // Rimuovi il session_id dal database
    $sql = "UPDATE agenti SET session_id = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $agente_id);
    $stmt->execute();
    $stmt->close();
}

// Distruggi la sessione
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>