<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si l'ID de la réservation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de réservation non fourni']);
    exit();
}

$bookingId = intval($_GET['id']);

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit();
}

// Récupérer les détails de la réservation avec la nouvelle structure
$sql = "SELECT r.id, r.utilisateur_id, r.date_reservation, r.prix_total, r.statut, 
        r.nb_passagers, r.classe, r.trajet_id
        FROM reservations r
        WHERE r.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Réservation non trouvée']);
    exit();
}

$booking = $result->fetch_assoc();

// Fermer la connexion
$stmt->close();
$conn->close();

// Renvoyer les résultats en JSON
header('Content-Type: application/json');
echo json_encode($booking);
?>
