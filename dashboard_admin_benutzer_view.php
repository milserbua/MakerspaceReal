<?php
session_start();
require 'db.php';

// 1. SICHERHEITS-CHECK: Nur Admins
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Variablen initialisieren
$msg = "";
$msg_type = ""; 
$show_add_form = false;   
$show_del_form = false;   

// Alle verfügbaren Schulungen für das Formular laden
$stmt_s = $pdo->query("SELECT * FROM maschinenschulungen ORDER BY Bezeichnung ASC");
$verfuegbare_schulungen = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------
// 2. LOGIK: DATEN VERARBEITEN
// ---------------------------------------------------------

// A) HINZUFÜGEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $vname  = trim($_POST['vorname']);
    $nname  = trim($_POST['nachname']);
    $user   = trim($_POST['username']);
    $pass   = $_POST['passwort'];
    $role   = $_POST['rolle'];
    $klasse = trim($_POST['klasse']);
    
    // Array der ausgewählten Schulungs-IDs
    $gewaehlte_schulungen = isset($_POST['schulungen']) ? $_POST['schulungen'] : [];

    if (empty($vname) || empty($nname) || empty($user) || empty($pass)) {
        $msg = "Bitte alle Pflichtfelder ausfüllen!";
        $msg_type = "error";
        $show_add_form = true; 
    } else {
        $check = $pdo->prepare("SELECT WerkBenutzerID FROM werkstattbenutzer WHERE Username = ?");
        $check->execute([$user]);
        if ($check->rowCount() > 0) {
            $msg = "Dieser Benutzername ist bereits vergeben!";
            $msg_type = "error";
            $show_add_form = true;
        } else {
            $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
            
            // 1. Benutzer anlegen
            $stmt = $pdo->prepare("INSERT INTO werkstattbenutzer (Vorname, Nachname, Username, Passwort, Rolle, Klasse) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$vname, $nname, $user, $hashedPass, $role, $klasse])) {
                
                // ID des neuen Users holen
                $new_user_id = $pdo->lastInsertId();

                // 2. Schulungen speichern
                if (!empty($gewaehlte_schulungen)) {
                    $sql_schulung = "INSERT INTO werkstattbenutzerschulungen (WerkBenutzerID, MaschinenSchulungsID, AbschlussDatum) VALUES (?, ?, CURDATE())";
                    $stmt_schulung = $pdo->prepare($sql_schulung);
                    
                    foreach ($gewaehlte_schulungen as $schulungs_id) {
                        $stmt_schulung->execute([$new_user_id, $schulungs_id]);
                    }
                }

                // --- NEU: LOGBUCH EINTRAG (Erstellen) ---
                $logText = "Admin hat Benutzer angelegt: $nname $vname ($user) [Rolle: $role]";
                $stmtLog = $pdo->prepare("INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, ?)");
                $stmtLog->execute([$_SESSION['userid'], $logText]);
                // ----------------------------------------

                $msg = "Benutzer '$user' erfolgreich angelegt (inkl. Schulungen)!";
                $msg_type = "success";
                $show_add_form = false; 
            } else {
                $msg = "Fehler beim Datenbank-Eintrag.";
                $msg_type = "error";
                $show_add_form = true;
            }
        }
    }
}

// B) LÖSCHEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $del_id = $_POST['delete_id'];
    
    if ($del_id == $_SESSION['userid']) {
        $msg = "Du kannst dich nicht selbst löschen!";
        $msg_type = "error";
        $show_del_form = true; 
    } else {
        // Daten vorher holen für das Logbuch
        $check = $pdo->prepare("SELECT Username, Vorname, Nachname FROM werkstattbenutzer WHERE WerkBenutzerID = ?");
        $check->execute([$del_id]);
        $userToDelete = $check->fetch(PDO::FETCH_ASSOC);

        if ($userToDelete) {
            // Erst Schulungen löschen
            $pdo->prepare("DELETE FROM werkstattbenutzerschulungen WHERE WerkBenutzerID = ?")->execute([$del_id]);
            
            // Dann User löschen
            $stmt = $pdo->prepare("DELETE FROM werkstattbenutzer WHERE WerkBenutzerID = ?");
            if ($stmt->execute([$del_id])) {
                
                // --- NEU: LOGBUCH EINTRAG (Löschen) ---
                $geloeschterName = $userToDelete['Nachname'] . " " . $userToDelete['Vorname'] . " (" . $userToDelete['Username'] . ")";
                $logText = "Admin hat Benutzer gelöscht: $geloeschterName";
                $stmtLog = $pdo->prepare("INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, ?)");
                $stmtLog->execute([$_SESSION['userid'], $logText]);
                // --------------------------------------

                $msg = "Benutzer '" . htmlspecialchars($userToDelete['Username']) . "' wurde gelöscht.";
                $msg_type = "success";
                $show_del_form = false;
            } else {
                $msg = "Fehler beim Löschen.";
                $msg_type = "error";
            }
        } else {
            $msg = "Benutzer nicht gefunden.";
            $msg_type = "error";
        }
    }
}

