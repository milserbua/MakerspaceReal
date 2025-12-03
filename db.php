<?php
// db.php
 
$host = 'localhost'; // Hier war der Fehler (jetzt korrigiert)
$dbname = 'makerspace'; // WICHTIG: Deine Datenbank muss in phpMyAdmin so heißen!
$user = 'root';
$pass = '';
 
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}
?>