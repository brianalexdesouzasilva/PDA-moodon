<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green - Step 3</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .column {
            flex: 0 0 48%;
            box-sizing: border-box;
        }

        .column.full-width {
            flex: 0 0 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo.png" alt="Logo">
        </div>
        <h2>Green - Step 3</h2>
        <form action="step4.php" method="POST">
            <input type="hidden" name="partner" value="<?php echo htmlspecialchars($_POST['partner']); ?>">
            <input type="hidden" name="category" value="luceegas">
            <div class="row">
                <div class="column">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="column">
                    <label for="cognome">Cognome:</label>
                    <input type="text" id="cognome" name="cognome" required>
                </div>
                <div class="column">
                    <label for="data_nascita">Data di Nascita:</label>
                    <input type="date" id="data_nascita" name="data_nascita" required>
                </div>
                <div class="column">
                    <label for="cf_piva">Codice Fiscale:</label>
                    <input type="text" id="cf_piva" name="cf_piva" required>
                </div>
                <div class="column">
                    <label for="luogo_nascita">Luogo di Nascita:</label>
                    <input type="text" id="luogo_nascita" name="luogo_nascita" required>
                </div>
                <div class="column">
                    <label for="indirizzo_residenza">Indirizzo:</label>
                    <input type="text" id="indirizzo_residenza" name="indirizzo_residenza" required>
                </div>
                <div class="column">
                    <label for="comune">Comune:</label>
                    <input type="text" id="comune" name="comune" required>
                </div>
                <div class="column">
                    <label for="provincia">Provincia:</label>
                    <input type="text" id="provincia" name="provincia" required>
                </div>
                <div class="column">
                    <label for="cap">CAP:</label>
                    <input type="text" id="cap" name="cap" required pattern="\d*">
                </div>
                <div class="column">
                    <label for="telefono">Telefono:</label>
                    <input type="text" id="telefono" name="telefono" pattern="\d*">
                </div>
                <div class="column">
                    <label for="cellulare">Cellulare:</label>
                    <input type="text" id="cellulare" name="cellulare" required pattern="\d*">
                </div>
                <div class="column">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="column full-width">
                    <button type="button" onclick="goBack()">Indietro</button>
                    <button type="submit">Avanti</button>
                </div>
            </div>
        </form>
    </div>
    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>
