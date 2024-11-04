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
    die("Codice Fiscale/Partita IVA non presente. Torna allo Step 3.");
}

// Controlla se il cliente esiste nella tabella "clienti"
$sql = "SELECT id FROM clienti WHERE UPPER(cf_piva) = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("s", $cf_piva);
$stmt->execute();
$stmt->bind_result($cliente_id);
$stmt->fetch();
$stmt->close();

if ($cliente_id) {
    // Il cliente esiste, salva l'ID nel processo e passa allo Step 5
    $dati_step['cliente_id'] = $cliente_id;

    // Codifica i dati in JSON
    $dati_step_json = json_encode($dati_step);

    // Aggiorna il processo nel database
    $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Errore nella preparazione della query di aggiornamento: " . $conn->error);
    }
    $step_corrente = 5; // Passa allo step 5
    $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
    if (!$stmt->execute()) {
        die("Errore nell'aggiornamento del processo: " . $stmt->error);
    }
    $stmt->close();

    // Reindirizza allo Step 5
    header("Location: luceegas-step5.php");
    exit();
} else {
    // Il cliente non esiste, mostra il form per l'inserimento dei dati
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recupera e valida i dati dal form
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $data_nascita = trim($_POST['data_nascita'] ?? '');
        $cf_piva = strtoupper(trim($_POST['cf_piva'] ?? ''));
        $luogo_nascita = trim($_POST['luogo_nascita'] ?? '');
        $indirizzo_residenza = trim($_POST['indirizzo_residenza'] ?? '');
        $provincia = trim($_POST['provincia'] ?? '');
        $comune = trim($_POST['comune'] ?? '');
        $cap = trim($_POST['cap'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $cellulare = trim($_POST['cellulare'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $ragione_sociale = trim($_POST['ragione_sociale'] ?? '');
        $indirizzo_sede_legale = trim($_POST['indirizzo_sede_legale'] ?? '');
        $cognome_rappresentante_legale = trim($_POST['cognome_rappresentante_legale'] ?? '');
        $sdi = trim($_POST['sdi'] ?? '');

        // Validazione dei campi obbligatori
        $errori = [];

        if (empty($nome)) $errori[] = "Il campo Nome è obbligatorio.";
        if (empty($cognome)) $errori[] = "Il campo Cognome è obbligatorio.";
        if (empty($data_nascita)) $errori[] = "Il campo Data di Nascita è obbligatorio.";
        if (empty($cf_piva)) $errori[] = "Il campo Codice Fiscale/Partita IVA è obbligatorio.";
        if (empty($luogo_nascita)) $errori[] = "Il campo Luogo di Nascita è obbligatorio.";
        if (empty($indirizzo_residenza)) $errori[] = "Il campo Indirizzo Residenza è obbligatorio.";
        if (empty($provincia)) $errori[] = "Il campo Provincia è obbligatorio.";
        if (empty($comune)) $errori[] = "Il campo Comune è obbligatorio.";
        if (empty($cap)) $errori[] = "Il campo CAP è obbligatorio.";
        if (empty($cellulare)) $errori[] = "Il campo Cellulare è obbligatorio.";
        if (empty($email)) $errori[] = "Il campo Email è obbligatorio.";

        if (count($errori) === 0) {
            // Inserisci il nuovo cliente nel database
            $sql = "INSERT INTO clienti (agente_id, nome, cognome, data_nascita, cf_piva, luogo_nascita, indirizzo_residenza, provincia, comune, cap, telefono, cellulare, email, ragione_sociale, indirizzo_sede_legale, cognome_rappresentante_legale, sdi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Errore nella preparazione della query di inserimento cliente: " . $conn->error);
            }
            $stmt->bind_param("issssssssssssssss", $agente_id, $nome, $cognome, $data_nascita, $cf_piva, $luogo_nascita, $indirizzo_residenza, $provincia, $comune, $cap, $telefono, $cellulare, $email, $ragione_sociale, $indirizzo_sede_legale, $cognome_rappresentante_legale, $sdi);
            if (!$stmt->execute()) {
                die("Errore nell'inserimento del cliente: " . $stmt->error);
            }
            $cliente_id = $stmt->insert_id;
            $stmt->close();

            // Salva l'ID del cliente e i dati nel processo
            $dati_step['cliente_id'] = $cliente_id;
            $dati_step['cliente'] = [
                'nome' => $nome,
                'cognome' => $cognome,
                'data_nascita' => $data_nascita,
                'cf_piva' => $cf_piva,
                'luogo_nascita' => $luogo_nascita,
                'indirizzo_residenza' => $indirizzo_residenza,
                'provincia' => $provincia,
                'comune' => $comune,
                'cap' => $cap,
                'telefono' => $telefono,
                'cellulare' => $cellulare,
                'email' => $email,
                'ragione_sociale' => $ragione_sociale,
                'indirizzo_sede_legale' => $indirizzo_sede_legale,
                'cognome_rappresentante_legale' => $cognome_rappresentante_legale,
                'sdi' => $sdi
            ];

            // Codifica i dati in JSON
            $dati_step_json = json_encode($dati_step);

            // Aggiorna il processo nel database
            $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Errore nella preparazione della query di aggiornamento processo: " . $conn->error);
            }
            $step_corrente = 5; // Aggiorniamo allo step successivo
            $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
            if (!$stmt->execute()) {
                die("Errore nell'aggiornamento del processo: " . $stmt->error);
            }
            $stmt->close();
                        // Reindirizza allo Step 5
            header("Location: luceegas-step5.php");
            exit();
        } else {
            // Ci sono errori di validazione
            $error_message = implode("<br>", $errori);
        }
    } else {
        // Campi vuoti allo Step 4
        $nome = '';
        $cognome = '';
        $data_nascita = '';
        $luogo_nascita = '';
        $indirizzo_residenza = '';
        $provincia = '';
        $comune = '';
        $cap = '';
        $telefono = '';
        $cellulare = '';
        $email = '';
        $ragione_sociale = '';
        $indirizzo_sede_legale = '';
        $cognome_rappresentante_legale = '';
        $sdi = '';
        $error_message = '';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Luce e Gas - Step 4</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container-step4 {
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

            .container-step4 {
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
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container-step4">
        <h2>Luce e Gas - Step 4</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <form action="luceegas-step4.php" method="POST">
            <div class="row">
                <!-- Campi del form per l'inserimento del cliente -->
                <div class="column">
                    <label for="cf_piva">Codice Fiscale/Partita IVA:</label>
                    <input type="text" id="cf_piva" name="cf_piva" value="<?php echo htmlspecialchars($cf_piva); ?>" required>
                </div>
                <div class="column">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>
                <div class="column">
                    <label for="cognome">Cognome:</label>
                    <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($cognome); ?>" required>
                </div>
                <div class="column">
                    <label for="data_nascita">Data di Nascita:</label>
                    <input type="date" id="data_nascita" name="data_nascita" value="<?php echo htmlspecialchars($data_nascita); ?>" required>
                </div>
                <div class="column">
                    <label for="luogo_nascita">Luogo di Nascita:</label>
                    <input type="text" id="luogo_nascita" name="luogo_nascita" value="<?php echo htmlspecialchars($luogo_nascita); ?>" required>
                </div>
                <div class="column">
                    <label for="indirizzo_residenza">Indirizzo Residenza:</label>
                    <input type="text" id="indirizzo_residenza" name="indirizzo_residenza" value="<?php echo htmlspecialchars($indirizzo_residenza); ?>" required>
                </div>
                <div class="column">
                    <label for="provincia">Provincia:</label>
                    <select id="provincia" name="provincia" required>
                        <option value="">Seleziona una provincia</option>
                        <!-- Le province saranno popolate tramite JavaScript -->
                    </select>
                </div>
                <div class="column">
                    <label for="comune">Comune:</label>
                    <select id="comune" name="comune" required>
                        <option value="">Seleziona un comune</option>
                        <!-- I comuni saranno popolate in base alla provincia selezionata -->
                    </select>
                </div>
                <div class="column">
                    <label for="cap">CAP:</label>
                    <input type="text" id="cap" name="cap" value="<?php echo htmlspecialchars($cap); ?>" required>
                </div>
                <div class="column">
                    <label for="telefono">Telefono:</label>
                    <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>">
                </div>
                <div class="column">
                    <label for="cellulare">Cellulare:</label>
                    <input type="text" id="cellulare" name="cellulare" value="<?php echo htmlspecialchars($cellulare); ?>" required>
                </div>
                <div class="column">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <!-- Sezione extra per Business -->
                <div class="column">
                    <label for="ragione_sociale">Ragione Sociale:</label>
                    <input type="text" id="ragione_sociale" name="ragione_sociale" value="<?php echo htmlspecialchars($ragione_sociale); ?>">
                </div>
                <div class="column">
                    <label for="indirizzo_sede_legale">Indirizzo Sede Legale:</label>
                    <input type="text" id="indirizzo_sede_legale" name="indirizzo_sede_legale" value="<?php echo htmlspecialchars($indirizzo_sede_legale); ?>">
                </div>
                <div class="column">
                    <label for="cognome_rappresentante_legale">Cognome Rappresentante Legale:</label>
                    <input type="text" id="cognome_rappresentante_legale" name="cognome_rappresentante_legale" value="<?php echo htmlspecialchars($cognome_rappresentante_legale); ?>">
                </div>
                <div class="column">
                    <label for="sdi">Codice SDI:</label>
                    <input type="text" id="sdi" name="sdi" value="<?php echo htmlspecialchars($sdi); ?>">
                </div>
                <!-- Pulsanti -->
                <div class="column full-width">
                    <button type="button" onclick="goBack()">Indietro</button>
                    <button type="submit">Avanti</button>
                </div>
            </div>
        </form>
    </div>

    <!-- JavaScript per il menu e per il popolamento delle province e comuni -->
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

        // Popolamento province e comuni con i dati dal JSON
        document.addEventListener("DOMContentLoaded", function() {
            const provinceSelect = document.getElementById("provincia");
            const comuneSelect = document.getElementById("comune");

            const provinceData = <?php echo file_get_contents('json-luogo/            gi_province.json'); ?>;
            const comuneData = <?php echo file_get_contents('json-luogo/gi_comuni.json'); ?>;

            // Popolamento delle province
            provinceData.forEach(function(provincia) {
                let option = document.createElement("option");
                option.value = provincia.sigla_provincia;
                option.text = provincia.denominazione_provincia;
                provinceSelect.appendChild(option);
            });

            // Popolamento dei comuni in base alla provincia selezionata
            function populateComuni() {
                comuneSelect.innerHTML = '<option value="">Seleziona un comune</option>';
                const selectedProvincia = provinceSelect.value;

                comuneData.forEach(function(comune) {
                    if (comune.sigla_provincia === selectedProvincia) {
                        let option = document.createElement("option");
                        option.value = comune.denominazione_ita;
                        option.text = comune.denominazione_ita;
                        comuneSelect.appendChild(option);
                    }
                });
            }

            provinceSelect.addEventListener("change", populateComuni);
        });
    </script>
</body>
</html>