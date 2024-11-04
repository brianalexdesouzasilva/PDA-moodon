<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luce e Gas - Step 1</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo.png" alt="Logo">
        </div>
        <h2>Green - Step 1</h2>
        <form action="green-step3.php" method="POST">
            <label for="partner">Partner:</label>
            <select id="partner" name="partner" required>
                <option value="" disabled selected>Seleziona un partner</option>
                <option value="hera">Hera</option>
                <!-- Aggiungi altre opzioni qui -->
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
