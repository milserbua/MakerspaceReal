<?php
$host = "127.0.0.1"; // 127.0.0.1 ist oft stabiler als localhost
$user = "root";
$pass = "";
$db   = "makerspace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Verbindung fehlgeschlagen: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$datei = "liste.txt";
if (!file_exists($datei)) { die("Datei liste.txt nicht gefunden!"); }

$zeilen = file($datei, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$values = [];

foreach ($zeilen as $zeile) {
    $zeile = trim(str_replace("", "", $zeile));
    $teile = explode("-", $zeile);
    
    if (count($teile) >= 2) {
        $klasse = $conn->real_escape_string(trim($teile[0]));
        $nachname = $conn->real_escape_string(trim($teile[1]));
        $vorname = isset($teile[2]) ? $conn->real_escape_string(trim($teile[2])) : "";
        $username = $conn->real_escape_string(trim($vorname . " " . $nachname));
        $rolle = "mitglied";

        $values[] = "('$vorname', '$nachname', '$username', '$klasse', '$rolle')";
    }
}

if (!empty($values)) {
    // WICHTIG: Hier steht jetzt INSERT IGNORE
    $sql = "INSERT IGNORE INTO werkstattbenutzer (vorname, nachname, username, klasse, rolle) VALUES " . implode(", ", $values);
    
    if ($conn->query($sql)) {
        echo "<h3>Import abgeschlossen!</h3>";
        // affected_rows zeigt an, wie viele NEUE Zeilen wirklich hinzugefügt wurden
        echo "Es wurden <b>" . $conn->affected_rows . "</b> neue Benutzer hinzugefügt.";
    } else {
        echo "Fehler beim Import: " . $conn->error;
    }
}

$conn->close();
?>