<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login Makerspace</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f2f2f2;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
            text-align: center;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"] {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background-color: steelblue;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #003f7f;
        }
        .error-box {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ef9a9a;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <p>Bitte melde dich an, um den Makerspace zu betreten.</p>

        <?php if (!empty($nachricht)): ?>
            <div class="error-box">
                <?php echo htmlspecialchars($nachricht); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">Benutzername</label><br>
            <input type="text" id="username" name="username" required><br>

            <label for="password">Passwort</label><br>
            <input type="password" id="password" name="password" required><br>

            <input type="submit" name="login_btn" value="Einloggen">
        </form>
    </div>
</body>
</html>
