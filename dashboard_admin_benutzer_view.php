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

// Schulungen für Modal laden
$stmt_s = $pdo->query("SELECT Bezeichnung FROM maschinenschulungen ORDER BY Bezeichnung ASC");
$alle_verfuegbaren_schulungen = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------
// 2. LOGIK
// ---------------------------------------------------------

// Schulungen AKTUALISIEREN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user_trainings') {
    $uid = $_POST['user_id'];
    $trainings = isset($_POST['schulungen']) ? implode(', ', $_POST['schulungen']) : '';
    $stmt = $pdo->prepare("UPDATE werkstattbenutzer SET schulungen = ? WHERE WerkBenutzerID = ?");
    if ($stmt->execute([$trainings, $uid])) {
        $msg = "Schulungen aktualisiert."; $msg_type = "success";
    }
}

// Benutzer HINZUFÜGEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $vname = trim($_POST['vorname']); $nname = trim($_POST['nachname']);
    $user = trim($_POST['username']); $pass = $_POST['passwort'];
    $role = $_POST['rolle']; $klasse = trim($_POST['klasse']);
    if (!empty($vname) && !empty($nname) && !empty($user) && !empty($pass)) {
        $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO werkstattbenutzer (Vorname, Nachname, Username, Passwort, Rolle, Klasse) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vname, $nname, $user, $hashedPass, $role, $klasse]);
        $msg = "Benutzer angelegt!"; $msg_type = "success";
    }
}

// Benutzer LÖSCHEN (Direkt aus der Zeile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_single') {
    $del_id = $_POST['delete_id'];
    // Selbst-Löschen verhindern
    if ($del_id != $_SESSION['userid']) {
        $pdo->prepare("DELETE FROM werkstattbenutzer WHERE WerkBenutzerID = ?")->execute([$del_id]);
        $msg = "Benutzer erfolgreich gelöscht."; $msg_type = "success";
    } else {
        $msg = "Du kannst dich nicht selbst löschen!"; $msg_type = "danger";
    }
}

