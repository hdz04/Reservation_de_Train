<?php
// Activer l'affichage des erreurs pour le diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

error_log("mark_notifications_read.php: Début du traitement de la demande");

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Erreur: Utilisateur non connecté");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit();
}

$userId = $_SESSION['user_id'];
error_log("ID utilisateur: $userId");

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
    
    // Marquer toutes les notifications comme lues pour cet utilisateur
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE (utilisateur_id = ? OR utilisateur_id IS NULL) AND is_read = 0");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de requête: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la mise à jour: ' . $stmt->error);
    }
    
    $affectedRows = $stmt->affected_rows;
    error_log("$affectedRows notifications marquées comme lues");
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => "Toutes les notifications ont été marquées comme lues ($affectedRows notifications)"
    ]);
    
} catch(Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>
