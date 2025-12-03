<?php
require 'db.php';

if (isset($_POST['raumnummer'])) {

    $raumnummer = trim($_POST['raumnummer']);

    if ($raumnummer === "") {
        exit("Fehler: Raumnummer darf nicht leer sein.");
    }

    $stmt = $conn->prepare("DELETE FROM raeume WHERE raumnummer = ?");
    $stmt->bind_param("s", $raumnummer);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "Raum erfolgreich gelöscht.";
        } else {
            echo "Kein Raum mit dieser Nummer gefunden.";
        }
    } else {
        echo "Fehler beim Löschen: " . $stmt->error;
    }

    $stmt->close();
}
?>
