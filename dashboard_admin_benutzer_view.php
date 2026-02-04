<?php
session_start();
require 'db.php';

// 1. SICHERHEITS-CHECK
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$msg = "";
$msg_type = ""; 
$show_add_form = false;   

// Schulungen für Dropdown laden
$stmt_s = $pdo->query("SELECT * FROM maschinenschulungen ORDER BY Bezeichnung ASC");
$verfuegbare_schulungen = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------
// 2. LOGIK: DATEN VERARBEITEN (CREATE / UPDATE / DELETE)
// ---------------------------------------------------------

// A) CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $vname = trim($_POST['vorname']); $nname = trim($_POST['nachname']);
    $user = trim($_POST['username']); $pass = $_POST['passwort'];
    $role = $_POST['rolle']; $klasse = trim($_POST['klasse']);
    $gewaehlte_schulungen = $_POST['schulungen'] ?? [];

    $check = $pdo->prepare("SELECT WerkBenutzerID FROM werkstattbenutzer WHERE Username = ?");
    $check->execute([$user]);
    if ($check->rowCount() > 0) {
        $msg = "Username existiert bereits!"; $msg_type = "error"; $show_add_form = true;
    } else {
        $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO werkstattbenutzer (Vorname, Nachname, Username, Passwort, Rolle, Klasse) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$vname, $nname, $user, $hashedPass, $role, $klasse])) {
            $new_id = $pdo->lastInsertId();
            if (!empty($gewaehlte_schulungen)) {
                $stmt_s = $pdo->prepare("INSERT INTO werkstattbenutzerschulungen (WerkBenutzerID, MaschinenSchulungsID, AbschlussDatum) VALUES (?, ?, CURDATE())");
                foreach ($gewaehlte_schulungen as $sid) $stmt_s->execute([$new_id, $sid]);
            }
            $pdo->prepare("INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, ?)")->execute([$_SESSION['userid'], "Admin hat Benutzer angelegt: $nname $vname"]);
            $msg = "Benutzer angelegt!"; $msg_type = "success";
        } else { $msg = "Fehler."; $msg_type = "error"; }
    }
}

// B) UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['edit_id']; $vname = trim($_POST['vorname']); $nname = trim($_POST['nachname']);
    $user = trim($_POST['username']); $role = $_POST['rolle']; $klasse = trim($_POST['klasse']);
    $gewaehlte_schulungen = $_POST['schulungen'] ?? [];

    $sql = "UPDATE werkstattbenutzer SET Vorname=?, Nachname=?, Username=?, Rolle=?, Klasse=? WHERE WerkBenutzerID=?";
    if ($pdo->prepare($sql)->execute([$vname, $nname, $user, $role, $klasse, $id])) {
        $pdo->prepare("DELETE FROM werkstattbenutzerschulungen WHERE WerkBenutzerID = ?")->execute([$id]);
        if (!empty($gewaehlte_schulungen)) {
            $stmt_s = $pdo->prepare("INSERT INTO werkstattbenutzerschulungen (WerkBenutzerID, MaschinenSchulungsID, AbschlussDatum) VALUES (?, ?, CURDATE())");
            foreach ($gewaehlte_schulungen as $sid) $stmt_s->execute([$id, $sid]);
        }
        $pdo->prepare("INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, ?)")->execute([$_SESSION['userid'], "Admin hat Benutzer bearbeitet: $nname $vname"]);
        $msg = "Aktualisiert!"; $msg_type = "success";
    }
}

// C) DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $del_id = $_POST['delete_id'];
    if ($del_id != $_SESSION['userid']) {
        $check = $pdo->prepare("SELECT Username FROM werkstattbenutzer WHERE WerkBenutzerID = ?");
        $check->execute([$del_id]);
        $u = $check->fetch();
        if ($u) {
            $pdo->prepare("DELETE FROM werkstattbenutzerschulungen WHERE WerkBenutzerID = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM werkstattbenutzer WHERE WerkBenutzerID = ?")->execute([$del_id]);
            $pdo->prepare("INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, ?)")->execute([$_SESSION['userid'], "Admin löschte User: " . $u['Username']]);
            $msg = "Gelöscht."; $msg_type = "success";
        }
    }
}

