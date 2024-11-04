<?php
// Includi il file di connessione al database
require_once 'db_connection.php';

// Query di test
$sql = "SELECT * FROM partner"; // Puoi cambiare la tabella se vuoi testare un'altra

// Esegui la query
$result = $conn->query($sql);

// Verifica se la query ha restituito dei risultati
if ($result) {
    if ($result->num_rows > 0) {
        echo "<h3>Connessione al database riuscita. Dati trovati nella tabella 'partner':</h3>";
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . " - Nome Partner: " . $row['nome_partner'] . " - Categoria: " . $row['categoria'] . "<br>";
        }
    } else {
        echo "<h3>Connessione riuscita ma nessun dato trovato nella tabella 'partner'.</h3>";
    }
} else {
    echo "<h3>Errore nella query: " . $conn->error . "</h3>";
}

// Chiudi la connessione
$conn->close();
?>