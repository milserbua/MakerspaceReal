<?php
session_start();
session_destroy(); // Zerstört das VIP-Bändchen (alle Session-Daten weg)
header("Location: login.php"); // Zurück zum Login
exit;
?>