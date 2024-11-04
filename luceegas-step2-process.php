<?php
session_start();
require_once 'db_connection.php';

// Verifica che i dati siano stati passati correttamente
if (!isset($_POST['codice_fiscale_piva'])) {
    die("Codice fiscale o partita IVA mancante.");
}

$codice_fiscale_piva = $_POST['codice_fiscale_piva'];

// Query per cercare il cliente nel database
$sql_cliente = "SELECT * FROM clienti WHERE codice_fiscale_piva = ?";
$stmt = $conn->prepare($sql_cliente);
$stmt->bind_param("s", $codice_fiscale_piva);
$stmt->execute();
$result_cliente = $stmt->get_result();

// Se il cliente esiste, passa allo Step 4
if ($result_cliente->num_rows > 0) {
    $cliente = $result_cliente->fetch_assoc();
    
    // Memorizza i dati del cliente nella sessione
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['nome'] = $cliente['nome'];
    $_SESSION['cognome'] = $cliente['cognome'];
    $_SESSION['codice_fiscale_piva'] = $cliente['codice_fiscale_piva'];
    
    header("Location: step4.php");
    exit;
} else {
    // Se il cliente non esiste, passa allo Step 3 per aggiungere il nuovo cliente
    header("Location: luceegas-step3.php");
    exit;
}
?>