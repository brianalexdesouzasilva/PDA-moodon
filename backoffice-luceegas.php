<?php
session_start();

// Abilita la visualizzazione degli errori per il debug (rimuovi in produzione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Controlla se l'utente è autenticato per il backoffice
if (!isset($_SESSION['backoffice_user'])) {
    header("Location: backoffice_login.php");
    exit();
}

// Includi la connessione al database
require_once 'db_connection.php';

// Messaggio di feedback
$message = "";

// Funzione per convertire categoria_id in nome categoria
function getCategoriaNome($categoria_id) {
    switch ($categoria_id) {
        case 1:
            return 'Luce e Gas';
        case 2:
            return 'Telefono e Fibra';
        case 3:
            return 'Green';
        case 4:
            return 'TV';
        default:
            return 'Sconosciuto';
    }
}

// Aggiungi un nuovo partner
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_partner') {
    $nome_partner = $_POST['nome_partner'];
    $categoria = 1; // Impostiamo la categoria come "Luce e Gas"

    $sql = "INSERT INTO partner (nome_partner, categoria_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $message = "Errore nella preparazione della query: " . $conn->error;
    } else {
        $stmt->bind_param("si", $nome_partner, $categoria);
        if ($stmt->execute()) {
            $message = "Partner aggiunto con successo!";
        } else {
            $message = "Errore nell'aggiunta del partner: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Aggiungi una nuova offerta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_offerta') {
    $partner_id = $_POST['partner_id'];
    $nome_offerta = $_POST['nome_offerta'];

    $sql = "INSERT INTO offerte (partner_id, nome_offerta) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $message = "Errore nella preparazione della query: " . $conn->error;
    } else {
        $stmt->bind_param("is", $partner_id, $nome_offerta);
        if ($stmt->execute()) {
            $message = "Offerta aggiunta con successo!";
        } else {
            $message = "Errore nell'aggiunta dell'offerta: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Caricamento o Modifica PDF per Partner
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'upload_pdf_partner') {
    $partner_id = $_POST['partner_id'];
    $tipo_pdf = $_POST['tipo_pdf']; // residenziale o business

    // Recupera il nome del partner
    $sql = "SELECT nome_partner FROM partner WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $partner_id);
    $stmt->execute();
    $stmt->bind_result($nome_partner);
    $stmt->fetch();
    $stmt->close();

    // Gestione caricamento file
    $file = $_FILES['pdf_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $nome_file = strtolower(str_replace(' ', '-', $nome_partner)) . "-" . $tipo_pdf . ".pdf";
        $target_dir = "uploads/partner/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($nome_file);

        // Rimuovi il vecchio file se esiste
        if (file_exists($target_file)) {
            unlink($target_file);
        }

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            $table_name = $tipo_pdf === 'residenziale' ? 'pdf_residenziale' : 'pdf_business';
            // Controlla se esiste già un record per questo partner e tipo di PDF
            $sql = "SELECT id FROM $table_name WHERE partner_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $partner_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                // Aggiorna il record esistente
                $sql_update = "UPDATE $table_name SET nome_file = ? WHERE partner_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $nome_file, $partner_id);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // Inserisci un nuovo record
                $sql_insert = "INSERT INTO $table_name (partner_id, nome_file) VALUES (?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("is", $partner_id, $nome_file);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            $stmt->close();

            $message = "PDF caricato o aggiornato con successo!";
        } else {
            $message = "Errore nel caricamento del file.";
        }
    } else {
        $message = "Nessun file selezionato o errore nel caricamento.";
    }
}

// Elimina PDF residenziale o business
if (isset($_GET['delete_pdf']) && isset($_GET['tipo_pdf']) && isset($_GET['partner_id'])) {
    $partner_id = $_GET['partner_id'];
    $tipo_pdf = $_GET['tipo_pdf']; // residenziale o business
    $table_name = $tipo_pdf === 'residenziale' ? 'pdf_residenziale' : 'pdf_business';

    // Recupera il nome del file dal database
    $sql = "SELECT nome_file FROM $table_name WHERE partner_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $partner_id);
    $stmt->execute();
    $stmt->bind_result($nome_file);
    $stmt->fetch();
    $stmt->close();

    // Rimuovi il file dal server
    $file_path = "uploads/partner/" . $nome_file;
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Elimina il record dal database
    $sql = "DELETE FROM $table_name WHERE partner_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $partner_id);
    if ($stmt->execute()) {
        $message = "PDF $tipo_pdf eliminato con successo!";
    } else {
        $message = "Errore nell'eliminazione del PDF $tipo_pdf.";
    }
    $stmt->close();
}

// Caricamento o Modifica PDF per Offerta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'upload_pdf_offerta') {
    $offerta_id = $_POST['offerta_id'];

    // Recupera il nome dell'offerta
    $sql = "SELECT nome_offerta FROM offerte WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $offerta_id);
    $stmt->execute();
    $stmt->bind_result($nome_offerta);
    $stmt->fetch();
    $stmt->close();

    // Gestione caricamento file
    $file = $_FILES['pdf_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $nome_file = "CTE-" . strtolower(str_replace(' ', '-', $nome_offerta)) . ".pdf";
        $target_dir = "uploads/offerte/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($nome_file);

        // Rimuovi il vecchio file se esiste
        if (file_exists($target_file)) {
            unlink($target_file);
        }

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            // Controlla se esiste già un record per questa offerta
            $sql = "SELECT id FROM pdf_offerte WHERE offerta_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $offerta_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                // Aggiorna il record esistente
                $sql_update = "UPDATE pdf_offerte SET nome_file = ? WHERE offerta_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $nome_file, $offerta_id);
                $stmt_update->execute();
$stmt_update->close();
            } else {
                // Inserisci un nuovo record
                $sql_insert = "INSERT INTO pdf_offerte (offerta_id, nome_file) VALUES (?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("is", $offerta_id, $nome_file);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            $stmt->close();

            $message = "PDF offerta caricato o aggiornato con successo!";
        } else {
            $message = "Errore nel caricamento del file offerta.";
        }
    } else {
        $message = "Nessun file selezionato o errore nel caricamento.";
    }
}

