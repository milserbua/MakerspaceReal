<?php
// impressum.php
session_start();

// SICHERHEITS-CHECK
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

// PRÜFEN: Ist der User ein Admin?
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin');
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressum</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #4a90e2;    
            --primary-hover: #357abd;
            --sidebar-bg: #2c3e50;       
            --sidebar-hover: #34495e;
            --bg-color: #f4f7f6;         
            --text-color: #333;
            --text-muted: #7f8c8d;
            --card-shadow: 0 4px 6px rgba(0,0,0,0.05);
            --border-radius: 12px;       
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0; padding: 0; background-color: var(--bg-color); color: var(--text-color);
            height: 100vh; display: flex; overflow: hidden;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 260px; background-color: var(--sidebar-bg); color: #ecf0f1;
            display: flex; flex-direction: column; justify-content: space-between;
            flex-shrink: 0; box-shadow: 4px 0 15px rgba(0,0,0,0.05); z-index: 1000; height: 100vh;
        }

        .sidebar-content-top { display: flex; flex-direction: column; flex: 1; overflow-y: auto; }

        .sidebar-brand { 
            min-height: 70px; display: flex; align-items: center; padding: 0 25px; 
            font-size: 1.4rem; font-weight: 700; text-transform: uppercase; 
            border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; gap: 12px; 
        }
        .sidebar-brand i { color: var(--primary-color); }

        .sidebar-nav { padding: 20px 10px; }

        .sidebar-bottom { 
            padding: 15px 10px; border-top: 1px solid rgba(255,255,255,0.05); background-color: rgba(0,0,0,0.1); 
        }

        .nav-link { 
            display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; 
            text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; 
            border-radius: 8px; margin-bottom: 5px; 
        }
        .nav-link:hover, .nav-link.active { 
            background-color: var(--sidebar-hover); color: #fff; transform: translateX(5px); 
        }
        .nav-link i { width: 35px; font-size: 1.1rem; transition: color 0.3s; }
        .nav-link:hover i, .nav-link.active i { color: var(--primary-color); }

        /* --- MAIN CONTENT --- */
        .main-content {
            flex: 1; display: flex; flex-direction: column; overflow-y: auto;
        }

        .top-navbar {
            height: 70px; background-color: #fff; padding: 0 40px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); flex-shrink: 0;
        }

        .page-title { color: var(--text-muted); font-weight: 500; font-size: 0.95rem; }

        .user-info { display: flex; align-items: center; gap: 20px; }

        .btn-logout {
            color: #e74c3c; text-decoration: none; border: 1px solid rgba(231, 76, 60, 0.3);
            padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;
            transition: all 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-logout:hover { background: #e74c3c; color: white; }

        /* --- CONTENT CONTAINER --- */
        .container {
            max-width: 900px; margin: 0 auto; padding: 40px; width: 100%; box-sizing: border-box; flex: 1;
        }

        .card {
            background: #fff;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        h2 { 
            margin-top: 0; color: #2c3e50; border-bottom: 2px solid #f4f7f6; 
            padding-bottom: 10px; margin-bottom: 20px; font-weight: 300; 
        }
        
        h3 { font-size: 1.1rem; margin-top: 25px; color: #444; }
        
        p, li { line-height: 1.6; color: #555; }

        .main-footer {
            background-color: #fff; border-top: 1px solid #eee; padding: 20px;
            text-align: center; color: #aaa; font-size: 0.8rem; margin-top: auto;
        }
        .credits .name { font-weight: 500; color: #999; }

    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-content-top">
            <div class="sidebar-brand">
                <i class="fas fa-tools"></i> MAKERSPACE
            </div>
            
            <nav class="sidebar-nav">
                
                <?php if ($isAdmin): ?>
                    <!-- ========================== -->
                    <!-- ADMIN NAVIGATION           -->
                    <!-- ========================== -->
                    <a href="dashboard_admin_view.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="dashboard_admin_benutzer_view.php" class="nav-link">
                        <i class="fas fa-users"></i> Benutzer
                    </a>
                    <a href="geraete_verwaltung.php" class="nav-link">
                        <i class="fas fa-hammer"></i> Geräte
                    </a>
                    <a href="logs_view.php" class="nav-link">
                        <i class="fas fa-shield-alt"></i> Logs
                    </a>

                <?php else: ?>
                    <!-- ========================== -->
                    <!-- MITGLIED NAVIGATION        -->
                    <!-- ========================== -->
                    <a href="dashboard_benutzer_view.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="geraete_uebersicht.php" class="nav-link">
                        <i class="fas fa-hammer"></i> Geräte
                    </a>
                    <a href="profil.php" class="nav-link">
                        <i class="fas fa-user-circle"></i> Mein Profil
                    </a>
                    <a href="hilfe.php" class="nav-link">
                        <i class="fas fa-question-circle"></i> Hilfe
                    </a>
                <?php endif; ?>

            </nav>
        </div>

        <div class="sidebar-bottom">
            <a href="impressum.php" class="nav-link active">
                <i class="fas fa-scale-balanced"></i> Impressum
            </a>
        </div>
    </aside>

    <main class="main-content">
        
        <nav class="top-navbar">
            <div class="page-title">Rechtliches &rsaquo; Impressum</div>
            <div class="user-info">
                <span>Hallo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Abmelden
                </a>
            </div>
        </nav>

        <div class="container">
            
            <div class="card">
                <h2>Impressum</h2>
                
                <h3>Angaben gemäß § 5 ECG</h3>
                <p>
                    <strong>Makerspace HTL Anichstraße</strong><br>
                    Anichstraße 26-28<br>
                    6020 Innsbruck<br>
                    Österreich
                </p>

                <h3>Kontakt</h3>
                <p>
                    Telefon: +43 123 456789<br>
                    E-Mail: info@makerspace-tirol.at
                </p>

                <h3>Verantwortlich für den Inhalt</h3>
                <p>
                    Karin Gratzel 
                    <br>Leon Delmarco  
                    <br>Clemens Eismayr  
                    <br>Koray Erbas 
                    <br>Noah Baumann<br>
                </p>

                <h3>Haftungsausschluss</h3>
                <p>
                    Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für die Inhalte externer Links. 
                    Für den Inhalt der verlinkten Seiten sind ausschließlich deren Betreiber verantwortlich.
                </p>
            </div>

        </div>

        <footer class="main-footer">
            &copy; 2025 Makerspace<br>
        </footer>

    </main>

</body>
</html>