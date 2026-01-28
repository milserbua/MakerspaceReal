<?php
// geraete_verwaltung.php
session_start();
require 'db.php';

// 1. SICHERHEITS-CHECK
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$msg = "";
$msg_type = ""; 
$show_add_form = false; // Steuert, ob das Formular aufgeklappt ist

// ---------------------------------------------------------
// 2. LOGIK: MASCHINEN VERARBEITEN
// ---------------------------------------------------------

// A) Maschine HINZUFÜGEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_machine') {
    $bezeichnung = trim($_POST['bezeichnung']);
    $bereich_id  = $_POST['bereich_id'];
    $schulung_id = !empty($_POST['schulung_id']) ? $_POST['schulung_id'] : null;

    if (empty($bezeichnung) || empty($bereich_id)) {
        $msg = "Bitte Bezeichnung und Bereich angeben!";
        $msg_type = "error";
        $show_add_form = true;
    } else {
        $stmt = $pdo->prepare("INSERT INTO MaschinenImWerkstattbereich (Bezeichnung, WerkBereichID, NotwendigeSchulungsID) VALUES (?, ?, ?)");
        if ($stmt->execute([$bezeichnung, $bereich_id, $schulung_id])) {
            $msg = "Maschine '$bezeichnung' erfolgreich angelegt!";
            $msg_type = "success";
            $show_add_form = false;
        } else {
            $msg = "Fehler beim Speichern.";
            $msg_type = "error";
            $show_add_form = true;
        }
    }
}

// B) Maschine LÖSCHEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_machine') {
    $del_id = $_POST['delete_id'];
    
    // Kleiner Check, wie die Maschine heißt (für Feedback)
    $check = $pdo->prepare("SELECT Bezeichnung FROM MaschinenImWerkstattbereich WHERE MaschineID = ?");
    $check->execute([$del_id]);
    $machineToDelete = $check->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("DELETE FROM MaschinenImWerkstattbereich WHERE MaschineID = ?");
    if ($stmt->execute([$del_id])) {
        $msg = "Maschine '" . ($machineToDelete['Bezeichnung'] ?? 'Unbekannt') . "' wurde gelöscht.";
        $msg_type = "success";
    } else {
        $msg = "Fehler beim Löschen.";
        $msg_type = "error";
    }
}

// 3. DATEN LADEN
// Maschinen inkl. Namen der Bereiche und Schulungen
$sql = "SELECT m.*, b.Bezeichnung as BereichsName, s.Bezeichnung as SchulungsName 
        FROM MaschinenImWerkstattbereich m
        JOIN Werkstattbereich b ON m.WerkBereichID = b.WerkBereichID
        LEFT JOIN Maschinenschulungen s ON m.NotwendigeSchulungsID = s.MaschinenSchulungsID
        ORDER BY m.Bezeichnung ASC";
