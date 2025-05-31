<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si l'ID du train est fourni
if (!isset($_POST['train_id']) || !is_numeric($_POST['train_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de train invalide']);
    exit();
}

$train_id = intval($_POST['train_id']);
$action = isset($_POST['action']) ? $_POST['action'] : '';

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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Train non trouvé']);
        exit();
    }
    
    if ($action === 'disable') {
        // Mettre le train hors service
        $stmt = $conn->prepare("UPDATE trains SET statut = 'retired' WHERE id = :id");
        $stmt->bindParam(':id', $train_id);
        $stmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Train mis hors service avec succès']);
    } elseif ($action === 'changeStatus') {
        // Changer le statut du train
        $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';
        
        // Vérifier que le statut est valide
        if (!in_array($new_status, ['active', 'retired'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Statut invalide']);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE trains SET statut = :status WHERE id = :id");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $train_id);
        $stmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Statut du train mis à jour avec succès']);
    } else {
        // Action non reconnue
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
    }
    
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()]);
}

// Fermer la connexion
$conn = null;
?>
