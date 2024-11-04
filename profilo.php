<?php
session_start();
require_once 'db_connection.php';

// Abilita la visualizzazione degli errori per il debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se l'agente è autenticato
if (!isset($_SESSION['agente_id'])) {
    header("Location: login.php");
    exit();
}

$agente_id = $_SESSION['agente_id'];
$success_message = '';
$error_message = '';

// Recupera i dati dell'agente dal database
$sql = "SELECT nome, cognome, email FROM agenti WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("i", $agente_id);
$stmt->execute();
$result = $stmt->get_result();
$agente = $result->fetch_assoc();
$stmt->close();

if (!$agente) {
    die("Dati dell'agente non trovati.");
}

// Gestione della modifica della password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_attuale'], $_POST['password_nuova'], $_POST['password_conferma'])) {
    $password_attuale = $_POST['password_attuale'];
    $password_nuova = $_POST['password_nuova'];
    $password_conferma = $_POST['password_conferma'];

    // Verifica se le nuove password corrispondono
    if ($password_nuova !== $password_conferma) {
        $error_message = "Le nuove password non corrispondono.";
    } else {
        // Verifica la password attuale
        $sql = "SELECT password FROM agenti WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $agente_id);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($password_attuale, $password_hash)) {
            $error_message = "La password attuale non è corretta.";
        } else {
            // Aggiorna la password nel database
            $password_hash_nuova = password_hash($password_nuova, PASSWORD_DEFAULT);
            $sql = "UPDATE agenti SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Errore nella preparazione della query di aggiornamento: " . $conn->error);
            }
            $stmt->bind_param("si", $password_hash_nuova, $agente_id);
            if ($stmt->execute()) {
                $success_message = "Password modificata con successo.";
            } else {
                $error_message = "Errore durante la modifica della password.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Agente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container">
        <h2>Profilo</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h3>Dati Personali</h3>
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($agente['nome']); ?></p>
        <p><strong>Cognome:</strong> <?php echo htmlspecialchars($agente['cognome']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($agente['email']); ?></p>

        <h3>Cambia Password</h3>
        <form action="profilo.php" method="POST">
            <label for="password_attuale">Password attuale:</label>
            <input type="password" id="password_attuale" name="password_attuale" required>

            <label for="password_nuova">Nuova Password:</label>
            <input type="password" id="password_nuova" name="password_nuova" required>

            <label for="password_conferma">Conferma Nuova Password:</label>
            <input type="password" id="password_conferma" name="password_conferma" required>

            <button type="submit">Modifica Password</button>
        </form>
    </div>

    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f4f4f4;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .alert-success {
            background-color: #4CAF50;
            color: white;
        }
        .alert-danger {
            background-color: #f44336;
            color: white;
        }
    </style>
</body>
</html>