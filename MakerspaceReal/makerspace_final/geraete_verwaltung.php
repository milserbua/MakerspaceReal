<?php
session_start();
require 'db.php';

// 1. SICHERHEITS-CHECK: Nur Admins
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$msg = "";
$msg_type = ""; 

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
    } else {
        $stmt = $pdo->prepare("INSERT INTO MaschinenImWerkstattbereich (Bezeichnung, WerkBereichID, NotwendigeSchulungsID) VALUES (?, ?, ?)");
        if ($stmt->execute([$bezeichnung, $bereich_id, $schulung_id])) {
            $msg = "Maschine erfolgreich angelegt!";
            $msg_type = "success";
        } else {
            $msg = "Fehler beim Speichern.";
            $msg_type = "error";
        }
    }
}

// B) Maschine LÖSCHEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_machine') {
    $del_id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM MaschinenImWerkstattbereich WHERE MaschineID = ?");
    if ($stmt->execute([$del_id])) {
        $msg = "Maschine gelöscht.";
        $msg_type = "success";
    }
}

// 3. DATEN LADEN (für die Tabelle und die Dropdowns)
// Alle Maschinen mit Bereichsnamen und Schulungsnamen holen
$sql = "SELECT m.*, b.Bezeichnung as BereichsName, s.Bezeichnung as SchulungsName 
        FROM MaschinenImWerkstattbereich m
        JOIN Werkstattbereich b ON m.WerkBereichID = b.WerkBereichID
        LEFT JOIN Maschinenschulungen s ON m.NotwendigeSchulungsID = s.MaschinenSchulungsID
        ORDER BY m.Bezeichnung ASC";
$alle_maschinen = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Alle Bereiche für das Dropdown laden
$bereiche = $pdo->query("SELECT * FROM Werkstattbereich")->fetchAll(PDO::FETCH_ASSOC);

// Alle Schulungen für das Dropdown laden
$schulungen = $pdo->query("SELECT * FROM Maschinenschulungen")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Geräteverwaltung</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* CSS bleibt identisch zu deinem Benutzer-View für ein einheitliches Design */
        :root { --primary: #4a90e2; --bg: #f4f7f6; --text: #333; --card-bg: #fff; --danger: #e74c3c; --success: #2ecc71; --border: #e9ecef; }
        body { font-family: 'Roboto', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .btn-back { text-decoration: none; color: #666; margin-bottom: 20px; display: inline-block; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; color: white; }
        .success { background: var(--success); }
        .error { background: var(--danger); }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: #f8f9fa; font-size: 0.8rem; text-transform: uppercase; }
        .input-group { margin-bottom: 15px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard_admin_view.php" class="btn-back">← Zurück zum Dashboard</a>
    <h1>Maschinen & Geräte</h1>

    <?php if ($msg): ?>
        <div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Neue Maschine erfassen</h3>
        <form action="" method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <input type="hidden" name="action" value="create_machine">
            <div class="input-group">
                <label>Bezeichnung</label>
                <input type="text" name="bezeichnung" placeholder="z.B. Standbohrmaschine" required>
            </div>
            <div class="input-group">
                <label>Bereich</label>
                <select name="bereich_id" required>
                    <option value="">-- Bereich wählen --</option>
                    <?php foreach ($bereiche as $b): ?>
                        <option value="<?php echo $b['WerkBereichID']; ?>"><?php echo htmlspecialchars($b['Bezeichnung']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label>Notwendige Schulung (optional)</label>
                <select name="schulung_id">
                    <option value="">Keine Schulung nötig</option>
                    <?php foreach ($schulungen as $s): ?>
                        <option value="<?php echo $s['MaschinenSchulungsID']; ?>"><?php echo htmlspecialchars($s['Bezeichnung']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">Hinzufügen</button>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Maschine</th>
                    <th>Standort (Bereich)</th>
                    <th>Benötigte Schulung</th>
                    <th style="text-align: right;">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alle_maschinen as $m): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($m['Bezeichnung']); ?></strong></td>
                    <td><?php echo htmlspecialchars($m['BereichsName']); ?></td>
                    <td><?php echo $m['SchulungsName'] ? htmlspecialchars($m['SchulungsName']) : '<i style="color:#999;">Freie Nutzung</i>'; ?></td>
                    <td style="text-align: right;">
                        <form method="POST" onsubmit="return confirm('Löschen?');">
                            <input type="hidden" name="action" value="delete_machine">
                            <input type="hidden" name="delete_id" value="<?php echo $m['MaschineID']; ?>">
                            <button type="submit" style="background:none; border:none; color:red; cursor:pointer;">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>