// ---------------------------------------------------------
// 3. DATEN LADEN (PERFORMANTE SUCHE)
// ---------------------------------------------------------

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit_clause = "";

// Basis-Query
$sql = "SELECT u.*, 
        GROUP_CONCAT(bs.MaschinenSchulungsID) as SchulungsIDs, 
        GROUP_CONCAT(s.Bezeichnung SEPARATOR ', ') as SchulungsNamen
        FROM werkstattbenutzer u
        LEFT JOIN werkstattbenutzerschulungen bs ON u.WerkBenutzerID = bs.WerkBenutzerID
        LEFT JOIN maschinenschulungen s ON bs.MaschinenSchulungsID = s.MaschinenSchulungsID";

// Such-Logik
if (!empty($search)) {
    // Wenn gesucht wird: Filtern
    $sql .= " WHERE u.Vorname LIKE :s OR u.Nachname LIKE :s OR u.Username LIKE :s OR u.Klasse LIKE :s OR u.Rolle LIKE :s";
} else {
    // Wenn NICHT gesucht wird: Nur die ersten 50 laden (Performance!)
    $limit_clause = " LIMIT 50"; 
}

$sql .= " GROUP BY u.WerkBenutzerID ORDER BY u.Nachname ASC" . $limit_clause;

$stmt = $pdo->prepare($sql);

if (!empty($search)) {
    $term = "%$search%";
    $stmt->execute(['s' => $term]);
} else {
    $stmt->execute();
}

