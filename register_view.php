<!-- register_view.php -->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrierung Makerspace</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
    </style>
</head>
<body>

    <h1>Willkommen im Makerspace</h1>
    <h2>Registrierung</h2>

    <!-- HIER binden wir jetzt einfach den Baustein ein -->
    <!-- PHP kopiert den Inhalt von feedback.php automatisch hier rein -->
    <?php include 'message.php'; ?>

    <form action="register.php" method="POST">
        <label>Benutzername:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Passwort:</label><br>
        <input type="password" name="password" required><br><br>

        <input type="submit" name="register_btn" value="Konto erstellen">
    </form>

</body>
</html>