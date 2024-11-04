<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'db_connection.php';

// Verifica se l'agente è autenticato
if (!isset($_SESSION['agente_id'])) {
    header("Location: login.php");
    exit();
}

$agente_id = $_SESSION['agente_id'];

// Recupera i partner per la categoria "luceegas" (categoria_id = 1)
$sql_partner = "SELECT id, nome_partner FROM partner WHERE categoria_id = 1";
$result_partner = $conn->query($sql_partner);

if (!$result_partner) {
    die("Errore nella query dei partner: " . $conn->error);
}

// Recupera le offerte associate ai partner selezionati
$sql_offerte = "SELECT o.id, o.nome_offerta, o.partner_id 
                FROM offerte o
                WHERE o.partner_id IN (SELECT id FROM partner WHERE categoria_id = 1)";
$result_offerte = $conn->query($sql_offerte);

if (!$result_offerte) {
    die("Errore nella query delle offerte: " . $conn->error);
}

// Costruisci un array di offerte per popolare il menu a tendina dinamicamente
$offerte = [];
while ($row = $result_offerte->fetch_assoc()) {
    $offerte[] = $row;
}

// Recupera la lista dei PDF disponibili
$pdf_directory = 'uploads/partner/';
if (is_dir($pdf_directory)) {
    $pdf_files = array_diff(scandir($pdf_directory), array('..', '.'));

    $pdf_options = [];
    foreach ($pdf_files as $pdf_file) {
        if (pathinfo($pdf_file, PATHINFO_EXTENSION) === 'pdf') {
            $pdf_options[] = $pdf_file;
        }
    }
} else {
    $pdf_options = [];
}

// Gestione del form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Raccogli i dati dal form
    $partner_id = $_POST['partner'] ?? '';
    $offerta_id = $_POST['offerta'] ?? '';
    $tipo_cliente = $_POST['tipo_cliente'] ?? '';
    $pdf_selezionato = $_POST['pdf_selezionato'] ?? '';

    $errori = [];

    // Validazione dei campi
    if (empty($partner_id)) {
        $errori[] = "Seleziona un partner.";
    }

    if (empty($offerta_id)) {
        $errori[] = "Seleziona un'offerta.";
    }

    if (empty($tipo_cliente)) {
        $errori[] = "Seleziona un tipo di cliente.";
    }

    if (empty($pdf_selezionato)) {
        $errori[] = "Seleziona un PDF.";
    }

    if (empty($errori)) {
        // Verifica se il PDF selezionato esiste
        $pdf_path = $pdf_directory . $pdf_selezionato;
        if (!file_exists($pdf_path)) {
            $errori[] = "Il PDF selezionato non esiste.";
        } else {
            // Crea una nuova struttura dati_step
            $dati_step = [
                'partner_id' => $partner_id,
                'offerta_id' => $offerta_id,
                'tipo_cliente' => $tipo_cliente
            ];

            // Codifica i dati in JSON
            $dati_step_json = json_encode($dati_step);

            // Inserisci un nuovo record nel database
            $sql_insert = "INSERT INTO processi (agente_id, dati_step, step_corrente, pdf_base) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if (!$stmt_insert) {
                die("Errore nella preparazione della query di inserimento: " . $conn->error);
            }
            $step_corrente = 2; // Step corrente iniziale
            $stmt_insert->bind_param("isis", $agente_id, $dati_step_json, $step_corrente, $pdf_path);
            if ($stmt_insert->execute()) {
                $processo_id = $stmt_insert->insert_id;
                $_SESSION['processo_id'] = $processo_id;
            } else {
                die("Errore nell'inserimento del processo: " . $stmt_insert->error);
            }
            $stmt_insert->close();

            // Reindirizza allo step successivo
            header("Location: luceegas-step2.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Luce e Gas - Step1</title>
    <link rel="stylesheet" href="style.css">
    <!-- Meta tag per rendere la pagina responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Stili aggiuntivi */
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
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>

    <div class="container">
        <h2>Luce e Gas - Step1</h2>
        <?php if (!empty($errori)): ?>
            <div class="alert">
                <?php foreach ($errori as $errore): ?>
                    <p><?php echo htmlspecialchars($errore); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="luceegas-step1.php" method="POST">
            <label for="partner">Seleziona Partner:</label>
            <select id="partner" name="partner" required>
                <option value="">Seleziona un partner</option>
                <?php
                // Ripristina il puntatore del risultato per i partner
                $result_partner->data_seek(0);
                while ($row = $result_partner->fetch_assoc()) {
                    $selected = ($row['id'] == ($_POST['partner'] ?? '')) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($row['id']) . "' $selected>" . htmlspecialchars($row['nome_partner']) . "</option>";
                }
                ?>
            </select>

            <label for="offerta">Seleziona Offerta:</label>
            <select id="offerta" name="offerta" required>
                <option value="">Seleziona un'offerta</option>
                <?php
                foreach ($offerte as $offerta) {
                    $selected = ($offerta['id'] == ($_POST['offerta'] ?? '')) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($offerta['id']) . "' data-partner='" . htmlspecialchars($offerta['partner_id']) . "' $selected>" . htmlspecialchars($offerta['nome_offerta']) . "</option>";
                }
                ?>
            </select>

            <label for="tipo_cliente">Tipo Cliente:</label>
            <select id="tipo_cliente" name="tipo_cliente" required>
                <option value="">Seleziona un tipo di cliente</option>
                <option value="residenziale" <?php if (($_POST['tipo_cliente'] ?? '') == 'residenziale') echo 'selected'; ?>>Residenziale</option>
                <option value="business" <?php if (($_POST['tipo_cliente'] ?? '') == 'business') echo 'selected'; ?>>Business</option>
            </select>

            <label for="pdf_selezionato">Seleziona il PDF:</label>
            <select id="pdf_selezionato" name="pdf_selezionato" required>
                <option value="">Seleziona un PDF</option>
                <?php
                foreach ($pdf_options as $pdf_file) {
                    $selected = ($pdf_file == ($_POST['pdf_selezionato'] ?? '')) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($pdf_file) . "' $selected>" . htmlspecialchars($pdf_file) . "</option>";
                }
                ?>
            </select>

            <button type="submit">Avanti</button>
        </form>
    </div>

    <script>
        // Filtra le offerte in base al partner selezionato
        document.getElementById('partner').addEventListener('change', function() {
            var partnerId = this.value;
            var offerteOptions = document.getElementById('offerta').options;
            for (var i = 0; i < offerteOptions.length; i++) {
                var option = offerteOptions[i];
                if (option.value === "") continue; // Salta l'opzione vuota
                if (option.getAttribute('data-partner') === partnerId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            }
            document.getElementById('offerta').value = ""; // Resetta la selezione delle offerte
        });
    </script>
</body>
</html>