<?php
// profil.php
session_start();
require 'db.php';

// 1. SICHERHEITS-CHECK
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$userid = $_SESSION['userid'];

// 2. USER-DATEN LADEN
$stmt = $pdo->prepare("SELECT Vorname, Nachname, Username, Klasse, Rolle, ErstelltAm FROM werkstattbenutzer WHERE WerkBenutzerID = ?");
$stmt->execute([$userid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Benutzer nicht gefunden.");
}

// 3. SCHULUNGEN LADEN
$sql_schulungen = "SELECT ms.Bezeichnung, wbs.AbschlussDatum 
                   FROM werkstattbenutzerschulungen wbs
                   JOIN maschinenschulungen ms ON wbs.MaschinenSchulungsID = ms.MaschinenSchulungsID
                   WHERE wbs.WerkBenutzerID = ?
                   ORDER BY ms.Bezeichnung ASC";
$stmt_s = $pdo->prepare($sql_schulungen);
$stmt_s->execute([$userid]);
$meine_schulungen = $stmt_s->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Profil</title>
    
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
            --card-shadow: 0 4px 15px rgba(0,0,0,0.05);
            --border-radius: 12px;       
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0; background-color: var(--bg-color); color: var(--text-color);
            height: 100vh; display: flex; overflow: hidden;
        }

       /* --- SIDEBAR (Fixiertes Layout) --- */
        .sidebar {
            width: 260px; background-color: var(--sidebar-bg); color: #ecf0f1;
            display: flex; flex-direction: column; justify-content: space-between; /* WICHTIG */
            flex-shrink: 0; box-shadow: 4px 0 15px rgba(0,0,0,0.05); z-index: 10; height: 100vh;
        }

        /* Wrapper für oben (Logo + Navi) */
        .sidebar-content-top { display: flex; flex-direction: column; flex: 1; overflow-y: auto; }

        .sidebar-brand { 
            min-height: 70px; display: flex; align-items: center; padding: 0 25px; 
            font-size: 1.4rem; font-weight: 700; text-transform: uppercase; 
            border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; gap: 12px; 
        }
        .sidebar-brand i { color: var(--primary-color); }

        .sidebar-nav { padding: 20px 10px; }

        /* Wrapper für unten (Impressum) */
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
        
        /* MAIN CONTENT */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .top-navbar { height: 70px; background-color: #fff; padding: 0 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.03); flex-shrink: 0; }
        .page-title { color: var(--text-muted); font-weight: 500; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .btn-logout { color: #e74c3c; text-decoration: none; border: 1px solid rgba(231, 76, 60, 0.3); padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .btn-logout:hover { background: #e74c3c; color: white; }

        /* CONTENT CONTAINER */
        .container { max-width: 1100px; margin: 0 auto; padding: 40px; width: 100%; box-sizing: border-box; flex: 1; }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr; /* Links schmaler, Rechts breiter */
            gap: 30px;
        }

        /* Profil Karten Design */
        .card { background: #fff; padding: 30px; border-radius: var(--border-radius); box-shadow: var(--card-shadow); }
        
        .card-header { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; }
        .card-header h2 { margin: 0; font-size: 1.3rem; color: #2c3e50; font-weight: 500; }
        .card-header i { font-size: 1.5rem; color: var(--primary-color); }

        /* Info Liste */
        .info-list { list-style: none; padding: 0; margin: 0; }
        .info-item { margin-bottom: 20px; }
        .info-label { display: block; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .info-value { font-size: 1.1rem; color: #333; font-weight: 500; }

        /* Schulungs Liste */
        .training-list { display: flex; flex-direction: column; gap: 15px; }
        .training-item {
            display: flex; justify-content: space-between; align-items: center;
            background: #f8f9fa; padding: 15px 20px; border-radius: 8px; border-left: 4px solid #7e22ce; /* Lila Akzent */
            transition: transform 0.2s;
        }
        .training-item:hover { transform: translateX(5px); background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .t-name { font-weight: 600; color: #333; font-size: 1rem; }
        .t-date { font-size: 0.85rem; color: #888; display: flex; align-items: center; gap: 5px; }

        .no-data { text-align: center; color: #aaa; padding: 30px; font-style: italic; }

        /* Footer */
        .main-footer { background: #fff; border-top: 1px solid #eee; padding: 20px; text-align: center; color: #aaa; font-size: 0.8rem; margin-top: auto; }
        .credits .name { font-weight: 500; color: #999; }

        /* Responsive */
        @media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <aside class="sidebar">
        <!-- OBERER TEIL -->
        <div class="sidebar-content-top">
            <div class="sidebar-brand"><i class="fas fa-tools"></i> MAKERSPACE</div>
            <nav class="sidebar-nav">
                <a href="dashboard_benutzer_view.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="geraete_uebersicht.php" class="nav-link"><i class="fas fa-hammer"></i> Geräte</a>
                <!-- ACTIVE CLASS BEI PROFIL -->
                <a href="profil.php" class="nav-link active"><i class="fas fa-user-circle"></i> Mein Profil</a>
                <a href="hilfe.php" class="nav-link"><i class="fas fa-question-circle"></i> Hilfe</a>
            </nav>
        </div>

        <!-- UNTERER TEIL -->
        <div class="sidebar-bottom">
            <a href="impressum.php" class="nav-link"><i class="fas fa-scale-balanced"></i> Impressum</a>
        </div>
    </aside>

    <main class="main-content">
        
        <nav class="top-navbar">
            <div class="page-title">Mitgliederbereich &rsaquo; Mein Profil</div>
            <div class="user-info">
                <span>Hallo, <strong><?php echo htmlspecialchars($user['Vorname']); ?></strong></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </nav>

        <div class="container">
            
            <div class="profile-grid">
                
                <!-- KARTE 1: PERSÖNLICHE DATEN -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-id-card"></i>
                        <h2>Meine Daten</h2>
                    </div>
                    <ul class="info-list">
                        <li class="info-item">
                            <span class="info-label">Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['Vorname'] . ' ' . $user['Nachname']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Benutzername</span>
                            <span class="info-value">@<?php echo htmlspecialchars($user['Username']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Klasse / Abteilung</span>
                            <span class="info-value"><?php echo !empty($user['Klasse']) ? htmlspecialchars($user['Klasse']) : '-'; ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value" style="color: var(--primary-color);">
                                <?php echo htmlspecialchars($user['Rolle']); ?>
                            </span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Mitglied seit</span>
                            <span class="info-value" style="font-size: 0.9rem; color:#666;">
                                <?php echo date('d.m.Y', strtotime($user['ErstelltAm'])); ?>
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- KARTE 2: SCHULUNGEN -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-graduation-cap"></i>
                        <h2>Meine Schulungen & Lizenzen</h2>
                    </div>
                    
                    <?php if (count($meine_schulungen) > 0): ?>
                        <div class="training-list">
                            <?php foreach ($meine_schulungen as $schulung): ?>
                                <div class="training-item">
                                    <div class="t-name">
                                        <?php echo htmlspecialchars($schulung['Bezeichnung']); ?>
                                    </div>
                                    <div class="t-date" title="Abschlussdatum">
                                        <i class="far fa-calendar-check"></i>
                                        <?php echo date('d.m.Y', strtotime($schulung['AbschlussDatum'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 10px; display:block; color:#ddd;"></i>
                            Du hast noch keine Maschinenschulungen absolviert.
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

        <footer class="main-footer">
            &copy; 2025 Makerspace<br>
        </footer>

    </main>

</body>
</html>