$alle_maschinen = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Dropdown Daten
$bereiche = $pdo->query("SELECT * FROM Werkstattbereich ORDER BY Bezeichnung ASC")->fetchAll(PDO::FETCH_ASSOC);
$schulungen = $pdo->query("SELECT * FROM Maschinenschulungen ORDER BY Bezeichnung ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geräteverwaltung</title>
    
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
            --danger: #e74c3c;
            --success: #2ecc71;
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
            color: var(--danger); text-decoration: none; border: 1px solid rgba(231, 76, 60, 0.3);
            padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;
            transition: all 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-logout:hover { background: var(--danger); color: white; }

        /* --- CONTENT CONTAINER --- */
        .container {
            max-width: 1200px; margin: 0 auto; padding: 40px; width: 100%; box-sizing: border-box; flex: 1;
        }

        /* ACTIONS & ALERTS */
        .action-buttons { display: flex; gap: 15px; margin-bottom: 25px; }
        
        .btn-toggle {
            border: none; padding: 12px 24px; border-radius: 6px; font-size: 0.95rem; cursor: pointer; 
            display: flex; align-items: center; gap: 10px; font-weight: 500; color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s, opacity 0.2s;
            background-color: var(--primary-color);
        }
        .btn-toggle:hover { transform: translateY(-2px); opacity: 0.95; }

        .alert {
            padding: 15px; border-radius: 6px; margin-bottom: 25px; color: white; font-weight: 500;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 10px;
        }
        .alert.error { background-color: var(--danger); }
        .alert.success { background-color: var(--success); }

        /* CARD & FORM */
        .card {
            background: #fff; border-radius: var(--border-radius);
            box-shadow: var(--card-shadow); margin-bottom: 30px; overflow: hidden;
            animation: slideDown 0.3s ease-out;
        }
        
        /* Formular Aufklapp-Effekt */
        #add-form-container { 
            display: none; padding: 30px; 
            border-left: 5px solid var(--primary-color); 
        }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .form-title { margin: 0 0 25px 0; font-size: 1.2rem; color: #444; font-weight: 500; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; align-items: end; }
        
        label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #666; font-weight: 500; }
        
        input, select {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;
            font-size: 0.95rem; box-sizing: border-box; transition: border 0.3s; outline: none; background: #fafafa;
        }
        input:focus, select:focus { border-color: var(--primary-color); background: #fff; }

        .btn-submit {
            background: var(--success); color: white; border: none; padding: 11px 20px; border-radius: 6px; cursor: pointer;
            font-weight: 600; width: 100%; transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.9; }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th {
            background: #f8f9fa; color: #6c757d; font-size: 0.8rem; text-transform: uppercase;
            padding: 15px 20px; font-weight: 700; border-bottom: 2px solid var(--border-color);
        }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fafafa; }

        .badge-free { 
            background: #d1fae5; color: #065f46; padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; 
        }
        .badge-req { 
            background: #fee2e2; color: #991b1b; padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; 
        }

        .btn-icon-small {
            background: transparent; border: 1px solid var(--danger); color: var(--danger);
            width: 32px; height: 32px; border-radius: 4px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; transition: 0.2s;
        }
        .btn-icon-small:hover { background: var(--danger); color: white; }

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
            <!-- WICHTIG: Active Class für Geräte -->
            <a href="geraete_verwaltung.php" class="nav-link active">
                <i class="fas fa-hammer"></i> Geräte
            </a>
            <a href="logs_view.php" class="nav-link">
                <i class="fas fa-shield-alt"></i> Logs
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <!-- TOP NAVBAR -->
        <nav class="top-navbar">
            <div class="page-title">Admin Dashboard &rsaquo; Geräteverwaltung</div>
            <div class="user-info">
                <span>Hallo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </nav>

        <!-- CONTENT -->
        <div class="container">

            <!-- FEEDBACK MSG -->
            <?php if ($msg): ?>
                <div class="alert <?php echo $msg_type; ?>">
                    <i class="fas <?php echo ($msg_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <!-- ACTION BUTTON -->
            <div class="action-buttons">
                <button class="btn-toggle" onclick="toggleAddForm()">
                    <i class="fas fa-plus"></i> Neue Maschine erfassen
                </button>
            </div>

            <!-- HINZUFÜGEN FORMULAR -->
            <div id="add-form-container" class="card" style="<?php echo $show_add_form ? 'display: block;' : ''; ?>">
                <h3 class="form-title">Neue Maschine erfassen</h3>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="create_machine">
                    <div class="form-grid" style="grid-template-columns: 1.5fr 1fr 1fr auto;">
                        <div>
                            <label>Bezeichnung</label>
                            <input type="text" name="bezeichnung" placeholder="z.B. 3D-Drucker Prusa" required>
                        </div>
                        <div>
                            <label>Standort (Bereich)</label>
                            <select name="bereich_id" required>
                                <option value="">-- Wählen --</option>
                                <?php foreach ($bereiche as $b): ?>
                                    <option value="<?php echo $b['WerkBereichID']; ?>"><?php echo htmlspecialchars($b['Bezeichnung']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Notwendige Schulung</label>
                            <select name="schulung_id">
                                <option value="">Keine (Freie Nutzung)</option>
                                <?php foreach ($schulungen as $s): ?>
                                    <option value="<?php echo $s['MaschinenSchulungsID']; ?>"><?php echo htmlspecialchars($s['Bezeichnung']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn-submit">Speichern</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TABELLE -->
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Maschine</th>
                            <th>Standort</th>
                            <th>Benötigte Schulung</th>
                            <th style="text-align: right;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($alle_maschinen) > 0): ?>
                            <?php foreach ($alle_maschinen as $m): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($m['Bezeichnung']); ?></strong>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt" style="color: #999; margin-right:5px; font-size:0.8rem;"></i>
                                    <?php echo htmlspecialchars($m['BereichsName']); ?>
                                </td>
                                <td>
                                    <?php if ($m['SchulungsName']): ?>
                                        <span class="badge-req"><?php echo htmlspecialchars($m['SchulungsName']); ?></span>
                                    <?php else: ?>
                                        <span class="badge-free">Freie Nutzung</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <form method="POST" onsubmit="return confirm('Möchtest du <?php echo htmlspecialchars($m['Bezeichnung']); ?> wirklich löschen?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_machine">
                                        <input type="hidden" name="delete_id" value="<?php echo $m['MaschineID']; ?>">
                                        <button type="submit" class="btn-icon-small" title="Löschen"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 40px; color: #999;">Keine Maschinen gefunden.</td></tr>
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

    <!-- JS FÜR TOGGLE -->
    <script>
        function toggleAddForm() {
            var form = document.getElementById('add-form-container');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>