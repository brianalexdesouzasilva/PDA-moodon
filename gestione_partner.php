<?php
session_start();

// Controlla se l'utente è autenticato per il backoffice
if (!isset($_SESSION['backoffice_user'])) {
    header("Location: backoffice_login.php");
    exit;
}

// Includi la connessione al database
require_once 'db_connection.php';

// Messaggio di feedback
$message = "";

// Aggiungi un nuovo partner
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_partner'])) {
    $nome_partner = $_POST['nome_partner'];
    $categoria = $_POST['categoria'];

    $sql = "INSERT INTO partner (nome_partner, categoria) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nome_partner, $categoria);

    if ($stmt->execute()) {
        $message = "Partner aggiunto con successo!";
    } else {
        $message = "Errore nell'aggiunta del partner.";
    }
    $stmt->close();
}

// Aggiungi una nuova offerta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_offerta'])) {
    $partner_id = $_POST['partner_id'];
    $nome_offerta = $_POST['nome_offerta'];

    $sql = "INSERT INTO offerte (partner_id, nome_offerta) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $partner_id, $nome_offerta);

    if ($stmt->execute()) {
        $message = "Offerta aggiunta con successo!";
    } else {
        $message = "Errore nell'aggiunta dell'offerta.";
    }
    $stmt->close();
}

// Elimina un partner
if (isset($_GET['delete_partner'])) {
    $partner_id = $_GET['delete_partner'];

    $sql = "DELETE FROM partner WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $partner_id);

    if ($stmt->execute()) {
        $message = "Partner eliminato con successo!";
    } else {
        $message = "Errore nell'eliminazione del partner.";
    }
    $stmt->close();
}

// Elimina un'offerta
if (isset($_GET['delete_offerta'])) {
    $offerta_id = $_GET['delete_offerta'];

    $sql = "DELETE FROM offerte WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $offerta_id);

    if ($stmt->execute()) {
        $message = "Offerta eliminata con successo!";
    } else {
        $message = "Errore nell'eliminazione dell'offerta.";
    }
    $stmt->close();
}

// Recupera tutti i partner
$sql = "SELECT * FROM partner";
$result_partner = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partner e Offerte</title>
    <link rel="stylesheet" href="style_backoffice.css">
</head>
<body>
    <div class="container">
        <h1>Gestione Partner e Offerte</h1>

        <?php if (!empty($message)) { ?>
            <p><?php echo $message; ?></p>
        <?php } ?>

        <h2>Aggiungi Partner</h2>
        <form action="gestione_partner.php" method="post">
            <label for="nome_partner">Nome Partner:</label>
            <input type="text" id="nome_partner" name="nome_partner" required>

            <label for="categoria">Categoria:</label>
            <select id="categoria" name="categoria" required>
                <option value="luceegas">Luce e Gas</option>
                <option value="telefonoefibra">Telefono e Fibra</option>
                <option value="green">Green</option>
                <option value="tv">TV</option>
            </select>

            <button type="submit" name="add_partner">Aggiungi Partner</button>
        </form>

        <h2>Aggiungi Offerta</h2>
        <form action="gestione_partner.php" method="post">
            <label for="partner_id">Seleziona Partner:</label>
            <select id="partner_id" name="partner_id" required>
                <?php while ($row = $result_partner->fetch_assoc()) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['nome_partner']; ?></option>
                <?php } ?>
            </select>

            <label for="nome_offerta">Nome Offerta:</label>
            <input type="text" id="nome_offerta" name="nome_offerta" required>

            <button type="submit" name="add_offerta">Aggiungi Offerta</button>
        </form>

        <h2>Lista Partner</h2>
        <?php
        $sql = "SELECT * FROM partner";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome Partner</th><th>Categoria</th><th>Azione</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row['id'] . "</td><td>" . $row['nome_partner'] . "</td><td>" . $row['categoria'] . "</td>";
                echo "<td><a href='gestione_partner.php?delete_partner=" . $row['id'] . "'>Elimina</a></td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nessun partner trovato.";
        }
        ?>

        <h2>Lista Offerte</h2>
        <?php
        $sql = "SELECT offerte.id, offerte.nome_offerta, partner.nome_partner 
                FROM offerte 
                INNER JOIN partner ON offerte.partner_id = partner.id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome Offerta</th><th>Nome Partner</th><th>Azione</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row['id'] . "</td><td>" . $row['nome_offerta'] . "</td><td>" . $row['nome_partner'] . "</td>";
                echo "<td><a href='gestione_partner.php?delete_offerta=" . $row['id'] . "'>Elimina</a></td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nessuna offerta trovata.";
        }
        ?>
    </div>
</body>
</html>