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

if (empty($cf_piva)) {
    die("Codice Fiscale o Partita IVA mancante. Torna allo Step 3.");
}

// Mantieni i dati del cliente per tutti gli step
$cliente_id = $dati_step['cliente_id'] ?? null;
if (!$cliente_id) {
    die("Dati del cliente mancanti. Torna allo Step 4.");
}

// Recupera la selezione dallo step2
$pod_pdr_scelta = $dati_step['pod_pdr_scelta'] ?? '';

if (empty($pod_pdr_scelta)) {
    die("Scelta POD/PDR mancante. Torna allo Step 2.");
}

// Seleziona i dati del cliente per l'indirizzo residenza
$sql = "SELECT indirizzo_residenza, comune, provincia, cap FROM clienti WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query cliente: " . $conn->error);
}
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $cliente_residenza = $result->fetch_assoc();
} else {
    die("Errore nel recupero dei dati del cliente.");
}
$stmt->close();

// Gestione del form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera i dati dal form in base alla selezione POD/PDR
    $pod = strtoupper(trim($_POST['pod'] ?? ''));
    $pdr = strtoupper(trim($_POST['pdr'] ?? ''));
    $kw_disponibili = trim($_POST['KW_disponibili'] ?? '');
    $kw_impegnati = trim($_POST['KW_impegnati'] ?? '');
    $smc = trim($_POST['smc'] ?? '');
    $monofase_trifase = trim($_POST['monofase_trifase'] ?? '');
    $consumo_annuo_luce = trim($_POST['consumo_annuo_luce'] ?? '');
    $consumo_annuo_gas = trim($_POST['consumo_annuo_gas'] ?? '');
    $attuale_gestore_luce = trim($_POST['attuale_gestore_luce'] ?? '');
    $attuale_gestore_gas = trim($_POST['attuale_gestore_gas'] ?? '');
    $indirizzo_scelta = $_POST['indirizzo_scelta'] ?? '';
    $indirizzo_fornitura = trim($_POST['indirizzo_fornitura'] ?? '');
    $civico = trim($_POST['civico'] ?? '');
    $citta = trim($_POST['citta'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $cap = trim($_POST['cap'] ?? '');

    // Se l'indirizzo di fornitura è uguale all'indirizzo di residenza
    if ($indirizzo_scelta == 'residenza') {
        $indirizzo_fornitura = $cliente_residenza['indirizzo_residenza'];
        $citta = $cliente_residenza['comune'];
        $provincia = $cliente_residenza['provincia'];
        $cap = $cliente_residenza['cap'];
    }

    // Validazione dei campi obbligatori
    $errori = [];

    if (($pod_pdr_scelta == 'pod' || $pod_pdr_scelta == 'pod_pdr') && empty($pod)) {
        $errori[] = "Il campo POD è obbligatorio.";
    }
    if (($pod_pdr_scelta == 'pdr' || $pod_pdr_scelta == 'pod_pdr') && empty($pdr)) {
        $errori[] = "Il campo PDR è obbligatorio.";
    }
    if (empty($indirizzo_scelta)) {
        $errori[] = "Selezionare l'indirizzo di fornitura.";
    }
    if ($indirizzo_scelta == 'diverso') {
        if (empty($indirizzo_fornitura)) $errori[] = "Il campo Indirizzo Fornitura è obbligatorio.";
        if (empty($citta)) $errori[] = "Il campo Città è obbligatorio.";
        if (empty($provincia)) $errori[] = "Il campo Provincia è obbligatorio.";
        if (empty($cap)) $errori[] = "Il campo CAP è obbligatorio.";
    }

    if (count($errori) === 0) {
        // Salva i dati nel 'dati_step' del processo
        $dati_step['dati_tecnici'] = [
            'pod' => $pod,
            'pdr' => $pdr,
            'KW_disponibili' => $kw_disponibili,
            'KW_impegnati' => $kw_impegnati,
            'smc' => $smc,
            'monofase_trifase' => $monofase_trifase,
            'consumo_annuo_luce' => $consumo_annuo_luce,
            'consumo_annuo_gas' => $consumo_annuo_gas,
            'attuale_gestore_luce' => $attuale_gestore_luce,
            'attuale_gestore_gas' => $attuale_gestore_gas,
            'indirizzo_fornitura' => $indirizzo_fornitura,
            'civico' => $civico,
            'citta' => $citta,
            'provincia' => $provincia,
            'cap' => $cap,
            'indirizzo_scelta' => $indirizzo_scelta
        ];

        // Codifica i dati in JSON
        $dati_step_json = json_encode($dati_step);

        // Aggiorna il processo nel database
        $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Errore nella preparazione della query di aggiornamento processo: " . $conn->error);
        }
        $step_corrente = 7; // Aggiorniamo allo step successivo
        $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
        if (!$stmt->execute()) {
            die("Errore nell'aggiornamento del processo: " . $stmt->error);
        }
        $stmt->close();

        // Reindirizza allo Step 7
        header("Location: luceegas-step7.php");
        exit();
    } else {
        // Ci sono errori di validazione
        $error_message = implode("<br>", $errori);
    }
} else {
    // Se non è stato inviato il form, inizializza le variabili
    $pod = '';
    $pdr = '';
    $kw_disponibili = '';
    $kw_impegnati = '';
    $smc = '';
    $monofase_trifase = '';
    $consumo_annuo_luce = '';
    $consumo_annuo_gas = '';
    $attuale_gestore_luce = '';
    $attuale_gestore_gas = '';
    $indirizzo_scelta = '';
    $indirizzo_fornitura = '';
    $civico = '';
    $citta = '';
    $provincia = '';
    $cap = '';
    $error_message = '';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dati Tecnici - Step 6</title>
    <link rel="stylesheet" href="style.css">
    <!-- Meta tag per rendere la pagina responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Qui puoi inserire il tuo CSS */
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

        form {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        label {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="date"],
        input[type="email"],
        select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            margin-bottom: 20px;
        }

        .column {
            flex: 0 0 48%;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            .column {
                flex: 0 0 100%;
            }

            .container {
                padding: 20px;
            }
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 10px;
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

        .full-width {
            flex: 0 0 100%;
        }

    </style>
    <script>
        // Funzione per mostrare o nascondere i campi in base alla scelta dell'indirizzo
        function toggleIndirizzoFornitura() {
            var scelta = document.getElementById('indirizzo_scelta').value;
            var indirizzoFields = document.getElementById('indirizzo_fields');
            if (scelta === 'diverso') {
                indirizzoFields.style.display = 'block';
            } else {
                indirizzoFields.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container">
        <h2>Inserisci i Dati Tecnici - Step 6</h2>
        <?php if (!empty($error_message)): ?>
            <div class="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        <form action="luceegas-step6.php" method="POST">
            <!-- Sezione LUCE -->
            <?php if ($pod_pdr_scelta == 'pod' || $pod_pdr_scelta == 'pod_pdr'): ?>
                <h4>LUCE</h4>
                <div class="column">
                    <label for="pod">POD:</label>
                    <input type="text" id="pod" name="pod" value="<?php echo htmlspecialchars($pod); ?>" required>

                    <label for="KW_disponibili">kW disponibili:</label>
                    <input type="text" id="KW_disponibili" name="KW_disponibili" value="<?php echo htmlspecialchars($kw_disponibili); ?>">

                    <label for="KW_impegnati">kW impegnati:</label>
                    <input type="text" id="KW_impegnati" name="KW_impegnati" value="<?php echo htmlspecialchars($kw_impegnati); ?>">

                    <label for="monofase_trifase">Monofase o Trifase:</label>
                    <select id="monofase_trifase" name="monofase_trifase">
                        <option value="">Seleziona</option>
                        <option value="monofase" <?php if ($monofase_trifase == 'monofase') echo 'selected'; ?>>Monofase</option>
                        <option value="trifase" <?php if ($monofase_trifase == 'trifase') echo 'selected'; ?>>Trifase</option>
                    </select>

                    <label for="consumo_annuo_luce">Consumo Annuo (Luce):</label>
                    <input type="text" id="consumo_annuo_luce" name="consumo_annuo_luce" value="<?php echo htmlspecialchars($consumo_annuo_luce); ?>">

                    <label for="attuale_gestore_luce">Attuale Gestore (Luce):</label>
                    <input type="text" id="attuale_gestore_luce" name="attuale_gestore_luce" value="<?php echo htmlspecialchars($attuale_gestore_luce); ?>">
                </div>
            <?php endif; ?>

            <!-- Sezione GAS -->
            <?php if ($pod_pdr_scelta == 'pdr' || $pod_pdr_scelta == 'pod_pdr'): ?>
                <h4>GAS</h4>
                <div class="column">
                    <label for="pdr">PDR:</label>
                    <input type="text" id="pdr" name="pdr" value="<?php echo htmlspecialchars($pdr); ?>" required>

                    <label for="consumo_annuo_gas">Consumo Annuo (Gas):</label>
                    <input type="text" id="consumo_annuo_gas" name="consumo_annuo_gas" value="<?php echo htmlspecialchars($consumo_annuo_gas); ?>">

                    <label for="attuale_gestore_gas">Attuale Gestore (Gas):</label>
                    <input type="text" id="attuale_gestore_gas" name="attuale_gestore_gas" value="<?php echo htmlspecialchars($attuale_gestore_gas); ?>">
                </div>
            <?php endif; ?>

            <!-- Menù a tendina per scegliere se l'indirizzo è uguale a quello di residenza -->
            <label for="indirizzo_scelta">Indirizzo di Fornitura:</label>
            <select id="indirizzo_scelta" name="indirizzo_scelta" onchange="toggleIndirizzoFornitura()" required>
                <option value="">Seleziona</option>
                <option value="residenza" <?php if ($indirizzo_scelta == 'residenza') echo 'selected'; ?>>Uguale all'indirizzo di residenza</option>
                <option value="diverso" <?php if ($indirizzo_scelta == 'diverso') echo 'selected'; ?>>Diverso dall'indirizzo di residenza</option>
            </select>

            <!-- Se l'utente seleziona "diverso", questi campi vengono mostrati -->
            <div id="indirizzo_fields" style="display: none;">
                <label for="indirizzo_fornitura">Indirizzo Fornitura:</label>
                <input type="text" id="indirizzo_fornitura" name="indirizzo_fornitura" value="<?php echo htmlspecialchars($indirizzo_fornitura); ?>">

                <label for="civico">Civico:</label>
                <input type="text" id="civico" name="civico" value="<?php echo htmlspecialchars($civico); ?>">

                <label for="citta">Città:</label>
                <input type="text" id="citta" name="citta" value="<?php echo htmlspecialchars($citta); ?>">

                <label for="provincia">Provincia:</label>
                <select id="provincia" name="provincia">
                    <option value="">Seleziona una provincia</option>
                    <!-- Le province saranno popolate tramite JavaScript -->
                </select>

                <label for="cap">CAP:</label>
                <input type="text" id="cap" name="cap" value="<?php echo htmlspecialchars($cap); ?>">
            </div>

            <div class="column full-width">
                <button type="button" onclick="goBack()">Indietro</button>
                <button type="submit" name="submit_dati_tecnici">Avanti</button>
            </div>
        </form>
    </div>

    <!-- JavaScript per il menu e per il popolamento delle province -->
    <script>
        function openNav() {
            document.getElementById("mySidenav").style.width = "250px";
        }

        function closeNav() {
            document.getElementById("mySidenav").style.width = "0";
        }

        function goBack() {
            window.history.back();
        }

        // Funzione per mostrare o nascondere i campi in base alla scelta dell'indirizzo
        function toggleIndirizzoFornitura() {
            var scelta = document.getElementById('indirizzo_scelta').value;
            var indirizzoFields = document.getElementById('indirizzo_fields');
            if (scelta === 'diverso') {
                indirizzoFields.style.display = 'block';
            } else {
                indirizzoFields.style.display = 'none';
            }
        }

        // Popolamento dinamico delle province
        document.addEventListener("DOMContentLoaded", function() {
            toggleIndirizzoFornitura();
            const provinceSelect = document.getElementById("provincia");

            const provinceData = <?php echo file_get_contents('json-luogo/gi_province.json'); ?>;

            provinceData.forEach(function(provincia) {
                let option = document.createElement("option");
                option.value = provincia.sigla_provincia;
                option.text = provincia.denominazione_provincia;
                if (provincia.sigla_provincia === "<?php echo htmlspecialchars($provincia); ?>") {
                    option.selected = true;
                }
                provinceSelect.appendChild(option);
            });
        });
    </script>
</body>
</html>