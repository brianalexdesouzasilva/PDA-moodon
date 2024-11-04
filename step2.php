<?php
session_start();
require_once 'db_connection.php';

// Abilita la visualizzazione degli errori
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se l'agente è autenticato
if (!isset($_SESSION['agente_id'])) {
    header("Location: login.php");
    exit();
}

$agente_id = $_SESSION['agente_id'];
$session_id = session_id();

// Verifica che il session_id corrisponda a quello nel database
$sql = "SELECT session_id FROM agenti WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("i", $agente_id);
$stmt->execute();
$stmt->bind_result($db_session_id);
$stmt->fetch();
$stmt->close();

if ($db_session_id !== $session_id) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Recupera il processo_id dalla sessione
if (!isset($_SESSION['processo_id'])) {
    die("Processo non inizializzato. Torna allo step precedente.");
}
$processo_id = $_SESSION['processo_id'];

// Recupera i dati del processo dal database
$sql = "SELECT dati_step FROM processi WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("i", $processo_id);
$stmt->execute();
$stmt->bind_result($dati_step_json);
$stmt->fetch();
$stmt->close();

// Gestione di $dati_step_json se è null
if (!empty($dati_step_json)) {
    $dati_step = json_decode($dati_step_json, true);
    if ($dati_step === null && json_last_error() !== JSON_ERROR_NONE) {
        die("Errore nel decoding dei dati del processo.");
    }
} else {
    $dati_step = []; // Inizializza come array vuoto se non ci sono dati
}

// Il resto del tuo codice per gestire lo step 2
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Step 2</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Scegli Categoria</h2>
        <!-- Form per Luce e Gas -->
        <form action="luceegas-step1.php" method="post">
            <button type="submit">Luce e Gas</button>
        </form>
        <!-- Form per Telefono e Fibra -->
        <form action="telefonoefibra-step1.php" method="post">
            <button type="submit">Telefono e Fibra</button>
        </form>
        <!-- Form per Green -->
        <form action="green-step1.php" method="post">
            <button type="submit">Green</button>
        </form>
        <!-- Form per TV -->
        <form action="tv_step1.php" method="post">
            <button type="submit">TV</button>
        </form>
    </div>
</body>
</html>

