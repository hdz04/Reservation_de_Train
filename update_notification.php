<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
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
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit();
}

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Vérifier l'action à effectuer
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['notification_id'])) {
    // Supprimer la notification
    $notification_id = intval($_POST['notification_id']);
    
    $sql = "DELETE FROM notifications WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notification_id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Notification supprimée avec succès']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erreur lors de la suppression de la notification: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Action non valide']);
}

// Fermer la connexion
$conn->close();
?>
