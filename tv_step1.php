<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header("Location: login.php");
    exit;
}

$host = '89.46.111.220';
$db = 'Sql1784170_4';
$user = 'Sql1784170';
$pass = 'kv81o11835';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT partner, tipo FROM tv";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV - Step 1</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo.png" alt="Logo">
        </div>
        <h2>TV - Step 1</h2>
        <form action="tv_step2.php" method="POST">
            <label for="partner">Seleziona il Partner:</label>
            <select id="partner" name="partner" required>
                <option value="" disabled selected>Seleziona un partner</option>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['partner'] . "'>" . ucfirst($row['partner']) . " (" . $row['tipo'] . ")</option>";
                    }
                } else {
                    echo "<option value='' disabled>Nessun partner disponibile</option>";
                }
                ?>
            </select>
            <button type="button" onclick="goBack()">Indietro</button>
            <button type="submit">Avanti</button>
        </form>
    </div>
    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>