// Elimina PDF Offerta
if (isset($_GET['delete_offerta_pdf']) && isset($_GET['offerta_id'])) {
    $offerta_id = $_GET['offerta_id'];

    // Recupera il nome del file dal database
    $sql = "SELECT nome_file FROM pdf_offerte WHERE offerta_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $offerta_id);
    $stmt->execute();
    $stmt->bind_result($nome_file);
    $stmt->fetch();
    $stmt->close();

    // Rimuovi il file dal server
    $file_path = "uploads/offerte/" . $nome_file;
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Elimina il record dal database
    $sql = "DELETE FROM pdf_offerte WHERE offerta_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $offerta_id);
    if ($stmt->execute()) {
        $message = "PDF offerta eliminato con successo!";
    } else {
        $message = "Errore nell'eliminazione del PDF offerta.";
    }
    $stmt->close();
}

// Elimina un'offerta
if (isset($_GET['delete_offerta'])) {
    $offerta_id = $_GET['delete_offerta'];

    // Elimina PDF associato all'offerta
    $sql = "SELECT nome_file FROM pdf_offerte WHERE offerta_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $offerta_id);
    $stmt->execute();
    $stmt->bind_result($nome_file);
    while ($stmt->fetch()) {
        $file_path = "uploads/offerte/" . $nome_file;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $stmt->close();

    // Elimina il record PDF dall'offerta
    $sql = "DELETE FROM pdf_offerte WHERE offerta_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $offerta_id);
    $stmt->execute();
    $stmt->close();

    // Elimina l'offerta
    $sql = "DELETE FROM offerte WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $message = "Errore nella preparazione della query: " . $conn->error;
    } else {
        $stmt->bind_param("i", $offerta_id);
        if ($stmt->execute()) {
            $message = "Offerta eliminata con successo!";
        } else {
            $message = "Errore nell'eliminazione dell'offerta: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Backoffice</title>
    <link rel="stylesheet" href="style_backoffice.css">
</head>
<body>
    <?php include 'header-backoffice.php'; ?>
    <div class="container">
        <h1>Gestione Partner, Offerte e PDF</h1>

        <?php if (!empty($message)) { ?>
            <p class="message"><?php echo $message; ?></p>
        <?php } ?>

        <!-- Sezione per aggiungere un partner -->
        <h2>Aggiungi Partner</h2>
        <form action="backoffice-luceegas.php" method="post">
            <input type="hidden" name="action" value="add_partner">
            <label for="nome_partner">Nome Partner:</label>
            <input type="text" id="nome_partner" name="nome_partner" required>
            <button type="submit">Aggiungi Partner</button>
        </form>
        <hr>

        <!-- Sezione per aggiungere un'offerta -->
        <h2>Aggiungi Offerta</h2>
        <form action="backoffice-luceegas.php" method="post">
            <input type="hidden" name="action" value="add_offerta">
            <label for="partner_id">Seleziona Partner:</label>
            <select id="partner_id" name="partner_id" required>
                <?php
                $sql = "SELECT * FROM partner WHERE categoria_id = 1";
                $result_partner = $conn->query($sql);
                while ($row = $result_partner->fetch_assoc()) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['nome_partner']; ?></option>
                <?php } ?>
            </select>

            <label for="nome_offerta">Nome Offerta:</label>
            <input type="text" id="nome_offerta" name="nome_offerta" required>

            <button type="submit">Aggiungi Offerta</button>
        </form>
        <hr>

        <!-- Sezione per caricare o modificare un PDF per Partner -->
        <h2>Carica o Modifica PDF per Partner</h2>
        <form action="backoffice-luceegas.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_pdf_partner">
            <label for="partner_id_pdf">Seleziona Partner:</label>
            <select id="partner_id_pdf" name="partner_id" required>
                <?php
                $sql = "SELECT * FROM partner WHERE categoria_id = 1";
                $result_partner = $conn->query($sql);
                while ($row = $result_partner->fetch_assoc()) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['nome_partner']; ?></option>
                <?php } ?>
            </select>

            <label for="tipo_pdf">Tipo PDF:</label>
            <select id="tipo_pdf" name="tipo_pdf" required>
                <option value="residenziale">Residenziale</option>
                <option value="business">Business</option>
            </select>

            <label for="pdf_file">Carica PDF:</label>
            <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" required>

            <button type="submit">Carica o Sostituisci PDF</button>
        </form>
        <hr>

        <!-- Sezione per caricare o modificare un PDF per Offerta -->
        <h2>Carica o Modifica PDF per Offerta</h2>
        <form action="backoffice-luceegas.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_pdf_offerta">
            <label for="offerta_id">Seleziona Offerta:</label>
            <select id="offerta_id" name="offerta_id" required>
                <?php
                $sql = "SELECT offerte.id, offerte.nome_offerta, partner.nome_partner 
                        FROM offerte 
                        INNER JOIN partner ON offerte.partner_id = partner.id
                        WHERE partner.categoria_id = 1";
                $result_offerte = $conn->query($sql);
                while ($row = $result_offerte->fetch_assoc()) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['nome_offerta']; ?> (<?php echo $row['nome_partner']; ?>)</option>
                <?php } ?>
            </select>

            <label for="pdf_file_offerta">Carica PDF:</label>
            <input type="file" id="pdf_file_offerta" name="pdf_file" accept="application/pdf" required>

            <button type="submit">Carica o Sostituisci PDF Offerta</button>
        </form>
        <hr>

        <!-- Lista Partner e PDF -->
        <h2>Lista Partner e PDF</h2>
        <?php
        $sql = "SELECT p.id AS partner_id, p.nome_partner, pr.nome_file AS pdf_residenziale, pb.nome_file AS pdf_business
                FROM partner p
                LEFT JOIN pdf_residenziale pr ON p.id = pr.partner_id
                LEFT JOIN pdf_business pb ON p.id = pb.partner_id
                WHERE p.categoria_id = 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr>
                    <th>Nome Partner</th>
                    <th>PDF Residenziale</th>
                    <th>PDF Business</th>
                    <th>Azione</th>
                  </tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['nome_partner']) . "</td>";

                // PDF Residenziale
               echo "<td>";
                if ($row['pdf_residenziale']) {
                    echo "<a href='uploads/partner/" . htmlspecialchars($row['pdf_residenziale']) . "' target='_blank'>Visualizza</a> | ";
                    echo "<a href='backoffice-luceegas.php?delete_pdf=residenziale&partner_id=" . $row['partner_id'] . "' class='btn_elimina'>Elimina</a>";
                } else {
                    echo "Non caricato";
                }
                echo "</td>";

                // PDF Business
                echo "<td>";
                if ($row['pdf_business']) {
                    echo "<a href='uploads/partner/" . htmlspecialchars($row['pdf_business']) . "' target='_blank'>Visualizza</a> | ";
                    echo "<a href='backoffice-luceegas.php?delete_pdf=business&partner_id=" . $row['partner_id'] . "' class='btn_elimina'>Elimina</a>";
                } else {
                    echo "Non caricato";
                }
                echo "</td>";

                // Azioni
                echo "<td><a class='btn_elimina' href='backoffice-luceegas.php?delete_partner=" . $row['partner_id'] . "'>Elimina Partner</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "Nessun partner trovato.";
        }
        ?>
        <hr>

        <!-- Lista Offerte e PDF -->
        <h2>Lista Offerte e PDF</h2>
        <?php
        $sql = "SELECT o.id AS offerta_id, o.nome_offerta, p.nome_partner, po.nome_file AS pdf_offerta
                FROM offerte o
                INNER JOIN partner p ON o.partner_id = p.id
                LEFT JOIN pdf_offerte po ON o.id = po.offerta_id
                WHERE p.categoria_id = 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr>
                    <th>Offerta</th>
                    <th>Partner</th>
                    <th>PDF Offerta</th>
                    <th>Azione</th>
                  </tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['nome_offerta']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nome_partner']) . "</td>";

                // PDF Offerta
                echo "<td>";
                if ($row['pdf_offerta']) {
                    echo "<a href='uploads/offerte/" . htmlspecialchars($row['pdf_offerta']) . "' target='_blank'>Visualizza</a> | ";
                    echo "<a href='backoffice-luceegas.php?delete_offerta_pdf=" . $row['offerta_id'] . "' class='btn_elimina'>Elimina</a>";
                } else {
                    echo "Non caricato";
                }
                echo "</td>";

                // Azioni
                echo "<td><a class='btn_elimina' href='backoffice-luceegas.php?delete_offerta=" . $row['offerta_id'] . "'>Elimina Offerta</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "Nessuna offerta trovata.";
        }
        ?>
    </div>
</body>
</html>