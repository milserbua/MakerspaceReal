<?php
// admin_dashboard_view.php
session_start();

// SICHERHEITS-CHECK (Unverändert)
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
    
    <!-- Gleiche Schriftarten wie Login -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* Farbpalette passend zur Login-View */
            --primary-color: #4a90e2;    /* Das Dashboard-Blau */
            --primary-hover: #357abd;
            --sidebar-bg: #2c3e50;       /* Dunkler Kontrast für Sidebar */
            --sidebar-hover: #34495e;
            --bg-color: #f4f7f6;         /* Heller Hintergrund Main */
            --text-color: #333;
            --text-muted: #7f8c8d;
            --card-shadow: 0 10px 25px rgba(0,0,0,0.05); /* Weicherer Schatten */
            --border-radius: 12px;       /* Gleiche Rundung wie Login */
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* --- 1. SIDEBAR (LINKS) --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
            z-index: 10;
        }

        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 25px;
            font-size: 1.4rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #fff;
            gap: 12px;
        }
        
        .sidebar-brand i { color: var(--primary-color); }

        .sidebar-nav {
            flex: 1;
            padding: 20px 10px;
            overflow-y: auto;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            border-radius: 8px; /* Moderne, abgerundete Links */
            margin-bottom: 5px;
        }

        .nav-link i {
            width: 35px;
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--sidebar-hover);
            color: #fff;
            transform: translateX(5px); /* Leichter Bewegungseffekt */
        }
        
        .nav-link:hover i, .nav-link.active i {
            color: var(--primary-color);
        }

        /* --- 2. HAUPTBEREICH (RECHTS) --- */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            position: relative;
        }

        /* Top Navbar */
        .top-navbar {
            height: 70px;
            background-color: #fff;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            flex-shrink: 0;
        }

        .page-title {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-badge {
            font-weight: 500;
            color: #555;
        }

        .btn-logout {
            color: #e74c3c;
            text-decoration: none;
            border: 1px solid rgba(231, 76, 60, 0.3);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-logout:hover { 
            background: #e74c3c; 
            color: white; 
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
        }

        /* Dashboard Container */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 50px 30px;
            width: 100%;
            box-sizing: border-box;
            flex: 1;
        }

        .header-text { text-align: center; margin-bottom: 50px; }
        .header-text h1 { 
            font-weight: 300; 
            font-size: 2.5rem; 
            color: #2c3e50; 
            margin: 0 0 10px 0; 
        }
        .header-text p { color: var(--text-muted); font-size: 1.05rem; }

        /* Das Grid für die Karten */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        /* Die Karten - Passend zum Login Design */
        .dash-card {
            background: #fff;
            padding: 40px 30px;
            border-radius: var(--border-radius);
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
            border: 1px solid transparent;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%; /* Gleich hoch */
        }

        .dash-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: rgba(74, 144, 226, 0.2);
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            background: #f0f7ff; /* Sehr helles Blau */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .dash-card:hover .icon-circle {
            background: #e1f0ff;
        }

        .icon {
            font-size: 2.5rem;
            color: var(--primary-color);
        }

        .card-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 10px; color: #2c3e50; }
        .card-desc { font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; }

        /* --- 3. FOOTER --- */
        .main-footer {
            background-color: #fff;
            border-top: 1px solid #eee;
            padding: 20px;
            text-align: center;
            color: #aaa;
            font-size: 0.8rem;
            margin-top: auto;
        }
        
        .credits .name { font-weight: 500; color: #999; }

    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-tools"></i> Makerspace
        </div>
        
        <nav class="sidebar-nav">
            <!-- Active Class hier setzen -->
            <a href="admin_dashboard_view.php" class="nav-link active">
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
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="page-title">Admin Dashboard &rsaquo; Übersicht</div>
            <div class="user-info">
                <span class="user-badge">Hallo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Abmelden
                </a>
            </div>
        </nav>

        <!-- Dashboard Inhalt -->
        <div class="container">
            
            <div class="header-text">
                <h1>Admin Dashboard</h1>
                <p>Verwaltung der Ressourcen, Mitglieder und Sicherheit.</p>
            </div>

            <div class="dashboard-grid">

                <!-- Kachel 1: Benutzer -->
                <a href="dashboard_admin_benutzer_view.php" class="dash-card">
                    <div class="icon-circle">
                        <i class="fas fa-users icon"></i>
                    </div>
                    <div class="card-title">Benutzer verwalten</div>
                    <div class="card-desc">Mitgliederliste einsehen, neue Benutzer anlegen und Rollen (Admin/Mitglied) zuweisen.</div>
                </a>

                <!-- Kachel 2: Geräte -->
                <a href="geraete_verwaltung.php" class="dash-card">
                    <div class="icon-circle">
                        <i class="fas fa-hammer icon"></i>
                    </div> 
                    <div class="card-title">Geräte & Maschinen</div>
                    <div class="card-desc">Inventar pflegen, Wartungsstatus prüfen und neue Maschinen in das System aufnehmen.</div>
                </a>

                <!-- Kachel 3: Logs -->
                <a href="logs_view.php" class="dash-card">
                    <div class="icon-circle">
                        <i class="fas fa-shield-alt icon"></i>
                    </div>
                    <div class="card-title">Sicherheit & Logs</div>
                    <div class="card-desc">Systemaktivitäten überwachen, Zugriffsprotokolle und Fehlermeldungen analysieren.</div>
                </a>

            </div>
        </div>

        <!-- Footer (Passend zur Login View) -->
        <footer class="main-footer">
            &copy; 2025 Makerspace Verwaltung<br>
            <span class="credits">Dev: <span class="name">Clemens</span> & <span class="name">Karin</span></span>
        </footer>

    </main>

</body>
</html>