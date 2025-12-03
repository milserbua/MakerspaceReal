<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Makerspace</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
       
        /* Tabellen Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
       
        /* Status Farben */
        .role-admin { color: red; font-weight: bold; }
        .role-teilnehmer { color: green; }
 
        /* Layout */
        .container { display: flex; gap: 20px; }
        .left-column { flex: 2; } /* Projekte bekommen mehr Platz */
        .right-column { flex: 1; } /* Mitgliederliste ist schmaler */
       
        .add-box { background: #eee; padding: 15px; border-radius: 5px; }
        .btn-delete { color: red; text-decoration: none; font-size: 0.8em; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
 
    <div class="top-bar">
        <h1>Hallo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <a href="logout.php" style="color:red;">Ausloggen</a>
    </div>
 
 
    <div class="container">
 
        <!-- RECHTE SPALTE: Mitgliederliste (Das ist neu!) -->
        <div class="right-column">
            <h3>Mitgliederliste</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Klasse</th>
                        <th>Tel. Nummer</th>
                        <th>Rolle</th>
                        <th>Freigabestufe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alle_mitglieder as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['id']); ?></td>
                            <td><?php echo htmlspecialchars($m['username']); ?></td>
                            <td><?php echo htmlspecialchars($m['klasse']); ?></td>
                            <td><?php echo htmlspecialchars($m['telnum']); ?></td>
                            <td><?php echo htmlspecialchars($m['role']); ?></td>
                            <td><?php echo htmlspecialchars($m['access']); ?></td>
                            <td>
                                <!-- Hier prüfen wir, welche Rolle der User hat für die Farbe -->
                                <?php if ($m['role'] == 'Admin'): ?>
                                    <span class="role-admin">Admin</span>
                                <?php else: ?>
                                    <span class="role-teilnehmer">Teilnehmer</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
 
    </div>
 
</body>
</html>