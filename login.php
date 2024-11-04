<?php
session_start();
require_once 'db_connection.php'; // Collegamento al database

// Abilita la visualizzazione degli errori per il debug (da rimuovere in produzione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Messaggio di errore
$error_message = "";

// Se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera e valida l'input dell'utente
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Verifica che i campi non siano vuoti
    if (empty($username) || empty($password)) {
        $error_message = "Per favore, inserisci sia il nome utente che la password.";
    } else {
        // Query per trovare l'agente nel database
        $sql = "SELECT id, password FROM agenti WHERE username = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Errore nella preparazione della query: " . $conn->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            // L'utente esiste
            $stmt->bind_result($id, $hashed_password);
            $stmt->fetch();

            // Verifica della password hashata
            if (password_verify($password, $hashed_password)) {
                // Login riuscito
                session_regenerate_id(true); // Previene attacchi di session fixation
                $new_session_id = session_id();

                // Aggiorna il session_id nel database per gestire sessioni uniche
                $update_sql = "UPDATE agenti SET session_id = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                if (!$update_stmt) {
                    die("Errore nella preparazione della query di aggiornamento: " . $conn->error);
                }
                $update_stmt->bind_param("si", $new_session_id, $id);
                $update_stmt->execute();
                $update_stmt->close();

                // Salva l'agente_id nella sessione
                $_SESSION['agente_id'] = $id;

                // Inizializza un nuovo processo
                $sql = "INSERT INTO processi (agente_id, step_corrente) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die("Errore nella preparazione della query di inserimento processo: " . $conn->error);
                }
                $step_corrente = 1; // Iniziamo dallo step 1
                $stmt->bind_param("ii", $id, $step_corrente);
                if ($stmt->execute()) {
                    $processo_id = $stmt->insert_id;
                    $_SESSION['processo_id'] = $processo_id;
                } else {
                    die("Errore nell'inserimento del processo: " . $stmt->error);
                }
                $stmt->close();

                // Reindirizzamento alla pagina Step 2 o Dashboard
                header("Location: step2.php");
                exit;
            } else {
                // Password errata
                $error_message = "Nome utente o password non corretti.";
            }
        } else {
            // Nome utente non trovato
            $error_message = "Nome utente o password non corretti.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Agenti</title>
    <link rel="stylesheet" href="style.css"> <!-- Collegamento al file CSS -->
</head>
<body>
    <div class="container">
            <img src="logo.png" alt="Logo">
        <h2>Login Agenti</h2>

        <?php if (!empty($error_message)) { ?>
            <div class="error">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php } ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Nome utente:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Accedi</button>
        </form>
    </div>
</body>
</html>