<?php
session_start();

// Messaggio di errore iniziale
$error_message = "";

// Autenticazione fittizia
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Controllo delle credenziali fittizie
    if ($username === 'backoffice' && $password === 'Backoffice_partner!2024') {
        $_SESSION['backoffice_user'] = $username; // Imposta la sessione
        header("Location: backoffice.php"); // Reindirizza alla pagina del backoffice
        exit;
    } else {
        $error_message = "Credenziali non valide!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Backoffice</title>
    <link rel="stylesheet" href="style_backoffice.css">
</head>
<body>
    <div class="container">
        <h1>Login Backoffice</h1>

        <?php if (!empty($error_message)) { ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php } ?>

        <form action="backoffice_login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>