$alle_mitglieder = $pdo->query("SELECT * FROM werkstattbenutzer ORDER BY Nachname ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung | Makerspace</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary-color: #4a90e2; 
            --sidebar-bg: #2c3e50; 
            --bg-color: #f4f7f6; 
            --text-color: #333;
            --danger: #e74c3c; 
            --success: #2ecc71; 
            --border: #e9ecef; 
        }

        body { font-family: 'Roboto', sans-serif; background: var(--bg-color); color: var(--text-color); margin: 0; display: flex; height: 100vh; overflow: hidden; }

        /* --- SIDEBAR --- */
        .sidebar { width: 260px; background-color: var(--sidebar-bg); color: #ecf0f1; display: flex; flex-direction: column; flex-shrink: 0; z-index: 10; }
        .sidebar-brand { height: 70px; display: flex; align-items: center; padding: 0 25px; font-size: 1.4rem; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 12px; }
        .sidebar-brand i { color: var(--primary-color); }
        .sidebar-nav { flex: 1; padding: 20px 10px; overflow-y: auto; }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: 0.3s; font-size: 0.95rem; border-radius: 8px; margin-bottom: 5px; }
        .nav-link i { width: 35px; font-size: 1.1rem; }
        .nav-link:hover, .nav-link.active { background-color: #34495e; color: #fff; }
        .nav-link.active i { color: var(--primary-color); }

        /* --- HAUPTBEREICH --- */
        .main-content { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .top-navbar { height: 70px; background-color: #fff; padding: 0 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.03); flex-shrink: 0; }
        .container { padding: 30px; }

        /* --- TABELLE --- */
        .table-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 12px 20px; text-align: left; font-size: 0.8rem; color: #666; text-transform: uppercase; }
        td { padding: 10px 20px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; margin: 2px; background: #e0e7ff; color: #4338ca; display: inline-block; }
        .badge-role { background: #d1fae5; color: #065f46; }

        .btn-toggle { border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; color: white; font-weight: 500; }
        .collapsible-form { display: none; background: #fff; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border); }
        
        .action-btn { background: none; border: none; cursor: pointer; font-size: 1.1rem; transition: 0.2s; padding: 4px; }
        .action-btn:hover { opacity: 0.6; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal-content { background: white; margin: 10% auto; padding: 25px; width: 380px; border-radius: 12px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-tools"></i> Makerspace</div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard_view.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
            <a href="dashboard_admin_benutzer_view.php" class="nav-link active"><i class="fas fa-users"></i> Benutzer</a>
            <a href="geraete_verwaltung.php" class="nav-link"><i class="fas fa-hammer"></i> Geräte</a>
            <a href="logs_view.php" class="nav-link"><i class="fas fa-shield-alt"></i> Logs</a>
        </nav>
    </aside>

    <main class="main-content">
        <nav class="top-navbar">
            <div style="font-weight:700;">Benutzerverwaltung</div>
            <div style="font-size: 0.9rem;">Hallo, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b> | <a href="logout.php" style="color:var(--danger); text-decoration:none;">Abmelden</a></div>
        </nav>

        <div class="container">
            <?php if ($msg): ?>
                <div style="padding:15px; background:<?php echo $msg_type=='success'?'var(--success)':'var(--danger)'; ?>; color:white; border-radius:6px; margin-bottom:20px;">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <button class="btn-toggle" style="background:var(--primary-color); margin-bottom: 20px;" onclick="toggleForm('add-form')">➕ Benutzer anlegen</button>

            <div id="add-form" class="collapsible-form">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px;">
                        <input type="text" name="vorname" placeholder="Vorname" required style="padding:8px;">
                        <input type="text" name="nachname" placeholder="Nachname" required style="padding:8px;">
                        <input type="text" name="username" placeholder="Benutzername" required style="padding:8px;">
                        <input type="password" name="passwort" placeholder="Passwort" required style="padding:8px;">
                        <input type="text" name="klasse" placeholder="Klasse" style="padding:8px;">
                        <select name="rolle" style="padding:8px;"><option>Mitglied</option><option>Admin</option></select>
                        <button type="submit" class="btn-toggle" style="background:var(--success); grid-column: span 3;">Speichern</button>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div style="padding: 15px; border-bottom: 1px solid var(--border);">
                    <input type="text" id="searchInput" placeholder="Suchen..." onkeyup="filterTable()" style="padding:8px; width:100%; border:1px solid #ddd; border-radius:4px;">
                </div>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Benutzername</th>
                            <th>Rolle</th>
                            <th>Schulungen</th>
                            <th style="text-align:right;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alle_mitglieder as $user): ?>
                        <tr class="user-row">
                            <td><strong><?php echo htmlspecialchars($user['Nachname']); ?></strong>, <?php echo htmlspecialchars($user['Vorname']); ?></td>
                            <td style="color: #666; font-family: monospace;"><?php echo htmlspecialchars($user['Username']); ?></td>
                            <td><span class="badge badge-role" data-role="<?php echo htmlspecialchars($user['Rolle']); ?>"><?php echo htmlspecialchars($user['Rolle']); ?></span></td>
                            <td>
                                <?php 
                                    $u_s = !empty($user['schulungen']) ? explode(', ', $user['schulungen']) : [];
                                    foreach ($u_s as $b) echo '<span class="badge">'.htmlspecialchars($b).'</span>';
                                ?>
                            </td>
                            <td style="text-align:right; white-space: nowrap;">
                                <button class="action-btn" title="Schulungen" onclick="openModal(<?php echo $user['WerkBenutzerID']; ?>, '<?php echo addslashes($user['schulungen']); ?>', '<?php echo htmlspecialchars($user['Vorname'].' '.$user['Nachname']); ?>')">⚙️</button>
                                
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Soll der Benutzer wirklich gelöscht werden?');">
                                    <input type="hidden" name="action" value="delete_single">
                                    <input type="hidden" name="delete_id" value="<?php echo $user['WerkBenutzerID']; ?>">
                                    <button type="submit" class="action-btn" title="Löschen">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="trainingModal" class="modal">
        <div class="modal-content">
            <h3 id="mTitle" style="margin-top:0;">Schulungen</h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="update_user_trainings"><input type="hidden" name="user_id" id="mUserId">
                <div style="max-height: 200px; overflow-y: auto; margin-bottom:15px; border:1px solid #eee; padding:10px;">
                    <?php foreach ($alle_verfuegbaren_schulungen as $s): ?>
                        <label style="display:block; margin:5px 0;"><input type="checkbox" name="schulungen[]" value="<?php echo htmlspecialchars($s['Bezeichnung']); ?>" class="t-cb"> <?php echo htmlspecialchars($s['Bezeichnung']); ?></label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" style="background:var(--success); color:white; border:none; padding:10px; width:100%; border-radius:6px; cursor:pointer;">Speichern</button>
                <button type="button" onclick="closeModal()" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer; color:#888;">Abbrechen</button>
            </form>
        </div>
    </div>

    <script>
        function toggleForm(id) {
            const f = document.getElementById(id);
            f.style.display = (f.style.display === 'block') ? 'none' : 'block';
        }
        function openModal(id, current, name) {
            document.getElementById('mUserId').value = id;
            document.getElementById('mTitle').innerText = name;
            const cbs = document.querySelectorAll('.t-cb');
            const has = current ? current.split(', ') : [];
            cbs.forEach(cb => { cb.checked = has.includes(cb.value); });
            document.getElementById('trainingModal').style.display = 'block';
        }
        function closeModal() { document.getElementById('trainingModal').style.display = 'none'; }
        
        function filterTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.user-row').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
            });
        }
    </script>
</body>
</html>