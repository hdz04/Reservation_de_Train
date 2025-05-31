<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$tripId = isset($data['id']) ? intval($data['id']) : 0;
$newStatus = isset($data['status']) ? $data['status'] : '';

// Valider les données
if ($tripId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de trajet invalide']);
    exit();
}

if (!in_array($newStatus, ['active', 'annulee', 'terminee'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Statut invalide']);
    exit();
}

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
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données: ' . $conn->connect_error]);
    exit();
}

// Mettre à jour le statut du trajet
$stmt = $conn->prepare("UPDATE trajets SET statut = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $tripId);
$stmt->execute();

// Vérifier si la mise à jour a réussi
if ($stmt->affected_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Trajet non trouvé ou aucune modification apportée']);
}

// Fermer la connexion
$stmt->close();
$conn->close();
?>
