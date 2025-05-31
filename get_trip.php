<?php
header('Content-Type: application/json');
session_start();

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Erreur de connexion: ' . $conn->connect_error]);
    exit;
}

// Récupérer l'ID du trajet
$tripId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($tripId <= 0) {
    echo json_encode(['error' => 'ID de trajet invalide']);
    exit;
}

// Construire la requête SQL avec la nouvelle structure
$sql = "SELECT t.id, t.id_gare_depart, t.id_gare_arrivee, 
        g1.nom as gare_depart_nom, g2.nom as gare_arrivee_nom,
        t.date_heure_depart, t.date_heure_arrivee, 
        t.train_id, tr.nom as train_nom, 
        t.prix, t.economique, t.premiere_classe, t.statut
        FROM trajets t
        JOIN trains tr ON t.train_id = tr.id
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        WHERE t.id = ?";

// Préparer la requête
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tripId);

// Exécuter la requête
$stmt->execute();
$result = $stmt->get_result();

// Récupérer le résultat
if ($result->num_rows > 0) {
    $trip = $result->fetch_assoc();
    
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Renvoyer le résultat au format JSON
    echo json_encode($trip);
} else {
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Renvoyer une erreur
    echo json_encode(['error' => 'Trajet non trouvé']);
}
?>
