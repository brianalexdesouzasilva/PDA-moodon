<?php
// Includi in ogni pagina dove necessario
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">
            <a href="step2.php"><img src="logo.png" alt="Logo"></a>
        </div>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </header>
</body>
</html>