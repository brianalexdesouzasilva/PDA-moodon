<?php

// Includi la connessione al database
require_once 'db_connection.php';

if (isset($_POST['partner_id'])) {
    $partner_id = $_POST['partner_id'];

    // Recupera le offerte associate al partner selezionato
    $sql_offerte = "SELECT id, nome_offerta FROM offerte WHERE partner_id = ?";
    $stmt = $conn->prepare($sql_offerte);
    $stmt->bind_param("i", $partner_id);
    $stmt->execute();
    $result_offerte = $stmt->get_result();

    if ($result_offerte->num_rows > 0) {
        while ($row = $result_offerte->fetch_assoc()) {
            echo "<option value='" . $row['id'] . "'>" . $row['nome_offerta'] . "</option>";
        }
    } else {
        echo "<option value=''>Nessuna offerta disponibile</option>";
    }
    $stmt->close();
}
?>