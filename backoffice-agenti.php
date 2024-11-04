<?php
session_start();

// Controlla se l'utente è autenticato come admin
if (!isset($_SESSION['backoffice_user'])) {
    header("Location: backoffice_login.php");
    exit;
}

require_once 'db_connection.php';

$message = "";

// Aggiungi un nuovo agente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_agente'])) {
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash della password
    $email = $_POST['email'];

    $sql = "INSERT INTO agenti (nome, cognome, username, password, email) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nome, $cognome, $username, $password, $email);

    if ($stmt->execute()) {
        $message = "Agente aggiunto con successo!";
    } else {
        $message = "Errore nell'aggiunta dell'agente.";
    }
    $stmt->close();
}

// Reset della password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $agente_id = $_POST['agente_id'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT); // Hash della nuova password

    $sql = "UPDATE agenti SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_password, $agente_id);

    if ($stmt->execute()) {
        $message = "Password aggiornata con successo!";
    } else {
        $message = "Errore nell'aggiornamento della password.";
    }
    $stmt->close();
}

// Elimina un agente
if (isset($_GET['delete_agente'])) {
    $agente_id = $_GET['delete_agente'];

    $sql = "DELETE FROM agenti WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $agente_id);

    if ($stmt->execute()) {
        $message = "Agente eliminato con successo!";
    } else {
        $message = "Errore nell'eliminazione dell'agente.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Agenti</title>
    <link rel="stylesheet" href="style_backoffice.css">
</head>
<body>
    <?php include 'header-backoffice.php'; ?>
    <div class="container">
        <h1>Gestione Agenti</h1>

        <?php if (!empty($message)) { ?>
            <p><?php echo $message; ?></p>
        <?php } ?>

        <!-- Sezione per aggiungere un nuovo agente -->
        <h2>Aggiungi Agente</h2>
        <form action="backoffice-agenti.php" method="post">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="cognome">Cognome:</label>
            <input type="text" id="cognome" name="cognome" required>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>


            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email">
            
            <button type="submit" name="add_agente">Aggiungi Agente</button>
        </form>

        <!-- Elenco Agenti -->
        <h2>Lista Agenti</h2>
        <?php
        $sql = "SELECT * FROM agenti";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Nome</th><th>Cognome</th><th>Username</th><th>Email</th><th>Reset Password</th><th>Azione</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['nome'] . "</td>";
                echo "<td>" . $row['cognome'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>";
                echo "<form action='backoffice-agenti.php' method='post' style='display: inline;'>";
                echo "<input type='hidden' name='agente_id' value='" . $row['id'] . "'>";
                echo "<input type='password' name='new_password' placeholder='Nuova password' required>";
                echo "<button type='submit' name='reset_password'>Reset</button>";
                echo "</form>";
                echo "</td>";
                echo "<td><a href='backoffice-agenti.php?delete_agente=" . $row['id'] . "' class='btn_elimina'>Elimina</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "Nessun agente trovato.";
        }
        ?>
    </div>
</body>
</html>