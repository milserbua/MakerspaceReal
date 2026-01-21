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

// Alle verfügbaren Schulungen laden
$stmt_s = $pdo->query("SELECT Bezeichnung FROM maschinenschulungen ORDER BY Bezeichnung ASC");
$alle_verfuegbaren_schulungen = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------
// 2. LOGIK: DATEN VERARBEITEN
// ---------------------------------------------------------

// A) Schulungen aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user_trainings') {
    $uid = $_POST['user_id'];
    $trainings = isset($_POST['schulungen']) ? implode(', ', $_POST['schulungen']) : '';
    $stmt = $pdo->prepare("UPDATE werkstattbenutzer SET schulungen = ? WHERE WerkBenutzerID = ?");
    if ($stmt->execute([$trainings, $uid])) {
        $msg = "Schulungen wurden aktualisiert.";
        $msg_type = "success";
    }
}

// B) Benutzer HINZUFÜGEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $vname  = trim($_POST['vorname']);
    $nname  = trim($_POST['nachname']);
    $user   = trim($_POST['username']);
    $pass   = $_POST['passwort'];
    $role   = $_POST['rolle'];
    $klasse = trim($_POST['klasse']);
    if (!empty($vname) && !empty($nname) && !empty($user) && !empty($pass)) {
        $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO werkstattbenutzer (Vorname, Nachname, Username, Passwort, Rolle, Klasse) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vname, $nname, $user, $hashedPass, $role, $klasse]);
        $msg = "Benutzer angelegt!"; $msg_type = "success";
    }
}

// C) Benutzer LÖSCHEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $del_id = $_POST['delete_id'];
    if ($del_id != $_SESSION['userid']) {
        $pdo->prepare("DELETE FROM werkstattbenutzer WHERE WerkBenutzerID = ?")->execute([$del_id]);
        $msg = "Benutzer gelöscht."; $msg_type = "success";
    }
}

