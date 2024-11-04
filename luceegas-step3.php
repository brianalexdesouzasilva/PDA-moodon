<?php
session_start();
require_once 'db_connection.php';

// Abilita la visualizzazione degli errori per il debug (rimuovere in produzione)
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cf_piva = isset($_POST['cf_piva']) ? strtoupper(trim($_POST['cf_piva'])) : '';

    if (empty($cf_piva)) {
        $error_message = "Per favore, inserisci il Codice Fiscale o Partita IVA.";
    } else {
        // Recupera i dati del processo
        $sql = "SELECT dati_step FROM processi WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $processo_id);
        $stmt->execute();
        $stmt->bind_result($dati_step_json);
        $stmt->fetch();
        $stmt->close();

        $dati_step = [];
        if (!empty($dati_step_json)) {
            $dati_step = json_decode($dati_step_json, true);
            if ($dati_step === null && json_last_error() !== JSON_ERROR_NONE) {
                die("Errore nel decoding dei dati del processo.");
            }
        }

        $dati_step['cf_piva'] = $cf_piva;

        $dati_step_json = json_encode($dati_step);

        // Aggiorna il processo
        $sql = "UPDATE processi SET dati_step = ?, step_corrente = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $step_corrente = 4;
        $stmt->bind_param('sii', $dati_step_json, $step_corrente, $processo_id);
        if (!$stmt->execute()) {
            die("Errore nell'aggiornamento del processo: " . $stmt->error);
        }
        $stmt->close();

        // Reindirizza allo step 4
        header("Location: luceegas-step4.php");
        exit();
    }
} else {
    $error_message = '';
    $cf_piva = '';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Luce e Gas - Step 3</title>
    <link rel="stylesheet" href="style.css">
    <!-- Meta tag per rendere la pagina responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container">
        <h2>Luce e Gas - Step 3</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="luceegas-step3.php" method="POST">
            <label for="cf_piva">Inserisci Codice Fiscale o Partita IVA:</label>
            <input type="text" id="cf_piva" name="cf_piva" value="<?php echo htmlspecialchars($cf_piva); ?>" required>

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