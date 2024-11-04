<?php
session_start();

// Controlla se l'utente è autenticato
if (!isset($_SESSION['access_token'])) {
    header("Location: login.php"); // Se non è autenticato, reindirizza alla pagina di login
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV - Step 2</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h2>TV - Step 2</h2>
        <form action="tv_step3.php" method="post">
            <?php
            foreach ($_POST as $key => $value) {
                echo "<input type='hidden' name='$key' value='$value'>";
            }
            ?>
            <label for="install_option">Opzioni Installazione:</label>
            <select id="install_option" name="install_option" required>
                <option value="self_install">Installazione Fai-da-te</option>
                <option value="pro_install">Installazione Professionale</option>
            </select>
            <button type="submit">Avanti</button>
        </form>
    </div>
</body>
</html>