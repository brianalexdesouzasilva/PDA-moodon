<?php
// Credenziali fittizie
$valid_username = 'Brian';
$valid_password = 'prova';

// Ottieni i dati dal form
$username = $_POST['username'];
$password = $_POST['password'];

// Verifica le credenziali
if ($username === $valid_username && $password === $valid_password) {
    // Login riuscito, reindirizza alla pagina principale (dashboard o home)
    header("Location: dashboard.php");
    exit();
} else {
    // Login fallito, reindirizza al form di login con errore
    header("Location: login.php?error=1");
    exit();
}
?>