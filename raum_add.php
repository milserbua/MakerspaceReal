<?php
require 'db.php';

if (isset($_POST['raumnummer'])) {

    $raumnummer = trim($_POST['raumnummer']);

    if ($raumnummer === "") {
        exit("Fehler: Raumnummer darf nicht leer sein.");
    }

    $stmt = $conn->prepare("INSERT INTO raeume (raumnummer) VALUES (?)");
    $stmt->bind_param("s", $raumnummer);

    if ($stmt->execute()) {
        echo "Raum erfolgreich hinzugefügt.";
    } else {
        echo "Fehler beim Hinzufügen: " . $stmt->error;
    }

    $stmt->close();
}
?>
