<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login Makerspace</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
    </style>
</head>
<body>
 
    <h1>Login</h1>
    <p>Bitte melde dich an, um den Makerspace zu betreten.</p>
 
 
    <form action="login.php" method="POST">
        <label>Benutzername:</label><br>
        <input type="text" name="username" required><br><br>
 
        <label>Passwort:</label><br>
        <input type="password" name="password" required><br><br>
 
        <input type="submit" name="login_btn" value="Einloggen">
    </form>
   
    <br>
   
 
</body>
</html>