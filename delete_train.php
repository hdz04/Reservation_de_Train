<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Renvoyer une réponse JSON d'erreur
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si l'ID du train est fourni
if (!isset($_POST['train_id']) || !is_numeric($_POST['train_id'])) {
    // Renvoyer une réponse JSON d'erreur
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de train invalide']);
    exit();
}

$train_id = intval($_POST['train_id']);

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si le train existe
    $stmt = $conn->prepare("SELECT id FROM trains WHERE id = :id");
    $stmt->bindParam(':id', $train_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Renvoyer une réponse JSON d'erreur
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Train non trouvé']);
        exit();
    }
    
    // Vérifier si le train est utilisé dans des trajets
    $stmt = $conn->prepare("SELECT id FROM trajets WHERE train_id = :train_id LIMIT 1");
    $stmt->bindParam(':train_id', $train_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Renvoyer une réponse JSON d'erreur
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Impossible de supprimer ce train car il est utilisé dans des trajets']);
        exit();
    }
    
    // Supprimer le train
    $stmt = $conn->prepare("DELETE FROM trains WHERE id = :id");
    $stmt->bindParam(':id', $train_id);
    $stmt->execute();
    
    // Renvoyer une réponse JSON de succès
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Train supprimé avec succès']);
    
} catch(PDOException $e) {
    // Renvoyer une réponse JSON d'erreur
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()]);
}

// Fermer la connexion
$conn = null;
?>
