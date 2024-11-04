<?php
// File: step21.php

session_start();
require_once 'db_connection.php';

// Abilita la visualizzazione degli errori (da rimuovere in produzione)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifica l'autenticazione dell'agente
if (!isset($_SESSION['agente_id'])) {
    header("Location: login.php");
    exit();
}

$agente_id = $_SESSION['agente_id'];
$processo_id = $_SESSION['processo_id'] ?? null;

if (!$processo_id) {
    die("Processo non trovato.");
}

// Recupera i dati tecnici dell'energia dalla tabella dati_tecnici_energia
$sql = "SELECT * FROM dati_tecnici_energia WHERE processo_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("i", $processo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Dati tecnici energia non trovati. Assicurati di aver completato tutti i passaggi precedenti.");
}

$dati_tecnici_energia = $result->fetch_assoc();
$stmt->close();

// Recupera altri dati dal processo se necessario
$sql_processi = "SELECT dati_step FROM processi WHERE id = ? AND agente_id = ?";
$stmt_processi = $conn->prepare($sql_processi);
if (!$stmt_processi) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt_processi->bind_param("ii", $processo_id, $agente_id);
$stmt_processi->execute();
$stmt_processi->bind_result($dati_step_json);
$stmt_processi->fetch();
$stmt_processi->close();

if (empty($dati_step_json)) {
    die("Dati del processo non trovati.");
}

$dati_step = json_decode($dati_step_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Errore nella decodifica dei dati del processo: " . json_last_error_msg());
}

// Puoi aggiungere ulteriori verifiche o processi qui

// Mostra un messaggio di conferma
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Processo Completato - Step 21</title>
    <style>
        /* Stili CSS */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            margin: 50px auto;
            max-width: 800px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            color: #4CAF50;
        }
        p {
            font-size: 18px;
        }
        .download-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #008CBA;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .download-link:hover {
            background-color: #005f6a;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/menu.php'; ?>
    <div class="container">
        <h2>Processo Completato</h2>
        <p>Il processo è stato completato con successo.</p>
        <?php
        // Opzionale: Fornisci un link per scaricare il PDF della PDA
        if (isset($dati_step['pdf_pda']) && file_exists(__DIR__ . '/' . $dati_step['pdf_pda'])) {
            echo '<a class="download-link" href="' . htmlspecialchars($dati_step['pdf_pda']) . '" download>PDA Generata - Scarica PDF</a>';
        }
        ?>
    </div>
</body>
</html>