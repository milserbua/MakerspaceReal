<?php
// admin_dashboard_view.php
session_start();

// SICHERHEITS-CHECK
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    echo "Zugriff verweigert! <a href='login.php'>Login</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makerspace Admin Zentrale</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- FontAwesome für coole Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-bg: #2c3e50;       /* Dunkles Blau/Grau für Sidebar */
            --sidebar-text: #ecf0f1;     /* Helle Schrift */
            --sidebar-hover: #34495e;    /* Hover Farbe Sidebar */
            --primary-accent: #4a90e2;   /* Deine Primärfarbe (Blau) */
            --bg-color: #f4f7f6;         /* Hintergrund Main */
            --text-color: #333;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            height: 100vh;      /* Volle Bildschirmhöhe */
            display: flex;      /* Flexbox für Sidebar-Layout */
            overflow: hidden;   /* Kein Scrollen auf dem Body selbst */
        }

        /* --- 1. SIDEBAR LINKS --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            padding: 25px 20px;
            background-color: #1a252f; /* Etwas dunklerer Header */
            font-size: 1.3rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-nav {
            flex: 1;
            padding-top: 20px;
            overflow-y: auto;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1rem;
            border-left: 4px solid transparent; /* Akzentlinie links */
        }

        .nav-link i {
            width: 30px; /* Breite für Icon reservieren */
            font-size: 1.1rem;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--sidebar-hover);
            color: #fff;
            border-left-color: var(--primary-accent); /* Blaue Linie bei Hover */
        }

        .sidebar-footer {
            padding: 20px;
            background-color: #1a252f;
            text-align: center;
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        /* --- 2. HAUPTBEREICH RECHTS --- */
        .main-content {
            flex: 1; /* Nimmt den restlichen Platz ein */
            display: flex;
            flex-direction: column;
            overflow-y: auto; /* Scrollen nur hier drinnen */
            position: relative;
        }

        /* Top Bar Header */
        .top-navbar {
            background-color: #fff;
            padding: 15px 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .user-info {
            font-weight: 500;
            color: #555;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-logout {
            color: #e74c3c;
            text-decoration: none;
            border: 1px solid #e74c3c;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .btn-logout:hover { background: #e74c3c; color: white; }

        /* Dashboard Grid Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
            width: 100%;
            box-sizing: border-box;
            flex: 1; /* Drückt Footer nach unten */
        }

        .header-text { text-align: center; margin-bottom: 50px; }
        .header-text h1 { font-weight: 300; font-size: 2.2rem; color: #2c3e50; margin: 0 0 10px 0; }
        .header-text p { color: #7f8c8d; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .dash-card {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #eee;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .dash-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: var(--primary-accent);
        }

        .icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary-accent);
        }

        .card-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; color: #2c3e50; }
        .card-desc { font-size: 0.9rem; color: #777; line-height: 1.5; }

        /* --- 3. FOOTER --- */
        .main-footer {
            background-color: #fff;
            border-top: 1px solid #eee;
            padding: 15px;
            text-align: center;
            color: #999;
            font-size: 0.85rem;
            margin-top: auto;
        }

    </style>
</head>
<body>

    <!-- SIDEBAR (Links) -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-tools"></i> Makerspace
        </div>
        
        <nav class="sidebar-nav">
            <!-- Menüpunkte -->
            <a href="admin_dashboard_view.php" class="nav-link active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <!-- WICHTIG: Hier dein Dateiname -->
            <a href="dashboard_admin_benutzer_view.php" class="nav-link">
                <i class="fas fa-users"></i> Benutzer
            </a>
            <a href="geraete_verwaltung.php" class="nav-link">
                <i class="fas fa-hammer"></i> Geräte
            </a>
           
            <a href="logs_view.php" class="nav-link">
                <i class="fas fa-shield-alt"></i> Logs
            </a>
        </nav>

        <div class="sidebar-footer">
            &copy; 2025 Admin Panel
        </div>
    </aside>

    <!-- MAIN CONTENT (Rechts) -->
    <main class="main-content">
        
        <!-- Navbar oben -->
        <nav class="top-navbar">
            <div style="color: #999; font-weight: 500;">Übersicht</div>
            <div class="user-info">
                <span>Hallo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </nav>

        <!-- Inhalt -->
        <div class="container">
            
            <div class="header-text">
                <h1>Admin Dashboard</h1>
                <p>Willkommen in der Verwaltung. Wähle einen Bereich aus.</p>
            </div>

            <div class="dashboard-grid">

                <!-- LINK 1: Benutzerverwaltung (Dein Dateiname) -->
                <a href="dashboard_admin_benutzer_view.php" class="dash-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="card-title">Benutzer verwalten</div>
                    <div class="card-desc">Benutzer erstellen, Mitgliederliste und deren Rollen ansehen</div>
                </a>

                <!-- LINK 2: Geräte -->
                <a href="geraete_verwaltung.php" class="dash-card">
                    <div class="icon"><i class="fas fa-hammer"></i></div> 
                    <div class="card-title">Geräte & Maschinen</div>
                    <div class="card-desc">Neue Maschinen erfassen, Bereiche und deren erforderliche Schulung ansehen</div>
                </a>


                <!-- LINK 3: Logs -->
                <a href="logs_view.php" class="dash-card">
                    <div class="icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="card-title">Sicherheit & Logs</div>
                    <div class="card-desc">Zugriffsprotokolle und Systemmeldungen überprüfen</div>
                </a>

            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            Makerspace Verwaltungssystem | Version 1.0
        </footer>

    </main>

</body>
</html>