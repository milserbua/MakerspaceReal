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

// ---------------------------------------------------------
// 2. LOGIK: DATEN VERARBEITEN
// ---------------------------------------------------------

// A) Benutzer HINZUFÜGEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $vname  = trim($_POST['vorname']);
    $nname  = trim($_POST['nachname']);
    $user   = trim($_POST['username']);
    $pass   = $_POST['passwort'];
    $role   = $_POST['rolle'];
    $klasse = trim($_POST['klasse']); // NEU: Klasse empfangen

    if (empty($vname) || empty($nname) || empty($user) || empty($pass)) {
        $msg = "Bitte alle Pflichtfelder ausfüllen!";
        $msg_type = "error";
        $show_add_form = true; 
    } else {
        // Prüfen, ob Username existiert
        $check = $pdo->prepare("SELECT WerkBenutzerID FROM werkstattbenutzer WHERE Username = ?");
        $check->execute([$user]);
        if ($check->rowCount() > 0) {
            $msg = "Dieser Benutzername ist bereits vergeben!";
            $msg_type = "error";
            $show_add_form = true;
        } else {
            $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
            // NEU: Klasse in den INSERT-Befehl aufgenommen
            $stmt = $pdo->prepare("INSERT INTO werkstattbenutzer (Vorname, Nachname, Username, Passwort, Rolle, Klasse) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$vname, $nname, $user, $hashedPass, $role, $klasse])) {
                $msg = "Benutzer '$user' erfolgreich angelegt!";
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

// B) Benutzer LÖSCHEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $del_id = $_POST['delete_id'];
    
    if ($del_id == $_SESSION['userid']) {
        $msg = "Du kannst dich nicht selbst löschen!";
        $msg_type = "error";
        $show_del_form = true; 
    } else {
        $check = $pdo->prepare("SELECT Username FROM werkstattbenutzer WHERE WerkBenutzerID = ?");
        $check->execute([$del_id]);
        $userToDelete = $check->fetch(PDO::FETCH_ASSOC);

        if ($userToDelete) {
            $stmt = $pdo->prepare("DELETE FROM werkstattbenutzer WHERE WerkBenutzerID = ?");
            if ($stmt->execute([$del_id])) {
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

// 3. DATEN LADEN (Inklusive Klasse)
$sql = "SELECT WerkBenutzerID, Vorname, Nachname, Username, Rolle, Klasse FROM werkstattbenutzer ORDER BY Nachname ASC";
$stmt = $pdo->query($sql);
$alle_mitglieder = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzerverwaltung</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4a90e2; 
            --bg: #f4f7f6; 
            --text: #333; 
            --card-bg: #fff;
            --danger: #e74c3c; 
            --success: #2ecc71; 
            --warning: #f39c12; 
            --border: #e9ecef;
        }

        body { font-family: 'Roboto', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding-bottom: 50px; }
        .navbar { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .brand { font-weight: 700; color: var(--primary); font-size: 1.2rem; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .btn-back { display: inline-flex; align-items: center; text-decoration: none; color: #666; font-weight: 500; margin-bottom: 20px; transition: 0.2s; }
        .btn-back:hover { color: var(--primary); transform: translateX(-3px); }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; color: white; font-weight: 500; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .alert.error { background-color: var(--danger); }
        .alert.success { background-color: var(--success); }

        .action-buttons { display: flex; gap: 15px; margin-bottom: 20px; }
        .btn-toggle {
            border: none; padding: 12px 24px; border-radius: 6px; font-size: 1rem; cursor: pointer; 
            display: flex; align-items: center; gap: 10px; font-weight: 500; color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.1s, opacity 0.2s;
        }
        .btn-toggle:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-add-toggle { background-color: var(--primary); }
        .btn-del-toggle { background-color: var(--danger); }

        .collapsible-form {
            display: none; background: var(--card-bg); border-radius: 8px; padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 30px; animation: slideDown 0.3s ease-out;
        }
        #add-form-container { border-left: 5px solid var(--primary); }
        #del-form-container { border-left: 5px solid var(--danger); }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .form-title { margin-top: 0; margin-bottom: 20px; font-size: 1.1rem; color: #444; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end; }
        
        .input-group label { display: block; margin-bottom: 5px; font-size: 0.85rem; color: #666; font-weight: 500; }
        .input-group input, .input-group select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;
            font-size: 0.95rem; box-sizing: border-box; transition: border 0.3s;
        }
        .input-group input:focus, .input-group select:focus { border-color: var(--primary); outline: none; }

        .btn-submit { color: white; border: none; padding: 11px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; width: 100%; transition: opacity 0.2s; }
        .btn-green { background: var(--success); }
        .btn-red { background: var(--danger); }

        .filter-bar {
            display: flex; gap: 15px; padding: 20px;
            background: #fff; border-bottom: 1px solid var(--border);
            border-top-left-radius: 8px; border-top-right-radius: 8px;
        }
        .filter-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem; }
        .filter-select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem; min-width: 150px; }

        .table-card { background: var(--card-bg); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; color: #6c757d; font-size: 0.8rem; text-transform: uppercase; padding: 15px 20px; text-align: left; }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover { background-color: #fafafa; }

        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #991b1b; }
        .role-user { background: #d1fae5; color: #065f46; }
        .role-manager { background: #fef3c7; color: #92400e; }

        .btn-delete-small {
            background: #fff; border: 1px solid var(--danger); color: var(--danger);
            padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: 0.2s;
        }
        .btn-delete-small:hover { background: var(--danger); color: white; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand">Admin Konsole</div>
        <div>Eingeloggt als: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></div>
    </nav>

    <div class="container">
        <a href="dashboard_admin_view.php" class="btn-back">← Zurück zum Dashboard</a>

        <?php if ($msg): ?>
            <div class="alert <?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="btn-toggle btn-add-toggle" onclick="toggleAddForm()"><span>➕</span> Neuen Benutzer anlegen</button>
            <button class="btn-toggle btn-del-toggle" onclick="toggleDelForm()"><span>🗑️</span> Benutzer löschen</button>
        </div>

        <div id="add-form-container" class="collapsible-form" style="<?php echo $show_add_form ? 'display: block;' : ''; ?>">
            <h3 class="form-title">Neuen Benutzer anlegen</h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div class="input-group"><label>Vorname</label><input type="text" name="vorname" placeholder="Max" required></div>
                    <div class="input-group"><label>Nachname</label><input type="text" name="nachname" placeholder="Mustermann" required></div>
                    
                    <div class="input-group"><label>Klasse</label><input type="text" name="klasse" placeholder="z.B. 10A"></div>
                    
                    <div class="input-group"><label>Username</label><input type="text" name="username" placeholder="max.muster" required></div>
                    <div class="input-group"><label>Passwort</label><input type="password" name="passwort" placeholder="******" required></div>
                    <div class="input-group">
                        <label>Rolle</label>
                        <select name="rolle">
                            <option value="Mitglied">Mitglied</option>
                            <option value="Raumbeauftragter">Raumbeauftragter</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div><button type="submit" class="btn-submit btn-green">Speichern</button></div>
                </div>
            </form>
        </div>

        <div id="del-form-container" class="collapsible-form" style="<?php echo $show_del_form ? 'display: block;' : ''; ?>">
            <h3 class="form-title" style="color: var(--danger);">Benutzer löschen</h3>
            <form action="" method="POST" onsubmit="return confirm('Sicher?');">
                <input type="hidden" name="action" value="delete">
                <div class="form-grid" style="grid-template-columns: 2fr 1fr;">
                    <div class="input-group">
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
                    <div><button type="submit" class="btn-submit btn-red">Löschen</button></div>
                </div>
            </form>
        </div>

        <div class="table-card">
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="🔍 Suchen nach Name, Username oder Klasse..." onkeyup="filterTable()">
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
                        <th>ID</th> <th>Name</th> <th>Klasse</th> <th>Username</th> <th>Rolle</th> <th style="text-align: right;">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($alle_mitglieder) > 0): ?>
                        <?php foreach ($alle_mitglieder as $user): ?>
                        <tr class="user-row">
                            <td>#<?php echo htmlspecialchars($user['WerkBenutzerID']); ?></td>
                            <td class="name-cell"><strong><?php echo htmlspecialchars($user['Nachname']); ?></strong>, <?php echo htmlspecialchars($user['Vorname']); ?></td>
                            <td class="klasse-cell"><?php echo htmlspecialchars($user['Klasse'] ?: '-'); ?></td>
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
                                        <button type="submit" class="btn-delete-small">🗑️</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:0.8rem; color:#ccc;">(Du)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 40px; color: #999;">Keine Benutzer.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

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
                var roleText = rows[i].querySelector('.badge').getAttribute('data-role').toLowerCase();

                var textMatch = (nameText.indexOf(filter) > -1) || (userText.indexOf(filter) > -1) || (klasseText.indexOf(filter) > -1);
                var roleMatch = (roleFilter === 'all') || (roleText === roleFilter);

                rows[i].style.display = (textMatch && roleMatch) ? "" : "none";
            }
        }
    </script>
</body>
</html>