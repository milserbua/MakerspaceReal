<?php
// export_logs.php
session_start();
require 'db.php';

// Nur Admins dürfen exportieren
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    die("Zugriff verweigert");
}

// Dateiname definieren
$filename = "system_logs_" . date('Y-m-d') . ".csv";

// Header senden, damit der Browser es als Datei-Download erkennt
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Datei-Handle öffnen
$output = fopen('php://output', 'w');

// BOM für Excel (damit Umlaute richtig angezeigt werden)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Überschriften
fputcsv($output, array('ID', 'Zeitpunkt', 'Admin/User', 'Username', 'Ereignis'), ';');

// Daten der letzten 30 Tage (oder alles, je nach Wunsch) holen
$sql = "SELECT l.LogID, l.Zeitpunkt, b.Nachname, b.Vorname, b.Username, l.Ereignis 
        FROM SystemLogs l
        JOIN Werkstattbenutzer b ON l.WerkBenutzerID = b.WerkBenutzerID
        ORDER BY l.Zeitpunkt DESC";
$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $fullName = $row['Nachname'] . ' ' . $row['Vorname'];
    fputcsv($output, array(
        $row['LogID'], 
        $row['Zeitpunkt'], 
        $fullName, 
        $row['Username'], 
        $row['Ereignis']
    ), ';');
}

fclose($output);
exit;
?>