<?php
// 1. Session starten (WICHTIG: Muss immer ganz oben stehen!)
// Das aktiviert das Gedächtnis des Servers.
session_start();
 
require 'db.php';
 
$nachricht = "";
 
// 2. Wurde der Login-Button gedrückt?
if (isset($_POST['login_btn'])) {
   
    $user_input = $_POST['username'];
    $pass_input = $_POST['password'];
 
    // 3. User in der Datenbank suchen
    $sql = "SELECT * FROM users WHERE username = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $user_input]);
   
    // 4. Daten abrufen
    // fetch() holt genau eine Zeile aus der Datenbank
    $user_db = $stmt->fetch();
 
    // 5. Passwort prüfen
    // Wir prüfen zwei Dinge:
    // a) Wurde ein User gefunden? ($user_db ist nicht false)
    // b) Stimmt das Passwort? (password_verify vergleicht Eingabe mit Hash)
    if ($user_db && password_verify($pass_input, $user_db['password'])) {
       
        // Wir schreiben Daten in die Session (das VIP-Bändchen)
        $_SESSION['userid'] = $user_db['id'];
        $_SESSION['username'] = $user_db['username'];
       
        // Weiterleitung zur geheimen Seite
        header("Location: dashboard.php");
        exit; // Wichtig: Skript hier beenden
 
    } else {
        // Login fehlgeschlagen
        $nachricht = "Benutzername oder Passwort falsch.";
    }
}
 
// Ansicht laden
include 'login_view.php';
?>