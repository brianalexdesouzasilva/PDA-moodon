<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caricamento Documento</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo.png" alt="Logo">
        </div>
        <h2>Caricamento Documento</h2>
        <form action="process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="partner" value="<?php echo htmlspecialchars($_POST['partner']); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($_POST['category']); ?>">
            <input type="hidden" name="nome" value="<?php echo htmlspecialchars($_POST['nome']); ?>">
            <input type="hidden" name="cognome" value="<?php echo htmlspecialchars($_POST['cognome']); ?>">
            <input type="hidden" name="data_nascita" value="<?php echo htmlspecialchars($_POST['data_nascita']); ?>">
            <input type="hidden" name="cf_piva" value="<?php echo htmlspecialchars($_POST['cf_piva']); ?>">
            <input type="hidden" name="luogo_nascita" value="<?php echo htmlspecialchars($_POST['luogo_nascita']); ?>">
            <input type="hidden" name="indirizzo_residenza" value="<?php echo htmlspecialchars($_POST['indirizzo_residenza']); ?>">
            <input type="hidden" name="comune" value="<?php echo htmlspecialchars($_POST['comune']); ?>">
            <input type="hidden" name="provincia" value="<?php echo htmlspecialchars($_POST['provincia']); ?>">
            <input type="hidden" name="cap" value="<?php echo htmlspecialchars($_POST['cap']); ?>">
            <input type="hidden" name="telefono" value="<?php echo htmlspecialchars($_POST['telefono']); ?>">
            <input type="hidden" name="cellulare" value="<?php echo htmlspecialchars($_POST['cellulare']); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email']); ?>">

            <div class="form-group">
                <label for="tipo_documento">Tipo Documento:</label>
                <input type="text" id="tipo_documento" name="tipo_documento" required>
            </div>
            <div class="form-group">
                <label for="emesso_da">Emesso da:</label>
                <input type="text" id="emesso_da" name="emesso_da" required>
            </div>
            <div class="form-group">
                <label for="data_emissione">Data Emissione:</label>
                <input type="date" id="data_emissione" name="data_emissione" required>
            </div>
            <div class="form-group">
                <label for="documento_fronte">Documento Fronte:</label>
                <input type="file" id="documento_fronte" name="documento_fronte" accept="image/*" required>
            </div>
            <div class="form-group">
                <label for="documento_retro">Documento Retro:</label>
                <input type="file" id="documento_retro" name="documento_retro" accept="image/*" required>
            </div>
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