// ---------------------------------------------------------
// 3. DATEN LADEN
// ---------------------------------------------------------
$sql = "SELECT 
            u.WerkBenutzerID, 
            u.Vorname, 
            u.Nachname, 
            u.Username, 
            u.Rolle, 
            u.Klasse,
            GROUP_CONCAT(s.Bezeichnung SEPARATOR ',') AS SchulungsListe
        FROM werkstattbenutzer u
        LEFT JOIN werkstattbenutzerschulungen bs ON u.WerkBenutzerID = bs.WerkBenutzerID
        LEFT JOIN maschinenschulungen s ON bs.MaschinenSchulungsID = s.MaschinenSchulungsID
        GROUP BY u.WerkBenutzerID
        ORDER BY u.Nachname ASC";

try {
    $stmt = $pdo->query($sql);
    $alle_mitglieder = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $msg = "DB-Fehler: " . $e->getMessage();
    $msg_type = "error";
    $stmt = $pdo->query("SELECT * FROM werkstattbenutzer ORDER BY Nachname ASC");
    $alle_mitglieder = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzerverwaltung</title>
    
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
            width: 260px; background-color: var(--sidebar-bg); color: #ecf0f1;
            display: flex; flex-direction: column; flex-shrink: 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05); z-index: 10;
        }
        .sidebar-brand {
            height: 70px; display: flex; align-items: center; padding: 0 25px;
            font-size: 1.4rem; font-weight: 700; text-transform: uppercase;
            border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; gap: 12px;
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
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
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

        /* --- CONTAINER --- */
        .container { max-width: 1400px; margin: 0 auto; padding: 40px; width: 100%; box-sizing: border-box; flex: 1; }

        .action-buttons { display: flex; gap: 15px; margin-bottom: 25px; }
        .btn-toggle {
            border: none; padding: 12px 24px; border-radius: 6px; font-size: 0.95rem; cursor: pointer; 
            display: flex; align-items: center; gap: 10px; font-weight: 500; color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s, opacity 0.2s;
        }
        .btn-toggle:hover { transform: translateY(-2px); opacity: 0.95; }
        .btn-add { background-color: var(--primary-color); }
        .btn-del { background-color: var(--danger); }

        .alert {
            padding: 15px; border-radius: 6px; margin-bottom: 25px; color: white; font-weight: 500;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 10px;
        }
        .alert.error { background-color: var(--danger); }
        .alert.success { background-color: var(--success); }

        .card {
            background: #fff; border-radius: var(--border-radius);
            box-shadow: var(--card-shadow); margin-bottom: 30px; overflow: hidden;
            animation: slideDown 0.3s ease-out;
        }
        .collapsible-form { display: none; padding: 30px; border-left: 5px solid transparent; }
        #add-form-container { border-left-color: var(--primary-color); }
        #del-form-container { border-left-color: var(--danger); }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; align-items: end; }
        
        .full-width { grid-column: 1 / -1; }

        label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #666; font-weight: 500; }
        input[type="text"], input[type="password"], select {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;
            font-size: 0.95rem; box-sizing: border-box; transition: border 0.3s; outline: none; background: #fafafa;
        }
        input:focus, select:focus { border-color: var(--primary-color); background: #fff; }
        .btn-submit {
            color: white; border: none; padding: 11px 20px; border-radius: 6px; cursor: pointer;
            font-weight: 600; width: 100%; transition: opacity 0.2s;
        }
        .btn-green { background: var(--success); }
        .btn-red { background: var(--danger); }

        .checkbox-group {
            display: flex; gap: 10px; flex-wrap: wrap; background: #fafafa; padding: 10px; border-radius: 6px; border: 1px solid #eee;
        }
        .checkbox-label {
            display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.9rem; 
            padding: 5px 10px; background: white; border-radius: 4px; border: 1px solid #e0e0e0; transition: 0.2s;
        }
        .checkbox-label:hover { border-color: var(--primary-color); }
        .checkbox-label input { width: auto; margin: 0; }

        /* TABLE */
        .filter-bar {
            padding: 20px; background: #fff; border-bottom: 1px solid var(--border-color);
            display: flex; gap: 15px; flex-wrap: wrap; align-items: center;
        }
        .filter-input { flex: 1; min-width: 200px; }
        .filter-select { width: auto; min-width: 150px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th {
            background: #f8f9fa; color: #6c757d; font-size: 0.8rem; text-transform: uppercase;
            padding: 15px 20px; font-weight: 700; border-bottom: 2px solid var(--border-color);
        }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fafafa; }

        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #991b1b; }
        .role-user { background: #d1fae5; color: #065f46; }
        .role-manager { background: #fef3c7; color: #92400e; }

        .badge-training {
            background: #f3e8ff; color: #7e22ce; 
            padding: 3px 8px; border-radius: 6px; font-size: 0.75rem;
            margin-right: 4px; margin-bottom: 3px; display: inline-block;
            border: 1px solid #d8b4fe; font-weight: 500;
        }

        .btn-icon-small {
            background: transparent; border: 1px solid var(--danger); color: var(--danger);
            width: 32px; height: 32px; border-radius: 4px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; transition: 0.2s;
        }
        .btn-icon-small:hover { background: var(--danger); color: white; }

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
            <a href="dashboard_admin_benutzer_view.php" class="nav-link active">
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

    <!-- MAIN -->
    <main class="main-content">
        <nav class="top-navbar">
            <div class="page-title">Admin Dashboard &rsaquo; Benutzerverwaltung</div>
            <div class="user-info">
                <span>Hallo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </nav>

        <div class="container">
            <?php if ($msg): ?>
                <div class="alert <?php echo $msg_type; ?>">
                    <i class="fas <?php echo ($msg_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <button class="btn-toggle btn-add" onclick="toggleAddForm()">
                    <i class="fas fa-plus"></i> Benutzer anlegen
                </button>
                <button class="btn-toggle btn-del" onclick="toggleDelForm()">
                    <i class="fas fa-trash"></i> Benutzer löschen
                </button>
            </div>

            <!-- ADD FORM -->
            <div id="add-form-container" class="card collapsible-form" style="<?php echo $show_add_form ? 'display: block;' : ''; ?>">
                <h3 class="form-title">Neuen Benutzer anlegen</h3>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="form-grid">
                        <div><label>Vorname</label><input type="text" name="vorname" placeholder="Max" required></div>
                        <div><label>Nachname</label><input type="text" name="nachname" placeholder="Mustermann" required></div>
                        <div><label>Klasse</label><input type="text" name="klasse" placeholder="z.B. 10A"></div>
                        <div><label>Username</label><input type="text" name="username" placeholder="max.muster" required></div>
                        <div><label>Passwort</label><input type="password" name="passwort" placeholder="******" required></div>
                        <div>
                            <label>Rolle</label>
                            <select name="rolle">
                                <option value="Mitglied">Mitglied</option>
                                <option value="Raumbeauftragter">Raumbeauftragter</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        
                        <!-- SCHULUNGEN CHECKBOXEN -->
                        <div class="full-width">
                            <label>Erhaltene Schulungen:</label>
                            <div class="checkbox-group">
                                <?php if (count($verfuegbare_schulungen) > 0): ?>
                                    <?php foreach ($verfuegbare_schulungen as $s): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="schulungen[]" value="<?php echo $s['MaschinenSchulungsID']; ?>">
                                            <?php echo htmlspecialchars($s['Bezeichnung']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color:#999; font-size:0.9rem;">Keine Schulungen in der Datenbank gefunden.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="full-width">
                            <button type="submit" class="btn-submit btn-green">Benutzer Speichern</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- DEL FORM -->
            <div id="del-form-container" class="card collapsible-form" style="<?php echo $show_del_form ? 'display: block;' : ''; ?>">
                <h3 class="form-title" style="color: var(--danger);">Benutzer auswählen und löschen</h3>
                <form action="" method="POST" onsubmit="return confirm('Sicher?');">
                    <input type="hidden" name="action" value="delete">
                    <div class="form-grid" style="grid-template-columns: 2fr 1fr;">
                        <div>
                            <label>Benutzer auswählen:</label>
                            <select name="delete_id" required>
                                <option value="" disabled selected>-- Bitte wählen --</option>
                                <?php foreach ($alle_mitglieder as $user): ?>
                                    <?php if ($user['WerkBenutzerID'] != $_SESSION['userid']): ?>
                                        <option value="<?php echo $user['WerkBenutzerID']; ?>">
                                            <?php echo htmlspecialchars($user['Nachname'] . ', ' . $user['Vorname'] . ' (' . $user['Username'] . ')'); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><button type="submit" class="btn-submit btn-red">Endgültig löschen</button></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="filter-bar">
                    <input type="text" id="searchInput" class="filter-input" placeholder="🔍 Suchen nach Name, Schulung oder Klasse..." onkeyup="filterTable()">
                    <select id="roleFilter" class="filter-select" onchange="filterTable()">
                        <option value="all">Alle Rollen</option>
                        <option value="Mitglied">Mitglied</option>
                        <option value="Raumbeauftragter">Raumbeauftragter</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>

                <table id="userTable">
                    <thead>
                        <tr>
                            <th width="5%">ID</th> 
                            <th width="20%">Name</th> 
                            <th width="10%">Klasse</th> 
                            <th width="30%">Schulungen</th> 
                            <th width="15%">Username</th> 
                            <th width="10%">Rolle</th> 
                            <th width="10%" style="text-align: right;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($alle_mitglieder) > 0): ?>
                            <?php foreach ($alle_mitglieder as $user): ?>
                            <tr class="user-row">
                                <td>#<?php echo htmlspecialchars($user['WerkBenutzerID']); ?></td>
                                <td class="name-cell">
    <strong><?php echo htmlspecialchars($user['Nachname']); ?></strong>
    <?php 
        // Zeige Komma und Vornamen nur an, wenn ein Vorname existiert
        if (!empty($user['Vorname'])) {
            echo ', ' . htmlspecialchars($user['Vorname']); 
        }
    ?>
</td>
                                <td class="klasse-cell"><?php echo htmlspecialchars($user['Klasse'] ?: '-'); ?></td>
                                
                                <td class="schulung-cell">
                                    <?php 
                                    if (!empty($user['SchulungsListe'])) {
                                        $schulungen = explode(',', $user['SchulungsListe']);
                                        foreach ($schulungen as $s) {
                                            echo '<span class="badge-training">' . htmlspecialchars(trim($s)) . '</span>';
                                        }
                                    } else {
                                        echo '<span style="color:#ccc; font-size:0.8rem; font-style:italic;">Keine</span>';
                                    }
                                    ?>
                                </td>

                                <td class="username-cell"><?php echo htmlspecialchars($user['Username']); ?></td>
                                <td>
                                    <?php 
                                        $r = $user['Rolle'];
                                        $roleClass = 'role-user';
                                        if (stripos($r, 'Admin') !== false) $roleClass = 'role-admin';
                                        elseif (stripos($r, 'Raumbeauftragter') !== false) $roleClass = 'role-manager';
                                    ?>
                                    <span class="badge <?php echo $roleClass; ?>" data-role="<?php echo htmlspecialchars($r); ?>">
                                        <?php echo htmlspecialchars($r); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($user['WerkBenutzerID'] != $_SESSION['userid']): ?>
                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Wirklich löschen?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="delete_id" value="<?php echo $user['WerkBenutzerID']; ?>">
                                            <button type="submit" class="btn-icon-small" title="Löschen"><i class="fas fa-trash"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:0.8rem; color:#ccc;">(Du)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding: 40px; color: #999;">Keine Benutzer.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="main-footer">
            &copy; 2025 Makerspace<br>
        </footer>
    </main>

    <script>
        function toggleAddForm() {
            var add = document.getElementById('add-form-container');
            var del = document.getElementById('del-form-container');
            del.style.display = 'none';
            add.style.display = (add.style.display === 'none' || add.style.display === '') ? 'block' : 'none';
        }

        function toggleDelForm() {
            var add = document.getElementById('add-form-container');
            var del = document.getElementById('del-form-container');
            add.style.display = 'none';
            del.style.display = (del.style.display === 'none' || del.style.display === '') ? 'block' : 'none';
        }

        function filterTable() {
            var input = document.getElementById('searchInput');
            var filter = input.value.toLowerCase();
            var roleSelect = document.getElementById('roleFilter');
            var roleFilter = roleSelect.value.toLowerCase();
            
            var table = document.getElementById('userTable');
            var rows = table.getElementsByClassName('user-row');

            for (var i = 0; i < rows.length; i++) {
                var nameText = rows[i].querySelector('.name-cell').textContent.toLowerCase();
                var userText = rows[i].querySelector('.username-cell').textContent.toLowerCase();
                var klasseText = rows[i].querySelector('.klasse-cell').textContent.toLowerCase();
                var schulungText = rows[i].querySelector('.schulung-cell').textContent.toLowerCase();
                var roleText = rows[i].querySelector('.badge').getAttribute('data-role').toLowerCase();

                var textMatch = (nameText.indexOf(filter) > -1) || 
                                (userText.indexOf(filter) > -1) || 
                                (klasseText.indexOf(filter) > -1) ||
                                (schulungText.indexOf(filter) > -1);

                var roleMatch = (roleFilter === 'all') || (roleText === roleFilter);

                rows[i].style.display = (textMatch && roleMatch) ? "" : "none";
            }
        }
    </script>
</body>
</html>