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

// Traiter la suppression d'une réservation
if (isset($_POST['delete']) && !empty($_POST['delete'])) {
    $bookingId = intval($_POST['delete']);
    
    // Début de la transaction
    $conn->begin_transaction();
    
    try {
        // Supprimer les billets associés
        $sql = "DELETE FROM billets WHERE reservation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // Supprimer les paiements associés
        $sql = "DELETE FROM paiements WHERE reservation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // Supprimer les demandes de remboursement associées
        $sql = "DELETE FROM demandes_remboursement WHERE reservation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // Supprimer la réservation
        $sql = "DELETE FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // Commit la transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        // Rollback en cas d'erreur
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Traiter la mise à jour d'une réservation
if (isset($_POST['booking_id']) && !empty($_POST['booking_id'])) {
    $bookingId = intval($_POST['booking_id']);
    $userId = intval($_POST['user_id']);
    $tripId = intval($_POST['trip_id']);
    $nbPassagers = intval($_POST['nb_passagers']);
    $classType = $_POST['class_type'];
    $bookingStatus = $_POST['booking_status'];
    $paymentMethod = $_POST['payment_method'];
    
    // Début de la transaction
    $conn->begin_transaction();
    
    try {
        // Récupérer le prix du trajet
        $sql = "SELECT prix FROM trajets WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $tripId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tripPrice = $result->fetch_assoc()['prix'];
        $stmt->close();
        
        // Calculer le nouveau prix total
        $totalPrice = $tripPrice * $nbPassagers;
        
        // Appliquer un supplément pour la première classe
        if ($classType === 'premiere') {
            $totalPrice *= 1.5; // 50% de plus pour la première classe
        }
        
        // Mettre à jour la réservation
        $sql = "UPDATE reservations SET 
                utilisateur_id = ?, 
                trajet_id = ?, 
                nb_passagers = ?, 
                classe = ?, 
                prix_total = ?, 
                statut = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisdsi", $userId, $tripId, $nbPassagers, $classType, $totalPrice, $bookingStatus, $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // Mettre à jour le paiement associé
        $sql = "UPDATE paiements SET 
                utilisateur_id = ?, 
                montant = ?, 
                methode = ? 
                WHERE reservation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idsi", $userId, $totalPrice, $paymentMethod, $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // Commit la transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        // Rollback en cas d'erreur
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Si on arrive ici, c'est qu'aucune action n'a été effectuée
header('Content-Type: application/json');
echo json_encode(['error' => 'Aucune action spécifiée']);
?>
