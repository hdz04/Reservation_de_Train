<?php
// Activer l'affichage des erreurs pour le diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Enregistrer la tentative
error_log("process_cancel_request.php: Début du traitement de la demande");
error_log("Données POST: " . print_r($_POST, true));

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    error_log("Erreur: Utilisateur non autorisé");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Erreur: La méthode n'est pas POST");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données du formulaire
$requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$clientId = isset($_POST['utilisateur_id']) ? intval($_POST['utilisateur_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$montantRembourse = isset($_POST['montant_rembourse']) ? floatval($_POST['montant_rembourse']) : 0;

error_log("Données reçues: requestId=$requestId, clientId=$clientId, action=$action");

// Valider les données
if ($requestId <= 0 || $clientId <= 0 || empty($action) || empty($message)) {
    error_log("Erreur: Données invalides");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit();
}

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Vérifier la connexion
    if ($conn->connect_error) {
        throw new Exception('Erreur de connexion: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
    // Début de la transaction
    $conn->begin_transaction();
    
    error_log("Début de la transaction");
    
    // Récupérer les informations de la demande
    $stmt = $conn->prepare("SELECT reservation_id, statut FROM demandes_remboursement WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de requête: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Demande non trouvée');
    }
    
    $row = $result->fetch_assoc();
    $reservationId = $row['reservation_id'];
    
    error_log("Demande trouvée: reservation_id=$reservationId");
    
    // Vérifier que la demande est en attente
    if ($row['statut'] !== 'pending') {
        throw new Exception('Cette demande a déjà été traitée');
    }
    
    // Mettre à jour le statut de la demande
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE demandes_remboursement SET 
                            statut = ?, 
                            date_traitement = NOW(), 
                            montant_rembourse = ?
                            WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de requête UPDATE: ' . $conn->error);
    }
    
    $stmt->bind_param("sdi", $newStatus, $montantRembourse, $requestId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la mise à jour de la demande: ' . $stmt->error);
    }
    
    error_log("Statut de la demande mis à jour vers: $newStatus");
    
    // Mettre à jour le statut de la réservation si approuvé
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Erreur de préparation de requête UPDATE reservations: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $reservationId);
        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de la mise à jour de la réservation: ' . $stmt->error);
        }
        
        // Mettre à jour le statut des billets
        $stmt = $conn->prepare("UPDATE billets SET statut = 'annule' WHERE reservation_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();
        }
        
        error_log("Statut de la réservation et des billets mis à jour vers annulé");
    }
    
    // Créer une notification pour l'utilisateur
    $notificationType = ($action === 'approve') ? 'success' : 'warning';
    $notificationTitle = ($action === 'approve') ? 'Demande d\'annulation approuvée' : 'Demande d\'annulation refusée';
    
    // Ajouter les informations de réponse au message
    $fullMessage = $message;
    if ($action === 'approve' && $montantRembourse > 0) {
        $fullMessage .= "\n\nMontant du remboursement: " . number_format($montantRembourse, 2, ',', ' ') . " DA";
    }
    
    $stmt = $conn->prepare("INSERT INTO notifications (utilisateur_id, content, date_creation, is_read) VALUES (?, ?, NOW(), 0)");
    if (!$stmt) {
        throw new Exception('Erreur de préparation de requête INSERT notifications: ' . $conn->error);
    }
    
    $stmt->bind_param("is", $clientId, $fullMessage);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de la création de la notification: ' . $stmt->error);
    }
    
    error_log("Notification créée pour le client: $clientId");
    
    // Valider la transaction
    $conn->commit();
    
    error_log("Transaction terminée avec succès");
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'La demande a été traitée avec succès et une notification a été envoyée au client.'
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    error_log("Erreur: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Fermer la connexion
if (isset($conn)) {
    $conn->close();
}
?>
