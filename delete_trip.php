<?php
session_start();

// Autorisation : vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Lire les données JSON envoyées
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier si un ID est fourni
if (!isset($data['id']) || !is_numeric($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de trajet invalide']);
    exit();
}

$tripId = intval($data['id']);

// Connexion à la base de données
$conn = new mysqli('localhost', 'root', '', 'train');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit();
}

// Vérifier s'il existe des réservations associées
$check = $conn->prepare("SELECT id FROM reservations WHERE trajet_id = ? LIMIT 1");
$check->bind_param("i", $tripId);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // S'il y a des réservations, on annule le trajet au lieu de le supprimer
    $check->close();
    $update = $conn->prepare("UPDATE trajets SET statut = 'annulee' WHERE id = ?");
    $update->bind_param("i", $tripId);
    if ($update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Le trajet a été annulé car des réservations y sont associées']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'annulation du trajet']);
    }
    $update->close();
} else {
    // S'il n'y a pas de réservations, on supprime le trajet
    $check->close();
    $delete = $conn->prepare("DELETE FROM trajets WHERE id = ?");
    $delete->bind_param("i", $tripId);
    if ($delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Trajet supprimé avec succès']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression du trajet']);
    }
    $delete->close();
}

$conn->close();
exit();
?>
