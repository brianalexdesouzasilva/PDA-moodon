<?php
session_start();
require_once 'db_connection.php';

// Abilita la visualizzazione degli errori (da rimuovere in produzione)
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

// Recupera il cliente_id e cf_piva da dati_step
$cliente_id = $dati_step['cliente_id'] ?? null;
$cf_piva = $dati_step['cf_piva'] ?? '';

if (empty($cf_piva) || !$cliente_id) {
    die("Dati del cliente mancanti. Torna allo Step precedente.");
}

// Recupera i dati del cliente per rinominare i file
$sql = "SELECT nome, cognome FROM clienti WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query cliente: " . $conn->error);
}
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
} else {
    die("Errore nel recupero dei dati del cliente.");
}
$stmt->close();

$nome_cliente = $cliente['nome'];
$cognome_cliente = $cliente['cognome'];

// Gestione del form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione dei campi obbligatori
    $tipo_documento = $_POST['tipo_documento'] ?? '';
    $emesso_da = $_POST['emesso_da'] ?? '';
    $data_emissione = $_POST['data_emissione'] ?? '';
    $ente_luogo = $_POST['ente_luogo'] ?? ''; // Campo libero

    $errori = [];

    if (empty($tipo_documento)) $errori[] = "Il campo Tipo Documento è obbligatorio.";
    if (empty($emesso_da)) $errori[] = "Il campo Emesso da è obbligatorio.";
    if (empty($data_emissione)) $errori[] = "Il campo Data Emissione è obbligatorio.";
    if (empty($ente_luogo)) $errori[] = "Il campo Ente/Luogo è obbligatorio.";

    // Verifica il caricamento dei file obbligatori
    $files = ['documento_fronte', 'documento_retro', 'documento_cf'];
    foreach ($files as $file) {
        if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
            $errori[] = "Errore durante il caricamento del file: $file.";
        }
    }

    if (empty($errori)) {
        // Caricamento dei file nella cartella "uploads/Documenti_cliente"
        $upload_dir = __DIR__ . '/uploads/Documenti_cliente/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Funzione per rinominare i file durante il caricamento
        function uploadFile($file_input_name, $upload_dir, $tipo_documento, $nome_cliente, $cognome_cliente, $fronte_retro) {
            $file = $_FILES[$file_input_name];
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);

            // Sanifica le stringhe per evitare problemi nei nomi dei file
            $tipo_documento_sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($tipo_documento));
            $nome_cliente_sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($nome_cliente));
            $cognome_cliente_sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($cognome_cliente));
            $fronte_retro_sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($fronte_retro));

            $new_name = "{$tipo_documento_sanitized}_{$nome_cliente_sanitized}_{$cognome_cliente_sanitized}";
            if (!empty($fronte_retro_sanitized)) {
                $new_name .= "_{$fronte_retro_sanitized}";
            }
            $new_name .= ".{$file_extension}";
            $target_file = $upload_dir . $new_name;

            // Evita conflitti di nomi di file
            $counter = 1;
            while (file_exists($target_file)) {
                $new_name_with_counter = "{$tipo_documento_sanitized}_{$nome_cliente_sanitized}_{$cognome_cliente_sanitized}";
                if (!empty($fronte_retro_sanitized)) {
                    $new_name_with_counter .= "_{$fronte_retro_sanitized}";
                }
                $new_name_with_counter .= "_{$counter}.{$file_extension}";
                $target_file = $upload_dir . $new_name_with_counter;
                $counter++;
            }

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                return 'uploads/Documenti_cliente/' . basename($target_file); // Percorso relativo per salvare nel database
            } else {
                return false;
            }
        }

        // Carica i file e verifica
        $documento_fronte = uploadFile('documento_fronte', $upload_dir, $tipo_documento, $nome_cliente, $cognome_cliente, 'fronte');
        $documento_retro = uploadFile('documento_retro', $upload_dir, $tipo_documento, $nome_cliente, $cognome_cliente, 'retro');
        $documento_cf = uploadFile('documento_cf', $upload_dir, 'codice_fiscale', $nome_cliente, $cognome_cliente, '');

        if ($documento_fronte && $documento_retro && $documento_cf) {
            // Aggiorna 'dati_step' con i nuovi dati
            $dati_step['documenti'] = [
                'tipo_documento' => $tipo_documento,
                'emesso_da' => $emesso_da,
                'data_emissione' => $data_emissione,
                'ente_luogo' => $ente_luogo,
                'documento_fronte' => $documento_fronte,
                'documento_retro' => $documento_retro,
                'documento_cf' => $documento_cf,
            ];

            // Codifica i dati in JSON
            $dati_step_json = json_encode($dati_step);

            // Aggiorna il processo nel database
            $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Errore nella preparazione della query di aggiornamento processo: " . $conn->error);
            }
            $step_corrente = 19; // Aggiorniamo allo step 19
            $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
            if (!$stmt->execute()) {
                die("Errore nell'aggiornamento del processo: " . $stmt->error);
            }
            $stmt->close();

            // Reindirizza allo Step 19
            header("Location: luceegas-step19.php");
            exit();
        } else {
            // Errore durante il caricamento dei file
            $error_message = "Si sono verificati errori durante il caricamento dei documenti.";
        }
    } else {
        // Ci sono errori di validazione
        $error_message = implode("<br>", $errori);
    }
} else {
    // Inizializza le variabili se il form non è stato inviato
    $tipo_documento = '';
    $emesso_da = '';
    $data_emissione = '';
    $ente_luogo = '';
    $error_message = '';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Caricamento Documenti - Step 10</title>
    <link rel="stylesheet" href="style.css">
    <!-- Meta tag per rendere la pagina responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* I tuoi stili CSS */
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            height: 100%;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
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

        input[type="text"],
        input[type="date"],
        input[type="file"],
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
            margin-top: 10px;
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

        /* Stile per la rotellina di caricamento */
        .spinner {
            border: 12px solid #f3f3f3; /* Grigio chiaro */
            border-top: 12px solid #3498db; /* Blu */
            border-radius: 50%;
            width: 80px;
            height: 80px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container">
        <h3>Dati Cliente</h3>
        <!-- Visualizza i dati del cliente -->
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($nome_cliente); ?></p>
        <p><strong>Cognome:</strong> <?php echo htmlspecialchars($cognome_cliente); ?></p>
        <p><strong>Codice Fiscale/P.IVA:</strong> <?php echo htmlspecialchars($cf_piva); ?></p>

        <h2>Carica i Documenti - Step 10</h2>
        <?php if (!empty($error_message)): ?>
            <div class="alert"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Form per il caricamento dei documenti -->
        <form action="luceegas-step10.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="tipo_documento">Tipo Documento:</label>
                <select id="tipo_documento" name="tipo_documento" required>
                    <option value="">Seleziona un tipo di documento</option>
                    <option value="Carta Identità">Carta Identità</option>
                    <option value="Patente">Patente</option>
                    <option value="Passaporto">Passaporto</option>
                </select>
            </div>

            <div class="form-group">
                <label for="emesso_da">Emesso da:</label>
                <select id="emesso_da" name="emesso_da" required>
                    <option value="">Seleziona un ente</option>
                    <option value="Comune">Comune</option>
                    <option value="Questura">Questura</option>
                    <option value="Motorizzazione">Motorizzazione</option>
                </select>
            </div>

            <div class="form-group">
                <label for="data_emissione">Data Emissione:</label>
                <input type="date" id="data_emissione" name="data_emissione" required>
            </div>

            <div class="form-group">
                <label for="ente_luogo">Ente/Luogo:</label>
                <input type="text" id="ente_luogo" name="ente_luogo" required>
            </div>

            <div class="form-group">
                <label for="documento_fronte">Documento Fronte:</label>
                <input type="file" id="documento_fronte" name="documento_fronte" accept="image/*,application/pdf" required>
            </div>

            <div class="form-group">
                <label for="documento_retro">Documento Retro:</label>
                <input type="file" id="documento_retro" name="documento_retro" accept="image/*,application/pdf" required>
            </div>

            <div class="form-group">
                <label for="documento_cf">Documento Codice Fiscale:</label>
                <input type="file" id="documento_cf" name="documento_cf" accept="image/*,application/pdf" required>
            </div>

            <button type="submit">Avanti</button>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function() {
            // Mostra il loader
            var loader = document.createElement('div');
            loader.id = 'loader';
            loader.style.position = 'fixed';
            loader.style.top = '0';
            loader.style.left = '0';
            loader.style.width = '100%';
            loader.style.height = '100%';
            loader.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
            loader.style.display = 'flex';
            loader.style.justifyContent = 'center';
            loader.style.alignItems = 'center';
            loader.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loader);
        });
    </script>

</body>
</html>