<?php
// 1. Verbindung zur Datenbank holen
require 'db.php';

$nachricht = ""; // Hier speichern wir Feedback für den User

// 2. Wurde der Knopf gedrückt?
if (isset($_POST['register_btn'])) {
    
    // Daten aus dem Formular holen
    $user = $_POST['username']; 
    $pass_klartext = $_POST['password'];

    // Prüfen ob Felder ausgefüllt sind
    if (!empty($user) && !empty($pass_klartext)) {
        
        // Passwort verschlüsseln
        $pass_hash = password_hash($pass_klartext, PASSWORD_DEFAULT);

        // SQL vorbereiten
        $sql = "INSERT INTO users (username, password) VALUES (:name, :pw)";
        $stmt = $pdo->prepare($sql);

        try {
            // SQL ausführen
            $stmt->execute(['name' => $user, 'pw' => $pass_hash]);
            $nachricht = "Erfolgreich registriert! <a href='login.php'>Zum Login</a>";
        } catch (PDOException $e) {
            // Fehlercode 23000 = Benutzername schon vergeben
            if ($e->getCode() == 23000) {
                $nachricht = "Der Name ist leider schon vergeben.";
            } else {
                $nachricht = "Fehler: " . $e->getMessage();
            }
        }

    } else {
        $nachricht = "Bitte alle Felder ausfüllen!";
    }
}

// WICHTIG: Ganz am Ende laden wir die HTML-Ansicht!
// Ohne diese Zeile bleibt die Seite weiß.
include 'register_view.php';
?>