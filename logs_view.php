<?php
session_start();
require 'db.php';

// SICHERHEITS-CHECK: Nur Admins
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// DATEN LADEN: Wer hat sich wann angemeldet?
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
    <title>System Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4a90e2; --bg: #f4f7f6; --text: #333; --card-bg: #fff; --border: #e9ecef; }
        body { font-family: 'Roboto', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .btn-back { text-decoration: none; color: #666; margin-bottom: 20px; display: inline-block; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: #f8f9fa; font-size: 0.8rem; text-transform: uppercase; color: #666; }
        .timestamp { color: #888; font-family: monospace; }
        .event-badge { background: #d1fae5; color: #065f46; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard_admin_view.php" class="btn-back">← Zurück zum Dashboard</a>
    <h1>Sicherheit & System-Logs</h1>

    <div class="card">
        <h3>Letzte Aktivitäten</h3>
        <table>
            <thead>
                <tr>
                    <th>Zeitpunkt</th>
                    <th>Benutzer</th>
                    <th>Benutzername</th>
                    <th>Ereignis</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($alle_logs) > 0): ?>
                    <?php foreach ($alle_logs as $log): ?>
                    <tr>
                        <td class="timestamp"><?php echo date('d.m.Y H:i:s', strtotime($log['Zeitpunkt'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($log['Nachname'] . ", " . $log['Vorname']); ?></strong></td>
                        <td>@<?php echo htmlspecialchars($log['Username']); ?></td>
                        <td><span class="event-badge"><?php echo htmlspecialchars($log['Ereignis']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">Noch keine Einträge vorhanden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>