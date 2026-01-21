<?php
// 1. Session starten
session_start();

require 'db.php'; // Verbindung zur Datenbank 'makerspace'

$nachricht = "";

// 2. Wurde der Login-Button gedrückt?
if (isset($_POST['login_btn'])) {
    
    $user_input = $_POST['username']; 
    $pass_input = $_POST['password']; 

    // 3. User in der Datenbank suchen
    $sql = "SELECT * FROM Werkstattbenutzer WHERE Username = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $user_input]);
    
    // 4. Daten abrufen
    $user_db = $stmt->fetch(); 

    // 5. Passwort prüfen
    if ($user_db && password_verify($pass_input, $user_db['Passwort'])) {

        
        
        // --- LOGIN ERFOLGREICH ---

        // Session-Daten setzen
        $_SESSION['userid'] = $user_db['WerkBenutzerID'];
        $_SESSION['username'] = $user_db['Username'];
        $_SESSION['role'] = $user_db['Rolle'];
        
        // NEU: Erfolgreichen Login in SystemLogs speichern
        $log_sql = "INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, 'Login erfolgreich')";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([$user_db['WerkBenutzerID']]);

        // Weiche: Admin oder normales Dashboard
        if ($user_db['Rolle'] === 'Admin') {
            header("Location: dashboard_admin_view.php");
        } else {
            header("Location: dashboardBenutzer.php");
        }
        exit; 

    } else {
        // --- LOGIN FEHLGESCHLAGEN ---

        // Optional: Wenn der User existiert, aber das PW falsch war, loggen wir den Versuch
        if ($user_db) {
            $log_sql = "INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, 'FEHLVERSUCH: Passwort falsch')";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([$user_db['WerkBenutzerID']]);
        }

        $nachricht = "Benutzername oder Passwort falsch.";
    }
}

// Ansicht laden
include 'login_view.php';
?>