<?php
session_start();

// Controlla se l'utente è autenticato per il backoffice
if (!isset($_SESSION['backoffice_user'])) {
    header("Location: backoffice_login.php");
    exit;
}

// Includi la connessione al database
require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Backoffice - Home</title>
    <link rel="stylesheet" href="style_backoffice.css">
    <style>
        .container {
            text-align: center;
            margin-top: 50px;
        }
        .category-button {
            display: inline-block;
            width: 200px;
            height: 200px;
            margin: 20px;
            text-align: center;
            vertical-align: middle;
            line-height: 200px;
            font-size: 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 10px;
        }
        .category-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <?php include 'header-backoffice.php'; ?>
    <div class="container">
        <h1>Backoffice - Gestione Categorie</h1>
        <a href="backoffice-luceegas.php" class="category-button">Luce e Gas</a>
        <a href="backoffice-telefonia.php" class="category-button">Telefonia e Fibra</a>
        <a href="backoffice-green.php" class="category-button">Green</a>
        <a href="backoffice-tv.php" class="category-button">TV</a>
    </div>
</body>
</html>