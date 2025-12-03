<?php
session_start();
require 'db.php';

// Sicherheits-Check
if (!isset($_SESSION['userid'])) {
    header("Location: login_view.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Makerspace</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        .role-admin { color: red; font-weight: bold; }
        .role-teilnehmer { color: green; }
    </style>
</head>
<body>
    <div class="top-bar">
        <h1>Hallo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <a href="logout.php" style="color:red;">Ausloggen</a>
    </div>

    <p>
        Deine Rolle:
        <?php if ($_SESSION['role'] === 'Admin'): ?>
            <span class="role-admin">Admin</span>
        <?php else: ?>
            <span class="role-teilnehmer">Teilnehmer</span>
        <?php endif; ?>
    </p>

    <p>Freigabestufe: <?php echo htmlspecialchars($_SESSION['access']); ?></p>
</body>
</html>
