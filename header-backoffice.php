<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style_backoffice.css">
    <title>Header Backoffice</title>
    <style>
        /* Stili per la navbar */
        .navbar {
            background-color: #000;
            overflow: hidden;
            text-align: center;
            padding: 20px 0;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            padding: 14px 20px;
            font-size: 18px;
            margin: 0 10px;
            cursor: pointer;
        }

        .navbar a:hover {
            background-color: #0056b3;
            border-radius: 5px;
        }

        .reparti {
            display: none;
            background-color: #000;
            padding: 20px 0;
        }

        .reparti a {
            color: #fff;
            text-decoration: none;
            align-content: center;
            padding: 14px 20px;
            font-size: 18px;
            margin: 0 10px;
            cursor: pointer;
        }

        .reparti a:hover {
            background-color: #0056b3;
            border-radius: 5px;
        }

    </style>
</head>
<body>
    <header>
        <div class="navbar" id="mainMenu">
            <a href="backoffice.php">Home</a>
            <a href="backoffice-agenti.php">Gestisci Agenti</a>
            <a href="javascript:void(0)" id="gestioneReparti">Gestione Reparti</a>
            <a href="backoffice.php?logout=true">Logout</a>
        </div>

        <div class="reparti" id="repartiMenu">
            <a href="backoffice-luceegas.php">Luce e Gas</a>
            <a href="backoffice-telefonia.php">Telefonia e Fibra</a>
            <a href="backoffice-green.php">Green</a>
            <a href="backoffice-tv.php">TV</a>
        </div>
    </header>

    <script>
        // Ottieni gli elementi
        const gestioneReparti = document.getElementById('gestioneReparti');
        const mainMenu = document.getElementById('mainMenu');
        const repartiMenu = document.getElementById('repartiMenu');

        // Aggiungi un listener per il passaggio del mouse
        gestioneReparti.addEventListener('mouseover', function() {
            // Nascondi il menu principale
            mainMenu.style.display = 'none';
            // Mostra il menu reparti
            repartiMenu.style.display = 'block';
        });

        // Aggiungi un listener per il mouse out dal menu reparti
        repartiMenu.addEventListener('mouseleave', function() {
            // Mostra di nuovo il menu principale
            mainMenu.style.display = 'block';
            // Nascondi il menu reparti
            repartiMenu.style.display = 'none';
        });
    </script>
</body>
</html>