$alle_mitglieder = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-color: #4a90e2; --sidebar-bg: #2c3e50; --bg-color: #f4f7f6; --text-color: #333; --danger: #e74c3c; --success: #2ecc71; --border-radius: 12px; }
        body { font-family: 'Roboto', sans-serif; margin: 0; background: var(--bg-color); color: var(--text-color); height: 100vh; display: flex; overflow: hidden; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); color: #ecf0f1; display: flex; flex-direction: column; flex-shrink: 0; }
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
        .sidebar-nav { flex: 1; padding: 20px 10px; overflow-y: auto; }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: #34495e; color: #fff; } .nav-link.active i { color: var(--primary-color); } .nav-link i { width: 35px; }
        
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .top-navbar { height: 70px; background: #fff; padding: 0 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.03); flex-shrink: 0; }
        .btn-logout { color: var(--danger); text-decoration: none; border: 1px solid rgba(231,76,60,0.3); padding: 6px 16px; border-radius: 20px; display: flex; gap: 8px; align-items: center; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 40px; width: 100%; box-sizing: border-box; flex: 1; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 25px; color: white; display: flex; gap: 10px; align-items: center; }
        .alert.success { background: var(--success); } .alert.error { background: var(--danger); }
        
        .action-buttons { display: flex; gap: 15px; margin-bottom: 25px; }
        .btn-toggle { border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 10px; color: white; }
        .btn-add { background: var(--primary-color); }
        
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .collapsible-form { display: none; padding: 30px; border-left: 5px solid var(--primary-color); }

        .user-info { display: flex; align-items: center; gap: 20px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end; }
        .full-width { grid-column: 1 / -1; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; background: #fafafa; }
        .checkbox-group { display: flex; gap: 10px; flex-wrap: wrap; background: #fafafa; padding: 10px; border-radius: 6px; border: 1px solid #eee; }
        .checkbox-label { display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 5px 10px; background: white; border: 1px solid #e0e0e0; border-radius: 4px; }
        .btn-submit { color: white; border: none; padding: 11px 20px; border-radius: 6px; cursor: pointer; width: 100%; background: var(--success); }
        
        /* Suchfeld Styling */
        .filter-bar { padding: 20px; border-bottom: 1px solid #eee; background: #fdfdfd; display: flex; gap: 10px;}
        .search-input { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; }
        .search-btn { background: var(--primary-color); color: white; border: none; padding: 0 25px; border-radius: 6px; cursor: pointer; font-weight: 500;}
        .search-btn:hover { background: #357abd; }

        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px 20px; color: #6c757d; font-size: 0.8rem; text-transform: uppercase; text-align: left; }
        td { padding: 15px 20px; border-bottom: 1px solid #e9ecef; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #991b1b; } .role-user { background: #d1fae5; color: #065f46; } .role-manager { background: #fef3c7; color: #92400e; }
        .badge-training { background: #f3e8ff; color: #7e22ce; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; margin-right: 4px; display: inline-block; border: 1px solid #d8b4fe; }
        
        .btn-icon-small { background: transparent; border: 1px solid #ddd; width: 32px; height: 32px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; margin-left: 5px; }
        .btn-edit { color: var(--primary-color); border-color: var(--primary-color); }
        .btn-trash { color: var(--danger); border-color: var(--danger); }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-tools"></i> Makerspace</div>
        <nav class="sidebar-nav">
            <a href="dashboard_admin_view.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
            <a href="dashboard_admin_benutzer_view.php" class="nav-link active"><i class="fas fa-users"></i> Benutzer</a>
            <a href="geraete_verwaltung.php" class="nav-link"><i class="fas fa-hammer"></i> Geräte</a>
            <a href="logs_view.php" class="nav-link"><i class="fas fa-shield-alt"></i> Logs</a>
        </nav>
    </aside>

    <main class="main-content">
        <nav class="top-navbar">
            <div style="color:#7f8c8d;">Admin Dashboard &rsaquo; Benutzerverwaltung</div>
            <div class="user-info">
                <span>Hallo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </nav>

        <div class="container">
            <?php if ($msg): ?><div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

            <div class="action-buttons">
                <button class="btn-toggle btn-add" onclick="resetForm(); toggleAddForm();">
                    <i class="fas fa-plus"></i> Benutzer anlegen
                </button>
            </div>

            <!-- FORMULAR -->
            <div id="add-form-container" class="card collapsible-form" style="<?php echo $show_add_form ? 'display: block;' : ''; ?>">
                <h3 style="margin-top:0;" id="form-title">Neuen Benutzer anlegen</h3>
                <form action="" method="POST" id="userForm">
                    <input type="hidden" name="action" id="form-action" value="create">
                    <input type="hidden" name="edit_id" id="edit-id" value="">
                    
                    <div class="form-grid">
                        <div><label>Vorname</label><input type="text" name="vorname" id="f_vorname" required></div>
                        <div><label>Nachname</label><input type="text" name="nachname" id="f_nachname" required></div>
                        <div><label>Klasse</label><input type="text" name="klasse" id="f_klasse"></div>
                        <div><label>Username</label><input type="text" name="username" id="f_username" required></div>
                        <div id="pass-container"><label>Passwort <small>(Leer lassen bei Edit)</small></label><input type="password" name="passwort" id="f_passwort"></div>
                        <div><label>Rolle</label><select name="rolle" id="f_rolle"><option value="Mitglied">Mitglied</option><option value="Raumbeauftragter">Raumbeauftragter</option><option value="Admin">Admin</option></select></div>
                        <div class="full-width"><label>Schulungen:</label><div class="checkbox-group">
                            <?php foreach ($verfuegbare_schulungen as $s): ?>
                                <label class="checkbox-label"><input type="checkbox" name="schulungen[]" value="<?php echo $s['MaschinenSchulungsID']; ?>" class="chk-schulung"> <?php echo htmlspecialchars($s['Bezeichnung']); ?></label>
                            <?php endforeach; ?>
                        </div></div>
                        <div class="full-width"><button type="submit" class="btn-submit" id="btn-save">Speichern</button></div>
                    </div>
                </form>
            </div>

            <!-- TABELLE MIT SERVER-SIDE SEARCH -->
            <div class="card">
                <!-- SUCHFORMULAR (Reset Button entfernt) -->
                <form method="GET" class="filter-bar">
                    <input type="text" name="search" class="search-input" 
                           placeholder="🔍 Namen, Klasse oder Schulung suchen..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">Suchen</button>
                </form>

                <table id="userTable">
                    <thead><tr><th>Name</th><th>Klasse</th><th>Schulungen</th><th>Username</th><th>Rolle</th><th style="text-align:right;">Aktion</th></tr></thead>
                    <tbody>
                        <?php if (count($alle_mitglieder) > 0): ?>
                            <?php foreach ($alle_mitglieder as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['Nachname']); ?></strong><?php if(!empty($user['Vorname'])) echo ', ' . htmlspecialchars($user['Vorname']); ?></td>
                                <td><?php echo htmlspecialchars($user['Klasse'] ?: '-'); ?></td>
                                <td>
                                    <?php if (!empty($user['SchulungsNamen'])) {
                                        foreach (explode(',', $user['SchulungsNamen']) as $s) echo '<span class="badge-training">' . htmlspecialchars(trim($s)) . '</span>';
                                    } else echo '<span style="color:#ccc;">Keine</span>'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                <td><span class="badge <?php echo (stripos($user['Rolle'], 'Admin') !== false ? 'role-admin' : (stripos($user['Rolle'], 'Raum') !== false ? 'role-manager' : 'role-user')); ?>"><?php echo htmlspecialchars($user['Rolle']); ?></span></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <button type="button" class="btn-icon-small btn-edit" onclick='editUser(<?php echo json_encode($user); ?>)'><i class="fas fa-cog"></i></button>
                                    <?php if ($user['WerkBenutzerID'] != $_SESSION['userid']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Löschen?');">
                                        <input type="hidden" name="action" value="delete"><input type="hidden" name="delete_id" value="<?php echo $user['WerkBenutzerID']; ?>">
                                        <button class="btn-icon-small btn-trash"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:40px; color:#999;">
                                <?php echo !empty($search) ? "Keine Ergebnisse für '$search'" : "Keine Benutzer gefunden."; ?>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (empty($search)): ?>
                    <div style="padding:15px; text-align:center; color:#999; font-size:0.85rem;">
                        Zeige die ersten 50 Einträge. Nutze die Suche für mehr.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleAddForm() {
            var form = document.getElementById('add-form-container');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }

        function resetForm() {
            document.getElementById('userForm').reset();
            document.getElementById('form-action').value = 'create';
            document.getElementById('edit-id').value = '';
            document.getElementById('form-title').innerText = 'Neuen Benutzer anlegen';
            document.getElementById('btn-save').innerText = 'Speichern';
            document.getElementById('f_passwort').required = true;
            document.querySelectorAll('.chk-schulung').forEach(cb => cb.checked = false);
        }

        function editUser(user) {
            document.getElementById('add-form-container').style.display = 'block';
            window.scrollTo(0,0);
            document.getElementById('form-action').value = 'update';
            document.getElementById('edit-id').value = user.WerkBenutzerID;
            document.getElementById('form-title').innerText = 'Benutzer bearbeiten';
            document.getElementById('btn-save').innerText = 'Änderungen speichern';
            document.getElementById('f_vorname').value = user.Vorname;
            document.getElementById('f_nachname').value = user.Nachname;
            document.getElementById('f_username').value = user.Username;
            document.getElementById('f_klasse').value = user.Klasse;
            document.getElementById('f_rolle').value = user.Rolle;
            document.getElementById('f_passwort').required = false;
            let ids = user.SchulungsIDs ? user.SchulungsIDs.toString().split(',') : [];
            document.querySelectorAll('.chk-schulung').forEach(cb => {
                cb.checked = ids.includes(cb.value);
            });
        }
    </script>
</body>
</html>