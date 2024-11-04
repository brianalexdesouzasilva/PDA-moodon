<?php
// File: step20.php

session_start();
require_once 'db_connection.php';

use setasign\Fpdi\Fpdi;

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

// Recupera i dati del processo, inclusa la path al pdf_base
$sql = "SELECT dati_step, pdf_base FROM processi WHERE id = ? AND agente_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("ii", $processo_id, $agente_id);
$stmt->execute();
$stmt->bind_result($dati_step_json, $pdf_base);
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

// Gestione del form di firma PDA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['signature'])) {
        die("Firma non ricevuta.");
    }

    $signature_data = $_POST['signature'];

    // Decodifica e salva la firma PDA
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

        // Salva la firma PDA
        $signature_filename = 'firma_pda_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nome_cliente . '_' . $cognome_cliente) . '_' . time() . '.png';
        $signature_path = $firma_dir . $signature_filename;
        file_put_contents($signature_path, $data);

        // Salva il percorso della firma PDA in dati_step
        $dati_step['firma_pda'] = 'uploads/Firme/' . $signature_filename;

        // Genera il PDF della PDA con la firma
        require_once __DIR__ . '/vendor/autoload.php';

        // Recupera il percorso del pdf_base dalla tabella processi
        $pdf_base_path = __DIR__ . '/' . $pdf_base;
        if (!file_exists($pdf_base_path)) {
            die("File PDF base non trovato.");
        }

        $pdf = new FPDI();
        $pageCount = $pdf->setSourceFile($pdf_base_path);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == $pageCount) {
                // Aggiungi la firma nell'ultima pagina
                if (file_exists($signature_path)) {
                    $pdf->Image($signature_path, 130, 240, 50);
                }
            }
        }

        // Salva il PDF generato
        $documenti_dir = __DIR__ . '/uploads/Documenti_generati/';
        if (!is_dir($documenti_dir)) {
            mkdir($documenti_dir, 0755, true);
        }

        $pdf_output = 'pda_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nome_cliente . '_' . $cognome_cliente) . '_' . time() . '.pdf';
        $pdf_output_path = $documenti_dir . $pdf_output;
        $pdf->Output($pdf_output_path, 'F');

        // Salva il percorso del PDF PDA in dati_step
        $dati_step['pdf_pda'] = 'uploads/Documenti_generati/' . $pdf_output;

        // Popola la tabella dati_tecnici_energia con i dati da dati_step
        // Assicurati di adattare i campi in base alla tua tabella
        $dati_tecnici_energia = [
            'processo_id' => $processo_id,
            'pod' => $dati_step['dati_tecnici']['pod'] ?? '',
            'indirizzo_fornitura' => $dati_step['dati_tecnici']['indirizzo_fornitura'] ?? '',
            'cap' => $dati_step['dati_tecnici']['cap'] ?? '',
            'citta' => $dati_step['dati_tecnici']['citta'] ?? '',
            'provincia' => $dati_step['dati_tecnici']['provincia'] ?? '',
            // Aggiungi altri campi necessari
        ];

        // Prepara i dati per l'inserimento
        $columns = implode(", ", array_keys($dati_tecnici_energia));
        $placeholders = implode(", ", array_fill(0, count($dati_tecnici_energia), '?'));
        $types = str_repeat('s', count($dati_tecnici_energia)); // Adatta i tipi se necessario

        $sql_insert = "INSERT INTO dati_tecnici_energia ($columns) VALUES ($placeholders)";
        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) {
            die("Errore nella preparazione della query di inserimento dati tecnici: " . $conn->error);
        }

        // Bind dei parametri dinamicamente
        $stmt_insert->bind_param($types, ...array_values($dati_tecnici_energia));

        if (!$stmt_insert->execute()) {
            die("Errore nell'inserimento dei dati tecnici: " . $stmt_insert->error);
        }
        $stmt_insert->close();

        // Aggiorna dati_step nel database
        $dati_step_json = json_encode($dati_step, JSON_UNESCAPED_UNICODE);

        $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ? AND agente_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Errore nella preparazione della query di aggiornamento: " . $conn->error);
        }
        $step_corrente = 21; // Passiamo allo step 21
        $stmt->bind_param('siii', $dati_step_json, $step_corrente, $processo_id, $agente_id);
        if (!$stmt->execute()) {
            die("Errore nell'aggiornamento del processo: " . $stmt->error);
        }
        $stmt->close();

        // Reindirizza allo step21.php per la conferma finale
        header("Location: step21.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Firma PDA - Step 20</title>
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
        <h2>Firma PDA - Step 20</h2>
        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($nome_cliente . ' ' . $cognome_cliente); ?></p>
        <form id="firma-form" action="step20.php" method="POST">
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