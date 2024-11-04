<?php
session_start();
require_once 'db_connection.php';

// Abilita la visualizzazione degli errori per il debug (rimuovi in produzione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se l'agente è autenticato
if (!isset($_SESSION['agente_id'])) {
    header("Location: login.php");
    exit();
}

$agente_id = $_SESSION['agente_id'];

// Verifica che il processo sia inizializzato
if (!isset($_SESSION['processo_id'])) {
    die("Processo non inizializzato. Torna allo step precedente.");
}
$processo_id = $_SESSION['processo_id'];

// Recupera i dati del processo
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

// Decodifica i dati precedenti
$dati_step = [];
if (!empty($dati_step_json)) {
    $dati_step = json_decode($dati_step_json, true);
    if ($dati_step === null && json_last_error() !== JSON_ERROR_NONE) {
        die("Errore nel decoding dei dati del processo.");
    }
}

$cf_piva = $dati_step['cf_piva'] ?? '';
$cliente_id = $dati_step['cliente_id'] ?? null;

if (empty($cf_piva) || !$cliente_id) {
    die("Dati del cliente mancanti. Torna allo Step precedente.");
}

// Recupera i dati tecnici inseriti nello Step 6
$dati_tecnici = $dati_step['dati_tecnici'] ?? [];

// Recupera la selezione dallo Step 2
$pod_pdr_scelta = $dati_step['pod_pdr_scelta'] ?? '';

// Gestione del form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';

    if (empty($metodo_pagamento)) {
        $error_message = "Seleziona un metodo di pagamento.";
    } else {
        // Salva il metodo di pagamento nei dati del processo
        $dati_step['metodo_pagamento'] = $metodo_pagamento;

        // Codifica i dati in JSON
        $dati_step_json = json_encode($dati_step);

        // Aggiorna il processo nel database
        $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Errore nella preparazione della query di aggiornamento processo: " . $conn->error);
        }
        $step_corrente = 8; // Aggiorniamo allo step successivo
        $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
        if (!$stmt->execute()) {
            die("Errore nell'aggiornamento del processo: " . $stmt->error);
        }
        $stmt->close();

        // Reindirizza allo Step 8
        header("Location: luceegas-step8.php");
        exit();
    }
} else {
    $metodo_pagamento = $dati_step['metodo_pagamento'] ?? '';
    $error_message = '';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Metodo di Pagamento - Step 7</title>
    <link rel="stylesheet" href="style.css">
    <!-- Meta tag per rendere la pagina responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Stile specifico per Step 7 -->
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            margin: 100px auto;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        button {
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .alert {
            padding: 10px;
            background-color: #f44336;
            color: white;
            margin-bottom: 15px;
            text-align: center;
            border-radius: 5px;
        }

    </style>
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container">
        <h2>Seleziona il Metodo di Pagamento - Step 7</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <form action="luceegas-step7.php" method="POST">
            <!-- Metodo di Pagamento -->
            <label for="metodo_pagamento">Metodo di Pagamento:</label>
            <select id="metodo_pagamento" name="metodo_pagamento" required>
                <option value="">Seleziona</option>
                <option value="bollettino" <?php if ($metodo_pagamento == 'bollettino') echo 'selected'; ?>>Bollettino</option>
                <option value="rid" <?php if ($metodo_pagamento == 'rid') echo 'selected'; ?>>RID</option>
            </select>

            <button type="submit">Avanti</button>
        </form>
    </div>

    <!-- JavaScript per il menu -->
    <script>
        function openNav() {
            document.getElementById("mySidenav").style.width = "250px";
        }

        function closeNav() {
            document.getElementById("mySidenav").style.width = "0";
        }
    </script>
</body>
</html>