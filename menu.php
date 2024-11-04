<?php
// Assicurati che la sessione sia avviata
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se l'agente è autenticato
if (!isset($_SESSION['agente_id'])) {
    // Se non è autenticato, non mostrare il menu
    return;
}

// Recupera le informazioni dell'agente dal database (es. nome, avatar)
require_once 'db_connection.php';
$agente_id = $_SESSION['agente_id'];

$sql = "SELECT nome, cognome FROM agenti WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $agente_id);
    $stmt->execute();
    $stmt->bind_result($nome, $cognome);
    $stmt->fetch();
    $stmt->close();
} else {
    $nome = "Agente";
    $cognome = "";
}

// Percorso all'avatar (modifica in base alla tua struttura)
$avatar_url = "avatar.png"; // Usa un'immagine di default o personalizzata
?>
<div class="header">
    <div class="logo">
            <img src="logo.png" alt="Logo">
    </div>
    <div class="menu">
        <ul>
            <li> <a href="logout.php">Logout</a></li>
            <li><a href="step2.php">Nuova pratica</a></li>
            <li><a href="http://cloud.speedycrm.com" target="_blank">Gestionale</a></li>
            <a href="gestione_clienti.php">Gestione Clienti/contratti</a>
        </ul>
    </div>
    <div class="profile">
        <a href="profilo.php">
            <span><?php echo htmlspecialchars($nome . ' ' . $cognome); ?></span>
            <img src="<?php echo $avatar_url; ?>" alt="Profilo">
        </a>
    </div>
    <!-- Hamburger menu per dispositivi mobili -->
    <div class="hamburger" onclick="openNav()">
        <div></div>
        <div></div>
        <div></div>
    </div>
</div>

<!-- Side Navigation Menu per mobile -->
<div id="mySidenav" class="sidenav">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="step2.php">Nuova pratica</a>
    <a href="gestione_clienti.php">Gestione Clienti/contratti</a>
    <a href="profilo.php">Profilo</a>
    <a href="logout.php">Logout</a>
    <!-- Aggiungi altri link se necessario -->
</div>