<?php
require 'db.php';

// Wir setzen das Passwort für den User 'admin' auf '1234'
$neues_passwort = '1234';
$hash = password_hash($neues_passwort, PASSWORD_DEFAULT);

$sql = "UPDATE Werkstattbenutzer SET Passwort = :pw WHERE Username = 'admin'";
$stmt = $pdo->prepare($sql);

if($stmt->execute(['pw' => $hash])) {
    echo "Passwort erfolgreich auf '1234' geändert. Neuer Hash: " . $hash;
} else {
    echo "Fehler beim Update.";
    print_r($stmt->errorInfo());
}
?>