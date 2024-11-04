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

// Recupera il metodo di pagamento dallo step precedente
$metodo_pagamento = $dati_step['metodo_pagamento'] ?? '';

if (empty($metodo_pagamento)) {
    die("Metodo di pagamento non selezionato. Torna allo Step 7.");
}

// Gestione del form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Salva i dati in base al metodo di pagamento
    if ($metodo_pagamento == 'rid') {
        $banca = trim($_POST['banca'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $intestatario_nome = trim($_POST['intestatario_nome'] ?? '');
        $intestatario_cognome = trim($_POST['intestatario_cognome'] ?? '');
        $codicefiscale = trim($_POST['codicefiscale'] ?? '');
        $rid_intestatario = $_POST['rid_intestatario'] ?? 'NO';

        // Validazione dei campi obbligatori
        $errori = [];

        if (empty($banca)) $errori[] = "Il campo Banca è obbligatorio.";
        if (empty($iban)) $errori[] = "Il campo IBAN è obbligatorio.";
        if (empty($intestatario_nome)) $errori[] = "Il campo Nome Intestatario è obbligatorio.";
        if (empty($intestatario_cognome)) $errori[] = "Il campo Cognome Intestatario è obbligatorio.";
        if (empty($codicefiscale)) $errori[] = "Il campo Codice Fiscale è obbligatorio.";

        // Se il RID è intestato a persona diversa, verifica i campi aggiuntivi
        if ($rid_intestatario == 'SI') {
            // Gestione dei file caricati
            $rid_intestatario_fronte = $_FILES['rid_intestatario_fronte'] ?? null;
            $rid_intestatario_retro = $_FILES['rid_intestatario_retro'] ?? null;
            $signature = $_POST['signature'] ?? '';

            if ($rid_intestatario_fronte['error'] !== UPLOAD_ERR_OK) {
                $errori[] = "Errore nel caricamento del documento fronte.";
            }
            if ($rid_intestatario_retro['error'] !== UPLOAD_ERR_OK) {
                $errori[] = "Errore nel caricamento del documento retro.";
            }
            if (empty($signature)) {
                $errori[] = "La firma è obbligatoria.";
            }
        }

        if (count($errori) === 0) {
            // Salva i dati nel 'dati_step' del processo
            $dati_step['dati_pagamento'] = [
                'metodo_pagamento' => $metodo_pagamento,
                'banca' => $banca,
                'iban' => $iban,
                'intestatario_nome' => $intestatario_nome,
                'intestatario_cognome' => $intestatario_cognome,
                'codicefiscale' => $codicefiscale,
                'rid_intestatario' => $rid_intestatario,
            ];

            // Gestione dei file caricati e firma
            if ($rid_intestatario == 'SI') {
                // Salva i file sul server
                $upload_dir = __DIR__ . '/uploads/Documenti_rid_diverso/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Genera un nome univoco per i file
                $fronte_filename = uniqid('fronte_') . '_' . basename($rid_intestatario_fronte['name']);
                $retro_filename = uniqid('retro_') . '_' . basename($rid_intestatario_retro['name']);

                $fronte_path = $upload_dir . $fronte_filename;
                $retro_path = $upload_dir . $retro_filename;

                move_uploaded_file($rid_intestatario_fronte['tmp_name'], $fronte_path);
                move_uploaded_file($rid_intestatario_retro['tmp_name'], $retro_path);

                // Salva i percorsi relativi dei file
                $dati_step['dati_pagamento']['rid_intestatario_fronte'] = 'uploads/Documenti_rid_diverso/' . $fronte_filename;
                $dati_step['dati_pagamento']['rid_intestatario_retro'] = 'uploads/Documenti_rid_diverso/' . $retro_filename;

                // Gestione della firma
                if (!empty($signature)) {
                    $signature = str_replace('data:image/png;base64,', '', $signature);
                    $signature = str_replace(' ', '+', $signature);
                    $data = base64_decode($signature);
                    $signature_filename = uniqid('firma_') . '.png';
                    $file_path = $upload_dir . $signature_filename;
                    file_put_contents($file_path, $data);
                    $dati_step['dati_pagamento']['signature'] = 'uploads/Documenti_rid_diverso/' . $signature_filename;
                }
            }

            // Codifica i dati in JSON
            $dati_step_json = json_encode($dati_step);

            // Aggiorna il processo nel database
            $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Errore nella preparazione della query di aggiornamento processo: " . $conn->error);
            }
            $step_corrente = 10; // Aggiorniamo allo step successivo
            $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
            if (!$stmt->execute()) {
                die("Errore nell'aggiornamento del processo: " . $stmt->error);
            }
            $stmt->close();

            // Reindirizza allo Step 10
            header("Location: luceegas-step10.php");
            exit();
        } else {
            // Ci sono errori di validazione
            $error_message = implode("<br>", $errori);
        }
    } else {
        // Se il metodo di pagamento non è RID, procedi senza ulteriori dati
        // Aggiorna lo step corrente e reindirizza allo step successivo
        $dati_step['dati_pagamento'] = [
            'metodo_pagamento' => $metodo_pagamento,
        ];

        // Codifica i dati in JSON
        $dati_step_json = json_encode($dati_step);

        // Aggiorna il processo nel database
        $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Errore nella preparazione della query di aggiornamento processo: " . $conn->error);
        }
        $step_corrente = 10; // Aggiorniamo allo step successivo
        $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
        if (!$stmt->execute()) {
            die("Errore nell'aggiornamento del processo: " . $stmt->error);
        }
        $stmt->close();

        // Reindirizza allo Step 10
        header("Location: luceegas-step10.php");
        exit();
    }
} else {
    // Inizializza le variabili se il form non è stato inviato
    $banca = $dati_step['dati_pagamento']['banca'] ?? '';
    $iban = $dati_step['dati_pagamento']['iban'] ?? '';
    $intestatario_nome = $dati_step['dati_pagamento']['intestatario_nome'] ?? '';
    $intestatario_cognome = $dati_step['dati_pagamento']['intestatario_cognome'] ?? '';
    $codicefiscale = $dati_step['dati_pagamento']['codicefiscale'] ?? '';
    $rid_intestatario = $dati_step['dati_pagamento']['rid_intestatario'] ?? 'NO';
    $error_message = '';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci i Dati di Pagamento - Step 8</title>
    <link rel="stylesheet" href="style.css">
    <!-- Meta tag per rendere la pagina responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Stile specifico per Step 8 -->
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
            max-width: 800px;
            margin: 100px auto;
        }

        h2 {
            text-align: center;
            color: #333;
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

        .hidden {
            display: none;
        }

        /* Stile per la firma */
        .signature-pad {
            border: 1px solid #ccc;
            width: 100%;
            height: 150px;
            margin-bottom: 10px;
        }

        #clear {
            width: 100%;
            padding: 10px;
            background-color: #f44336;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        #clear:hover {
            background-color: #d32f2f;
        }

    </style>
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container">
        <h2>Inserisci i Dati di Pagamento - Step 8</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <form action="luceegas-step8.php" method="POST" enctype="multipart/form-data">
            <?php if ($metodo_pagamento == 'rid'): ?>
                <label for="banca">Banca:</label>
                <input type="text" id="banca" name="banca" value="<?php echo htmlspecialchars($banca); ?>" required>

                <label for="iban">IBAN:</label>
                <input type="text" id="iban" name="iban" value="<?php echo htmlspecialchars($iban); ?>" required>

                <label for="intestatario_nome">Nome Intestatario:</label>
                <input type="text" id="intestatario_nome" name="intestatario_nome" value="<?php echo htmlspecialchars($intestatario_nome); ?>" required>

                <label for="intestatario_cognome">Cognome Intestatario:</label>
                <input type="text" id="intestatario_cognome" name="intestatario_cognome" value="<?php echo htmlspecialchars($intestatario_cognome); ?>" required>

                <label for="codicefiscale">Codice Fiscale:</label>
                <input type="text" id="codicefiscale" name="codicefiscale" value="<?php echo htmlspecialchars($codicefiscale); ?>" required>

                <!-- Menù a tendina per RID intestato -->
                <label for="rid_intestatario">Il RID è intestato a persona diversa dal contraente?</label>
                <select id="rid_intestatario" name="rid_intestatario" onchange="toggleRidFields(this.value)">
                    <option value="NO" <?php if ($rid_intestatario == 'NO') echo 'selected'; ?>>No</option>
                    <option value="SI" <?php if ($rid_intestatario == 'SI') echo 'selected'; ?>>Sì</option>
                </select>

                <div id="rid_fields" class="hidden">
                    <label for="rid_intestatario_fronte">Carica documento fronte:</label>
                    <input type="file" id="rid_intestatario_fronte" name="rid_intestatario_fronte" accept="image/*">

                    <label for="rid_intestatario_retro">Carica documento retro:</label>
                    <input type="file" id="rid_intestatario_retro" name="rid_intestatario_retro" accept="image/*">

                    <label for="signature">Firma:</label>
                    <div id="signature-pad" class="signature-pad">
                        <canvas></canvas>
                    </div>
                    <input type="hidden" id="signature" name="signature">
                    <button type="button" id="clear">Cancella Firma</button>
                </div>
            <?php else: ?>
                <!-- Se il metodo di pagamento è diverso da RID -->
                <p>Hai selezionato il pagamento tramite <strong><?php echo htmlspecialchars($metodo_pagamento); ?></strong>. Clicca su "Avanti" per continuare.</p>
            <?php endif; ?>

            <button type="submit">Avanti</button>
        </form>
    </div>

    <!-- Script per la firma e altre funzionalità -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        function toggleRidFields(value) {
            var ridFields = document.getElementById('rid_fields');
            var ridIntestatarioFronte = document.getElementById('rid_intestatario_fronte');
            var ridIntestatarioRetro = document.getElementById('rid_intestatario_retro');

            if (value === 'SI') {
                ridFields.style.display = 'block';
                // Aggiungi required ai campi file
                ridIntestatarioFronte.required = true;
                ridIntestatarioRetro.required = true;
            } else {
                ridFields.style.display = 'none';
                // Rimuovi required dai campi file
                ridIntestatarioFronte.required = false;
                ridIntestatarioRetro.required = false;
            }
        }

        // Inizializza lo stato dei campi RID al caricamento della pagina
        document.addEventListener('DOMContentLoaded', function() {
            var ridIntestatarioSelect = document.getElementById('rid_intestatario');
            toggleRidFields(ridIntestatarioSelect.value);

            // Gestione della firma
            var canvas = document.querySelector('#signature-pad canvas');
            var signaturePad = new SignaturePad(canvas);
            var clearButton = document.getElementById('clear');

            clearButton.addEventListener('click', function () {
                signaturePad.clear();
                document.getElementById('signature').value = '';
            });

            // Gestisci l'invio del form
            document.querySelector('form').addEventListener('submit', function (event) {
                if (ridIntestatarioSelect.value === 'SI') {
                    if (signaturePad.isEmpty()) {
                        alert('Per favore, fornisci una firma.');
                        event.preventDefault();
                        return;
                    } else {
                        var dataUrl = signaturePad.toDataURL();
                        document.getElementById('signature').value = dataUrl;
                    }
                }
            });
        });
    </script>
</body>
</html>