<?php
session_start();
require 'db.php';
 
// 1. Sicherheits-Check
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}
 
$user_id = $_SESSION['userid'];
$nachricht = "";
 
// --- NEU: 5. LOGIK: Alle Mitglieder laden ---
// Wir holen Benutzername und Rolle von ALLEN Usern
$sql_users = "SELECT username, role, created_at FROM users ORDER BY created_at DESC";
$stmt_users = $pdo->query($sql_users);
$alle_mitglieder = $stmt_users->fetchAll();
// --------------------------------------------
 
include 'dashboard_view.php';
?>
 