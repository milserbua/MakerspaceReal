<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makerspace Login</title>
    
    <!-- Schriftarten & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #4a90e2; 
            --primary-hover: #357abd;
            --bg-color: #f4f7f6;
            --text-color: #333;
            --error-bg: #fee2e2;
            --error-text: #dc2626;
            --card-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--bg-color);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text-color);
        }

        /* Haupt-Container (Karte) */
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 360px;
            text-align: center;
            position: relative;
            transition: transform 0.3s ease;
        }

        .container:hover { transform: translateY(-5px); }

        /* --- LOGO BEREICH (Angepasst) --- */
        .logo-container {
            margin-bottom: 10px; /* Weniger Abstand nach unten */
            padding-bottom: 0;   /* Kein Padding unten */
            border-bottom: none; /* WICHTIG: Kein Strich/Schatten mehr */
        }

        .htl-logo {
            display: block;
            margin: 0 auto;
            width: 100%;      /* Nimmt sich den Platz */
            max-width: 260px; /* WICHTIG: Logo deutlich größer */
            height: auto;
            transition: opacity 0.3s;
        }
        
        .htl-logo:hover {
            opacity: 0.85;
        }

        /* Header Bereich */
        .icon-header { font-size: 2rem; color: var(--primary-color); margin-bottom: 5px; margin-top: 5px; }
        h1 { margin: 0 0 5px 0; font-weight: 300; color: #2c3e50; font-size: 1.6rem; }
        p { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 25px; }
        
        /* Formular Styling */
        form { text-align: left; }
        label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.85rem; color: #555; }
        
        .input-wrapper { position: relative; margin-bottom: 15px; }
        .input-wrapper i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 0.9rem; }
        
        input[type="text"], input[type="password"] {
            width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 0.95rem; box-sizing: border-box; transition: all 0.3s; outline: none; background: #fafafa;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--primary-color); background: #fff; box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        button[type="submit"] {
            margin-top: 10px; width: 100%; padding: 12px; background-color: var(--primary-color);
            color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem;
            font-weight: 600; transition: background 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px;
            box-shadow: 0 4px 6px rgba(74, 144, 226, 0.2);
        }
        button[type="submit"]:hover { background-color: var(--primary-hover); transform: translateY(-1px); }

        /* Fehlermeldung */
        .error-box {
            background-color: var(--error-bg); color: var(--error-text); padding: 10px; border-radius: 6px;
            font-size: 0.85rem; margin-bottom: 20px; border: 1px solid #fca5a5; display: flex; align-items: center; justify-content: center; gap: 8px;
        }

        /* Footer */
        .footer-text { margin-top: 30px; font-size: 0.75rem; color: #bbb; line-height: 1.6; }
        .credits .name { font-weight: 500; color: #999; }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- HTL LOGO -->
        <div class="logo-container">
            <a href="https://www.htl.tirol/standorte/htl-anichstrasse" target="_blank">
                <img src="https://www.htl.tirol/fileadmin/_processed_/7/1/csm_Logo_HTL_Anichstrasse_cab5e6307c.png" alt="HTL Anichstraße" class="htl-logo">
            </a>
        </div>

        <!-- Titel Bereich -->
        <div class="icon-header">
            <i class="fas fa-tools"></i>
        </div>
        <h1>Login</h1>
        <p>Bitte anmelden</p>

        <!-- Fehlermeldung -->
        <?php if (!empty($nachricht)): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($nachricht); ?>
            </div>
        <?php endif; ?>

        <!-- Formular -->
        <form action="login.php" method="POST" autocomplete="off" id="loginForm">
            
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Benutzername" required autofocus autocomplete="off">
            </div>

            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Passwort" required autocomplete="new-password">
            </div>

            <button type="submit" name="login_btn">
                Einloggen <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <!-- Footer -->
        <div class="footer-text">
            &copy; 2025 Makerspace Verwaltung<br>
            <span class="credits">Dev: <span class="name">Clemens Eismayr</span>
        </div>
    </div>

    <!-- Script zum Leeren der Felder beim Zurück-Klicken -->
    <script>
        window.addEventListener('pageshow', function(event) {
            var historyTraversal = event.persisted || ( typeof window.performance != "undefined" && window.performance.navigation.type === 2 );
            if (historyTraversal) {
                document.getElementById("loginForm").reset();
            }
        });
    </script>

</body>
</html>