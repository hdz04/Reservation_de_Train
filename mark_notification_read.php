<?php
// Activer l'affichage des erreurs pour le diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

error_log("mark_notification_read.php: Début du traitement de la demande");

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Erreur: Utilisateur non connecté");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit();
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);
error_log("Données reçues: " . print_r($input, true));

// Vérifier si l'ID de notification est fourni
if (!isset($input['id']) || !is_numeric($input['id'])) {
    error_log("Erreur: ID de notification invalide");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de notification invalide']);
    exit();
}

$notificationId = intval($input['id']);
$userId = $_SESSION['user_id'];

error_log("ID de notification: $notificationId, ID utilisateur: $userId");

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Erreur de connexion: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
    // Vérifier que la notification appartient bien à l'utilisateur ou est générale
    $stmt = $conn->prepare("SELECT id, is_read FROM notifications WHERE id = ? AND (utilisateur_id = ? OR utilisateur_id IS NULL)");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de requête: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Erreur: Notification non trouvée ou non autorisée");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Notification non trouvée ou non autorisée']);
        exit();
    }
    
    $notification = $result->fetch_assoc();
    
    // Marquer la notification comme lue seulement si elle n'est pas déjà lue
    if ($notification['is_read'] == 0) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Erreur de préparation de requête UPDATE: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $notificationId);
        
        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de la mise à jour: ' . $stmt->error);
        }
        
        error_log("Notification marquée comme lue");
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Notification marquée comme lue']);
    
} catch(Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>
