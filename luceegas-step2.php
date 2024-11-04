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
    // Sessione non valida, distruggi la sessione e reindirizza al login
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

// Gestione della richiesta POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione e sanitizzazione dell'input
    $pod_pdr_scelta = $_POST['pod_pdr_scelta'] ?? '';
    $tipo_attivazione = $_POST['tipo_attivazione'] ?? '';
    $appuntamento_switch = $_POST['appuntamento_switch'] ?? '';

    if (empty($pod_pdr_scelta) || empty($tipo_attivazione) || empty($appuntamento_switch)) {
        $error_message = "Per favore, compila tutti i campi richiesti.";
    } else {
        // Recupera i dati precedenti dal processo
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

        // Aggiungi i nuovi dati
        $dati_step['pod_pdr_scelta'] = $pod_pdr_scelta;
        $dati_step['tipo_attivazione'] = $tipo_attivazione;
        $dati_step['appuntamento_switch'] = $appuntamento_switch;

        // Codifica i dati in JSON
        $dati_step_json = json_encode($dati_step);

        // Aggiorna il record del processo nel database
        $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Errore nella preparazione della query di aggiornamento: " . $conn->error);
        }
        $step_corrente = 2; // Aggiorniamo allo step successivo
        $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
        if (!$stmt->execute()) {
            die("Errore nell'aggiornamento del processo: " . $stmt->error);
        }
        $stmt->close();

        // Reindirizza allo step successivo
        header("Location: luceegas-step3.php");
        exit();
    }
}

// Se ci sono errori, visualizzali
$error_message = $error_message ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Luce e Gas - Step 2</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container">
        <h2>Seleziona Opzioni</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="luceegas-step2.php" method="POST">
            <label for="pod_pdr_scelta">POD/PDR Scelta:</label>
            <select name="pod_pdr_scelta" id="pod_pdr_scelta" required>
                <option value="">Seleziona</option>
                <option value="pod">POD</option>
                <option value="pdr">PDR</option>
                <option value="pod_pdr">POD/PDR</option>
            </select>

            <label for="tipo_attivazione">Tipo Attivazione:</label>
            <select name="tipo_attivazione" id="tipo_attivazione" required>
                <option value="">Seleziona</option>
                <option value="prima_attivazione">Prima Attivazione</option>
                <option value="subentro">Subentro</option>
                <option value="switch_luce_con_voltura">Switch Luce con Voltura</option>
                <option value="switch">Switch</option>
            </select>

            <label for="appuntamento_switch">Appuntamento/Switch:</label>
            <select name="appuntamento_switch" id="appuntamento_switch" required>
                <option value="">Seleziona</option>
                <option value="appuntamento">Appuntamento</option>
                <option value="autonomia">Autonomia</option>
                <option value="lead">Lead</option>
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