<?php
// logs_view.php
session_start();
require 'db.php';

// 1. SICHERHEITS-CHECK: Nur Admins
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// 2. DATEN LADEN: Wer hat sich wann angemeldet? (Unverändert)
$sql = "SELECT l.*, b.Vorname, b.Nachname, b.Username 
        FROM SystemLogs l
        JOIN Werkstattbenutzer b ON l.WerkBenutzerID = b.WerkBenutzerID
        ORDER BY l.Zeitpunkt DESC";
$stmt = $pdo->query($sql);
$alle_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs</title>
    
    <!-- Fonts & Icons -->
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
            --border-color: #e9ecef;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* --- SIDEBAR --- */
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
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #fff;
            gap: 12px;
        }
        .sidebar-brand i { color: var(--primary-color); }

        .sidebar-nav { flex: 1; padding: 20px 10px; overflow-y: auto; }

        .nav-link {
            display: flex; align-items: center; padding: 12px 20px;
            color: #bdc3c7; text-decoration: none; transition: all 0.3s ease;
            font-size: 0.95rem; border-radius: 8px; margin-bottom: 5px;
        }
        .nav-link i { width: 35px; font-size: 1.1rem; }
        .nav-link:hover, .nav-link.active {
            background-color: var(--sidebar-hover); color: #fff; transform: translateX(5px);
        }
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
        
        .page-title { color: var(--text-muted); font-weight: 500; }
        
        .user-info { display: flex; align-items: center; gap: 20px; }
        .btn-logout {
            color: #e74c3c; text-decoration: none; border: 1px solid rgba(231, 76, 60, 0.3);
            padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;
            transition: all 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-logout:hover { background: #e74c3c; color: white; }

        /* --- CONTENT CONTAINER --- */
        .container {
            max-width: 1200px; margin: 0 auto; padding: 40px; width: 100%; box-sizing: border-box; flex: 1;
        }

        .header-text { margin-bottom: 30px; }
        .header-text h1 { margin: 0 0 10px 0; font-weight: 300; color: #2c3e50; }
        .header-text p { color: var(--text-muted); }

        /* CARD */
        .card {
            background: #fff; border-radius: var(--border-radius);
            box-shadow: var(--card-shadow); margin-bottom: 30px; overflow: hidden;
        }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th {
            background: #f8f9fa; color: #6c757d; font-size: 0.8rem; text-transform: uppercase;
            padding: 15px 20px; font-weight: 700; border-bottom: 2px solid var(--border-color);
        }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fafafa; }

        /* LOG SPECIFIC STYLES */
        .timestamp { 
            font-family: 'Courier New', monospace; 
            color: #666; 
            font-size: 0.9rem;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .badge-log { 
            background: #e0f2fe; 
            color: #0284c7; 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* FOOTER */
        .main-footer {
            background-color: #fff; border-top: 1px solid #eee; padding: 20px;
            text-align: center; color: #aaa; font-size: 0.8rem; margin-top: auto;
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
            <a href="dashboard_admin_view.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="dashboard_admin_benutzer_view.php" class="nav-link">
                <i class="fas fa-users"></i> Benutzer
            </a>
            <a href="geraete_verwaltung.php" class="nav-link">
                <i class="fas fa-hammer"></i> Geräte
            </a>
            <!-- WICHTIG: Active Class für Logs -->
            <a href="logs_view.php" class="nav-link active">
                <i class="fas fa-shield-alt"></i> Logs
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <!-- TOP NAVBAR -->
        <nav class="top-navbar">
            <div class="page-title">Admin Dashboard &rsaquo; Sicherheit & Logs</div>
            <div class="user-info">
                <span>Hallo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </nav>

        <!-- CONTENT -->
        <div class="container">
            
            <div class="header-text">
                <h1>System Aktivitäten</h1>
                <p>Protokoll aller Anmeldungen und sicherheitsrelevanten Ereignisse.</p>
            </div>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th width="20%"><i class="far fa-clock"></i> Zeitpunkt</th>
                            <th width="25%"><i class="far fa-user"></i> Name</th>
                            <th width="20%"><i class="fas fa-at"></i> Username</th>
                            <th width="35%"><i class="fas fa-info-circle"></i> Ereignis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($alle_logs) > 0): ?>
                            <?php foreach ($alle_logs as $log): ?>
                            <tr>
                                <td>
                                    <span class="timestamp">
                                        <?php echo date('d.m.Y H:i:s', strtotime($log['Zeitpunkt'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['Nachname']); ?></strong>, 
                                    <?php echo htmlspecialchars($log['Vorname']); ?>
                                </td>
                                <td>
                                    <span style="color: #666;">@<?php echo htmlspecialchars($log['Username']); ?></span>
                                </td>
                                <td>
                                    <span class="badge-log">
                                        <i class="fas fa-terminal" style="font-size: 0.7rem;"></i>
                                        <?php echo htmlspecialchars($log['Ereignis']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:50px; color:#999;">
                                    <i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 10px; display:block;"></i>
                                    Noch keine Log-Einträge vorhanden.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- FOOTER -->
        <footer class="main-footer">
            &copy; 2025 Makerspace Verwaltung<br>
            <span class="credits">Dev: <span class="name">Dein Name</span> & <span class="name">Dein Name</span></span>
        </footer>

    </main>

</body>
</html>