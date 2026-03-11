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
    $vname = trim($_POST['vorname']);
    $nname = trim($_POST['nachname']);
    $user  = trim($_POST['username']);
    $pass  = $_POST['passwort'];
    $role  = $_POST['rolle'];
    $klasse = trim($_POST['klasse']); // NEU: Klasse aus Formular

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
            // NEU: Klasse im INSERT Statement hinzugefügt
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

// B) Benutzer LÖSCHEN (Bleibt gleich...)
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
            }
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
    <title>Benutzerverwaltung</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Deine Styles bleiben gleich, ich füge nur eine Spalte hinzu */
        :root { --primary: #4a90e2; --bg: #f4f7f6; --text: #333; --card-bg: #fff; --danger: #e74c3c; --success: #2ecc71; --border: #e9ecef; }
        body { font-family: 'Roboto', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding-bottom: 50px; }
        .navbar { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .collapsible-form { display: none; background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px; border-left: 5px solid var(--primary); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end; }
        .input-group label { display: block; margin-bottom: 5px; font-size: 0.85rem; color: #666; }
        .input-group input, .input-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-submit { background: var(--success); color: white; border: none; padding: 11px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .table-card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #f8f9fa; font-size: 0.8rem; text-transform: uppercase; color: #6c757d; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .role-admin { background: #fee2e2; color: #991b1b; }
        .role-user { background: #d1fae5; color: #065f46; }
        .class-badge { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    </style>
</head>
<body>

<nav class="navbar">
    <div style="font-weight:700; color:var(--primary);">Admin Konsole</div>
    <div>Eingeloggt als: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></div>
</nav>

<div class="container">
    <a href="dashboard_admin_view.php" style="text-decoration:none; color:#666;">← Zurück</a>

    <?php if ($msg): ?>
        <div style="padding:15px; background:<?php echo $msg_type=='success'?'#2ecc71':'#e74c3c'; ?>; color:white; border-radius:6px; margin-bottom:20px;">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom:20px;">
        <button onclick="toggleAddForm()" style="background:var(--primary); color:white; border:none; padding:12px 20px; border-radius:6px; cursor:pointer;">➕ Benutzer mit Klasse anlegen</button>
    </div>

    <div id="add-form-container" class="collapsible-form" style="<?php echo $show_add_form ? 'display: block;' : ''; ?>">
        <form action="" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-grid">
                <div class="input-group"><label>Vorname</label><input type="text" name="vorname" required></div>
                <div class="input-group"><label>Nachname</label><input type="text" name="nachname" required></div>
                <div class="input-group"><label>Klasse</label><input type="text" name="klasse" placeholder="z.B. 10A"></div> <div class="input-group"><label>Username</label><input type="text" name="username" required></div>
                <div class="input-group"><label>Passwort</label><input type="password" name="passwort" required></div>
                <div class="input-group">
                    <label>Rolle</label>
                    <select name="rolle">
                        <option value="Mitglied">Mitglied</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Speichern</button>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div style="padding:20px; background:#fff; border-bottom:1px solid #eee;">
            <input type="text" id="searchInput" placeholder="Suche nach Name, Username oder Klasse..." onkeyup="filterTable()" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
        </div>
        <table id="userTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Klasse</th> <th>Username</th>
                    <th>Rolle</th>
                    <th style="text-align:right;">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alle_mitglieder as $user): ?>
                <tr class="user-row">
                    <td><strong><?php echo htmlspecialchars($user['Nachname']); ?></strong>, <?php echo htmlspecialchars($user['Vorname']); ?></td>
                    <td><span class="badge class-badge"><?php echo htmlspecialchars($user['Klasse'] ?: '-'); ?></span></td> <td class="username-cell"><?php echo htmlspecialchars($user['Username']); ?></td>
                    <td>
                        <span class="badge <?php echo $user['Rolle']=='Admin'?'role-admin':'role-user'; ?>">
                            <?php echo htmlspecialchars($user['Rolle']); ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <?php if ($user['WerkBenutzerID'] != $_SESSION['userid']): ?>
                            <form method="POST" onsubmit="return confirm('Löschen?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="delete_id" value="<?php echo $user['WerkBenutzerID']; ?>">
                                <button type="submit" style="background:none; border:none; cursor:pointer;">🗑️</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleAddForm() {
        var f = document.getElementById('add-form-container');
        f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';
    }

    function filterTable() {
        var input = document.getElementById('searchInput').value.toLowerCase();
        var rows = document.getElementsByClassName('user-row');
        for (var i = 0; i < rows.length; i++) {
            // Sucht im gesamten Text der Zeile (Name, Username UND Klasse)
            var text = rows[i].innerText.toLowerCase();
            rows[i].style.display = text.includes(input) ? "" : "none";
        }
    }
</script>

</body>
</html>