// 3. DATEN LADEN
$alle_mitglieder = $pdo->query("SELECT * FROM werkstattbenutzer ORDER BY Nachname ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4a90e2; --bg: #f4f7f6; --text: #333; --card-bg: #fff; --danger: #e74c3c; --success: #2ecc71; --border: #e9ecef; }
        body { font-family: 'Roboto', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding-bottom: 50px; }
        .navbar { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        .btn-back { display: inline-block; text-decoration: none; color: #666; margin-bottom: 20px; font-weight: 500; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; color: white; }
        .action-buttons { display: flex; gap: 15px; margin-bottom: 20px; }
        .btn-toggle { border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; color: white; font-weight: 500; }
        .collapsible-form { display: none; background: #fff; padding: 25px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .filter-bar { display: flex; gap: 15px; padding: 20px; background: #fff; border-bottom: 1px solid var(--border); border-radius: 8px 8px 0 0; }
        .filter-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }

        .table-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px 20px; text-align: left; font-size: 0.8rem; color: #666; text-transform: uppercase; }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border); }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-right: 5px; background: #e0e7ff; color: #4338ca; }
        .badge-role { background: #d1fae5; color: #065f46; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal-content { background: white; margin: 10% auto; padding: 30px; width: 400px; border-radius: 12px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div style="font-weight:700; color:var(--primary);">Admin Konsole</div>
        <div>Eingeloggt als: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></div>
    </nav>

    <div class="container">
        <a href="dashboard_admin_view.php" class="btn-back">← Zurück zum Dashboard</a>

        <?php if ($msg): ?>
            <div class="alert" style="background:<?php echo $msg_type=='success'?'var(--success)':'var(--danger)'; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="btn-toggle" style="background:var(--primary)" onclick="toggleForm('add-form-container')">➕ Benutzer anlegen</button>
            <button class="btn-toggle" style="background:var(--danger)" onclick="toggleForm('del-form-container')">🗑️ Benutzer löschen</button>
        </div>

        <div id="add-form-container" class="collapsible-form">
            <h3>Neuen Benutzer anlegen</h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create">
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
                    <input type="text" name="vorname" placeholder="Vorname" required style="padding:10px;">
                    <input type="text" name="nachname" placeholder="Nachname" required style="padding:10px;">
                    <input type="text" name="klasse" placeholder="Klasse" style="padding:10px;">
                    <input type="text" name="username" placeholder="Username" required style="padding:10px;">
                    <input type="password" name="passwort" placeholder="Passwort" required style="padding:10px;">
                    <select name="rolle" style="padding:10px;"><option>Mitglied</option><option>Raumbeauftragter</option><option>Admin</option></select>
                    <button type="submit" class="btn-toggle" style="background:var(--success); grid-column: span 3;">Speichern</button>
                </div>
            </form>
        </div>

        <div id="del-form-container" class="collapsible-form">
            <h3 style="color:var(--danger)">Benutzer löschen</h3>
            <form action="" method="POST" onsubmit="return confirm('Sicher?');">
                <input type="hidden" name="action" value="delete">
                <select name="delete_id" required style="padding:10px; width:80%;">
                    <option value="">-- Wählen --</option>
                    <?php foreach ($alle_mitglieder as $u): ?>
                        <option value="<?php echo $u['WerkBenutzerID']; ?>"><?php echo htmlspecialchars($u['Nachname'].", ".$u['Vorname']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-toggle" style="background:var(--danger)">Löschen</button>
            </form>
        </div>

        <div class="table-card">
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="🔍 Suchen..." onkeyup="filterTable()">
                <select id="roleFilter" class="filter-select" onchange="filterTable()">
                    <option value="all">Alle Rollen</option>
                    <option value="Mitglied">Mitglied</option>
                    <option value="Raumbeauftragter">Raumbeauftragter</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>

            <table id="userTable">
                <thead>
                    <tr><th>Name</th><th>Username</th><th>Klasse</th><th>Rolle</th><th>Schulungen</th><th style="text-align:right;">Aktion</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($alle_mitglieder as $user): ?>
                    <tr class="user-row">
                        <td class="search-cell"><strong><?php echo htmlspecialchars($user['Nachname']); ?></strong>, <?php echo htmlspecialchars($user['Vorname']); ?></td>
                        <td class="search-cell"><?php echo htmlspecialchars($user['Username']); ?></td>
                        <td class="search-cell"><?php echo htmlspecialchars($user['Klasse'] ?: '-'); ?></td>
                        <td class="role-cell"><span class="badge badge-role" data-role="<?php echo htmlspecialchars($user['Rolle']); ?>"><?php echo htmlspecialchars($user['Rolle']); ?></span></td>
                        <td class="search-cell">
                            <?php 
                                $u_s = !empty($user['schulungen']) ? explode(', ', $user['schulungen']) : [];
                                foreach ($u_s as $b) echo '<span class="badge">'.htmlspecialchars($b).'</span>';
                            ?>
                        </td>
                        <td style="text-align:right;">
                            <button onclick="openModal(<?php echo $user['WerkBenutzerID']; ?>, '<?php echo addslashes($user['schulungen']); ?>', '<?php echo htmlspecialchars($user['Vorname'].' '.$user['Nachname']); ?>')" style="background:var(--primary); color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">⚙️</button>
                            <form method="POST" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="delete_id" value="<?php echo $user['WerkBenutzerID']; ?>"><button type="submit" onclick="return confirm('Sicher?')" style="background:none; border:1px solid var(--danger); color:var(--danger); padding:5px 8px; border-radius:4px; cursor:pointer;">🗑️</button></form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="trainingModal" class="modal">
        <div class="modal-content">
            <h3 id="mTitle">Schulungen</h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="update_user_trainings"><input type="hidden" name="user_id" id="mUserId">
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid #eee; padding: 10px;">
                    <?php foreach ($alle_verfuegbaren_schulungen as $s): ?>
                        <div style="margin:5px 0;"><input type="checkbox" name="schulungen[]" value="<?php echo htmlspecialchars($s['Bezeichnung']); ?>" class="t-cb"> <?php echo htmlspecialchars($s['Bezeichnung']); ?></div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" style="background:var(--success); color:white; border:none; padding:10px; width:100%; margin-top:15px; cursor:pointer; font-weight:bold;">Speichern</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; margin-top:10px; cursor:pointer;">Abbrechen</button>
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
            const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                const textContent = row.innerText.toLowerCase();
                const roleBadge = row.querySelector('.badge-role');
                const role = roleBadge ? roleBadge.getAttribute('data-role').toLowerCase() : "";

                const matchesText = textContent.includes(input);
                const matchesRole = (roleFilter === 'all' || role === roleFilter);

                row.style.display = (matchesText && matchesRole) ? "" : "none";
            });
        }
    </script>
</body>
</html>