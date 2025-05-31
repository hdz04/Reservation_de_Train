<?php
// Démarrer la session
session_start();

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
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

// Journaliser les données reçues pour le débogage
error_log("POST data: " . print_r($_POST, true));

// Traiter la suppression d'un trajet
if (isset($_POST['delete'])) {
    $tripId = intval($_POST['delete']);
    
    // Vérifier si le trajet existe
    $stmt = $conn->prepare("SELECT id FROM trajets WHERE id = ?");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Trajet non trouvé']);
        exit();
    }
    $stmt->close();
    
    // Vérifier si le trajet a des réservations
    $stmt = $conn->prepare("SELECT id FROM reservations WHERE trajet_id = ? LIMIT 1");
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur de préparation de la requête: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Si le trajet a des réservations, on le marque comme annulé au lieu de le supprimer
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE trajets SET statut = 'annulee' WHERE id = ?");
        $stmt->bind_param("i", $tripId);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Trajet annulé car il a des réservations']);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'annulation du trajet: ' . $stmt->error]);
            exit();
        }
    } else {
        // Si le trajet n'a pas de réservations, on peut le supprimer
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM trajets WHERE id = ?");
        $stmt->bind_param("i", $tripId);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Trajet supprimé avec succès']);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression du trajet: ' . $stmt->error]);
            exit();
        }
    }
}

// Traiter la mise à jour d'un trajet
if (isset($_POST['UpdateTrip'])) {
    $tripId = isset($_POST['trip_id']) ? intval($_POST['trip_id']) : 0;
    $idGareDepart = isset($_POST['gare_depart']) ? intval($_POST['gare_depart']) : 0;
    $idGareArrivee = isset($_POST['gare_arrivee']) ? intval($_POST['gare_arrivee']) : 0;
    $dateHeureDepart = isset($_POST['date_heure_depart']) ? $_POST['date_heure_depart'] : '';
    $dateHeureArrivee = isset($_POST['date_heure_arrivee']) ? $_POST['date_heure_arrivee'] : '';
    $trainId = isset($_POST['train_id']) ? intval($_POST['train_id']) : 0;
    $prix = isset($_POST['Prix']) ? floatval($_POST['Prix']) : 0;
    $economique = isset($_POST['economique']) ? intval($_POST['economique']) : 0;
    $premiereClasse = isset($_POST['premiere_classe']) ? intval($_POST['premiere_classe']) : 0;
    $statut = isset($_POST['statut']) ? $_POST['statut'] : 'active';
    
    // Convertir les dates au format MySQL (YYYY-MM-DD HH:MM:SS)
    $dateHeureDepart = date('Y-m-d H:i:s', strtotime($dateHeureDepart));
    $dateHeureArrivee = date('Y-m-d H:i:s', strtotime($dateHeureArrivee));
    
    // Valider les données
    if ($tripId <= 0 || $idGareDepart <= 0 || $idGareArrivee <= 0 || empty($dateHeureDepart) || empty($dateHeureArrivee) || $trainId <= 0 || $prix <= 0 || $economique < 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Tous les champs obligatoires doivent être remplis correctement',
            'data' => [
                'tripId' => $tripId,
                'idGareDepart' => $idGareDepart,
                'idGareArrivee' => $idGareArrivee,
                'dateHeureDepart' => $dateHeureDepart,
                'dateHeureArrivee' => $dateHeureArrivee,
                'trainId' => $trainId,
                'prix' => $prix,
                'economique' => $economique
            ]
        ]);
        exit();
    }
    
    // Mettre à jour le trajet
    $sql = "UPDATE trajets SET 
            id_gare_depart = ?, 
            id_gare_arrivee = ?, 
            date_heure_depart = ?, 
            date_heure_arrivee = ?, 
            train_id = ?, 
            prix = ?, 
            economique = ?, 
            premiere_classe = ?, 
            statut = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur de préparation de la requête: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("iissidissi", $idGareDepart, $idGareArrivee, $dateHeureDepart, $dateHeureArrivee, $trainId, $prix, $economique, $premiereClasse, $statut, $tripId);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Trajet mis à jour avec succès']);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour du trajet: ' . $stmt->error]);
        exit();
    }
}

// Traiter le changement de statut d'un trajet
if (isset($_POST['action']) && $_POST['action'] === 'changeStatus') {
    $tripId = isset($_POST['trip_id']) ? intval($_POST['trip_id']) : 0;
    $newStatus = isset($_POST['new_status']) ? $_POST['new_status'] : '';
    
    // Valider les données
    if ($tripId <= 0 || !in_array($newStatus, ['active', 'annulee', 'terminee'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit();
    }
    
    // Vérifier si le trajet existe
    $stmt = $conn->prepare("SELECT id FROM trajets WHERE id = ?");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Trajet non trouvé']);
        exit();
    }
    $stmt->close();
    
    // Mettre à jour le statut du trajet
    $stmt = $conn->prepare("UPDATE trajets SET statut = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $tripId);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Statut du trajet mis à jour avec succès']);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour du statut: ' . $stmt->error]);
        exit();
    }
}

// Si aucune action n'est spécifiée
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Aucune action spécifiée']);
exit();
?>
