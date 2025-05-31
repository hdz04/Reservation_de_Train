<?php
// Paramètres de connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

// En-têtes pour permettre les requêtes AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Créer une connexion à la base de données
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8");

    // Vérifier la connexion
    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion à la base de données: " . $conn->connect_error);
    }

    // Requête SQL pour récupérer toutes les gares
    $sql = "SELECT id_gare, nom, ville FROM gares ORDER BY ville ASC, nom ASC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Erreur lors de l'exécution de la requête: " . $conn->error);
    }

    // Récupérer les résultats
    $gares = array();
    while ($row = $result->fetch_assoc()) {
        $gares[] = array(
            'id_gare' => $row['id_gare'],
            'nom' => $row['nom'],
            'ville' => $row['ville']
        );
    }

    // Fermer la connexion
    $conn->close();

    // Renvoyer les gares au format JSON
    echo json_encode($gares);

} catch (Exception $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
?>
