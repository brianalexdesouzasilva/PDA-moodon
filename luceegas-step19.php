<?php
// File: luceegas-step19.php

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

// Recupera i dati del processo
$sql = "SELECT dati_step FROM processi WHERE id = ? AND agente_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("ii", $processo_id, $agente_id);
$stmt->execute();
$stmt->bind_result($dati_step_json);
$stmt->fetch();
$stmt->close();

if (empty($dati_step_json)) {
    die("Dati del processo non trovati.");
}

// Decodifica i dati del processo
$dati_step = json_decode($dati_step_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Errore nella decodifica dei dati del processo: " . json_last_error_msg());
}

// Dati per la firma
$nome_cliente = $dati_step['cliente']['nome'] ?? 'Nome Cliente';
$cognome_cliente = $dati_step['cliente']['cognome'] ?? 'Cognome Cliente';
$codice_fiscale = $dati_step['cliente']['cf_piva'] ?? 'Codice Fiscale';

// Gestione del form di firma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['signature'])) {
        die("Firma non ricevuta.");
    }

    $signature_data = $_POST['signature'];

    // Decodifica e salva la firma
    if (preg_match('/^data:image\/(\w+);base64,/', $signature_data, $type)) {
        $data = base64_decode(substr($signature_data, strpos($signature_data, ',') + 1));

        if ($data === false) {
            die("Errore nella decodifica della firma.");
        }

        // Assicurati che la cartella per le firme esista
        $firma_dir = __DIR__ . '/uploads/Firme/';
        if (!is_dir($firma_dir)) {
            mkdir($firma_dir, 0755, true);
        }

        // Salva la firma
        $signature_filename = 'firma_cte_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nome_cliente . '_' . $cognome_cliente) . '_' . time() . '.png';
        $signature_path = $firma_dir . $signature_filename;
        file_put_contents($signature_path, $data);

        // Salva il percorso della firma in dati_step
        $dati_step['firma_cte'] = 'uploads/Firme/' . $signature_filename;

        // Aggiorna dati_step nel database
        $dati_step_json = json_encode($dati_step, JSON_UNESCAPED_UNICODE);

        $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ? AND agente_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Errore nella preparazione della query di aggiornamento: " . $conn->error);
        }
        $step_corrente = 20; // Passiamo allo step 20
        $stmt->bind_param('siii', $dati_step_json, $step_corrente, $processo_id, $agente_id);
        if (!$stmt->execute()) {
            die("Errore nell'aggiornamento del processo: " . $stmt->error);
        }
        $stmt->close();

        // Reindirizza allo step20.php per la firma PDA
        header("Location: step20.php");
        exit();
    } else {
        die("Formato della firma non valido.");
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Firma CTE - Step 19</title>
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
        }
        h2 {
            text-align: center;
        }
        canvas {
            border: 1px solid #ccc;
            width: 100%;
            height: 300px;
        }
        .button-group {
            text-align: center;
            margin-top: 20px;
        }
        .button-group button {
            padding: 10px 20px;
            font-size: 16px;
            margin: 0 10px;
            cursor: pointer;
        }
        .button-group button#clear {
            background-color: #f44336;
            color: #fff;
            border: none;
        }
        .button-group button#submit-firma {
            background-color: #4CAF50;
            color: #fff;
            border: none;
        }
        p {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/menu.php'; ?>
    <div class="container">
        <h2>Firma CTE - Step 19</h2>
        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($nome_cliente . ' ' . $cognome_cliente); ?></p>
        <p><strong>Codice Fiscale:</strong> <?php echo htmlspecialchars($codice_fiscale); ?></p>
        <form id="firma-form" action="luceegas-step19.php" method="POST">
            <canvas id="signature-pad"></canvas>
            <input type="hidden" id="signature" name="signature">
            <div class="button-group">
                <button type="button" id="clear">Cancella</button>
                <button type="button" id="submit-firma">Prosegui</button>
            </div>
        </form>
    </div>
    <script>
        var canvas = document.getElementById('signature-pad');
        var context = canvas.getContext('2d');
        var drawing = false;

        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
        }

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        canvas.addEventListener('mousedown', function(event) {
            drawing = true;
            context.beginPath();
            context.moveTo(event.offsetX, event.offsetY);
        });

        canvas.addEventListener('mouseup', function() {
            drawing = false;
        });

        canvas.addEventListener('mousemove', function(event) {
            if (drawing) {
                context.lineTo(event.offsetX, event.offsetY);
                context.stroke();
            }
        });

        canvas.addEventListener('touchstart', function(event) {
            event.preventDefault();
            drawing = true;
            var touch = event.touches[0];
            var rect = canvas.getBoundingClientRect();
            context.beginPath();
            context.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
        });

        canvas.addEventListener('touchmove', function(event) {
            event.preventDefault();
            if (drawing) {
                var touch = event.touches[0];
                var rect = canvas.getBoundingClientRect();
                context.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
                context.stroke();
            }
        });

        canvas.addEventListener('touchend', function(event) {
            drawing = false;
        });

        document.getElementById('clear').addEventListener('click', function() {
            context.clearRect(0, 0, canvas.width, canvas.height);
        });

        document.getElementById('submit-firma').addEventListener('click', function() {
            var dataUrl = canvas.toDataURL();
            var blank = document.createElement('canvas');
            blank.width = canvas.width;
            blank.height = canvas.height;

            if (dataUrl !== blank.toDataURL()) {
                document.getElementById('signature').value = dataUrl;
                document.getElementById('firma-form').submit();
            } else {
                alert('Per favore, fornisci una firma.');
            }
        });
    </script>
</body>
</html>