<?php
$servername = "89.46.111.220";
$username = "Sql1784170";
$password = "Marti1992!";
$dbname = "Sql1784170_4";

// Crea la connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
?>