<?php

function getVehicules($conn) {
    $stmt = $conn->prepare(
        "SELECT vehicules_id, matricule_vehicule, type_vehicule FROM vehicules ORDER BY matricule_vehicule"
    );

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>