<?php
session_start();
require 'db.php';

// Falls nicht eingeloggt, zum Login schicken
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$ist_admin = ($_SESSION['role'] === 'Admin');
$userid = $_SESSION['userid'];

// 1. Daten für Admins: Alle Benutzer laden
if ($ist_admin) {
    $stmt = $pdo->query("SELECT * FROM Werkstattbenutzer");
    $alle_mitglieder = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Daten für Benutzer: Eigene Schulungen laden
$stmt_schulungen = $pdo->prepare("
    SELECT s.Bezeichnung, s.MaschinenGruppe, bs.AbschlussDatum 
    FROM Werkstattbenutzerschulungen bs
    JOIN Maschinenschulungen s ON bs.MaschinenSchulungsID = s.MaschinenSchulungsID
    WHERE bs.WerkBenutzerID = ?
    ORDER BY bs.AbschlussDatum DESC
");
$stmt_schulungen->execute([$userid]);
$meine_schulungen = $stmt_schulungen->fetchAll(PDO::FETCH_ASSOC);

// 3. Daten für Benutzer: Eigene Maschinenerlaubnisse laden
$stmt_maschinen = $pdo->prepare("
    SELECT m.Bezeichnung, b.Bezeichnung as Raum 
    FROM Benutzungserlaubnis be
    JOIN MaschinenImWerkstattbereich m ON be.MaschineID = m.MaschineID
    JOIN Werkstattbereich b ON m.WerkBereichID = b.WerkBereichID
    WHERE be.WerkBenutzerID = ?
");
$stmt_maschinen->execute([$userid]);
$meine_maschinen = $stmt_maschinen->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makerspace Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background-color: #f9f9f9; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .logout-btn { background-color: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        
        h2 { margin-top: 30px; border-left: 5px solid #4a90e2; padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #4a90e2; color: white; text-transform: uppercase; font-size: 12px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f1f7ff; }
        
        .pw-cell { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #888; font-family: monospace; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1 style="margin:0;">Hallo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p style="margin:5px 0 0 0;">Status: <span class="badge"><?php echo htmlspecialchars($_SESSION['role']); ?></span></p>
        </div>
        <a href="logout.php" class="logout-btn">Ausloggen</a>
    </div>

    <?php if ($ist_admin): ?>
        <h2 style="color: #c0392b;">Admin-Bereich: Alle Benutzer</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vorname</th>
                    <th>Nachname</th>
                    <th>Username</th>
                    <th>Passwort (Hash)</th>
                    <th>Rolle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alle_mitglieder as $user): ?>
                    <tr>
                        <td><?php echo $user['WerkBenutzerID']; ?></td>
                        <td><?php echo htmlspecialchars($user['Vorname']); ?></td>
                        <td><?php echo htmlspecialchars($user['Nachname']); ?></td>
                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                        <td class="pw-cell"><?php echo $user['Passwort']; ?></td>
                        <td><?php echo $user['Rolle']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px;">
            <a href="geraete_verwaltung.php" style="color: #4a90e2;">➔ Zur Geräteverwaltung</a> | 
            <a href="logs_view.php" style="color: #4a90e2;">➔ System-Logs einsehen</a>
        </p>

    <?php else: ?>

        <h2>Meine absolvierten Schulungen</h2>
        <?php if (count($meine_schulungen) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Schulung</th>
                        <th>Kategorie</th>
                        <th>Abgeschlossen am</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meine_schulungen as $s): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['Bezeichnung']); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['MaschinenGruppe']); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($s['AbschlussDatum'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Du hast bisher noch keine Schulungen absolviert.</p>
        <?php endif; ?>

        <h2>Meine Maschinenberechtigungen</h2>
        <?php if (count($meine_maschinen) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Maschine</th>
                        <th>Standort / Raum</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meine_maschinen as $m): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($m['Bezeichnung']); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['Raum']); ?></td>
                            <td><span class="badge">Aktiv</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Du hast noch keine spezifischen Maschinenberechtigungen erhalten.</p>
        <?php endif; ?>

    <?php endif; ?>

</body>
</html>