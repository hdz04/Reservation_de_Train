<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si le formulaire a été soumis
if (!isset($_POST['AddBooking'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Requête invalide']);
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
    error_log("Erreur de connexion à la base de données: " . $conn->connect_error);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit();
}

// Récupérer et valider les données du formulaire
$clientId = filter_input(INPUT_POST, 'utilisateur_id', FILTER_VALIDATE_INT);
$trajetId = filter_input(INPUT_POST, 'trajet_id', FILTER_VALIDATE_INT);
$nbPassagers = filter_input(INPUT_POST, 'nb_passagers', FILTER_VALIDATE_INT);
$classe = filter_input(INPUT_POST, 'classe', FILTER_SANITIZE_SPECIAL_CHARS);
$statut = filter_input(INPUT_POST, 'statut', FILTER_SANITIZE_SPECIAL_CHARS);
$methode = filter_input(INPUT_POST, 'methode_paiement', FILTER_SANITIZE_SPECIAL_CHARS);

// Validation des données
if ($clientId === false || $clientId <= 0 || 
    $trajetId === false || $trajetId <= 0 || 
    $nbPassagers === false || $nbPassagers <= 0 ||
    !in_array($classe, ['economique', 'premiere']) ||
    !in_array($statut, ['confirmee', 'annulee', 'terminee']) ||
    !in_array($methode, ['carte', 'Edahabia'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Données invalides']);
    exit();
}

// Récupérer le prix du trajet
$sql = "SELECT prix FROM trajets WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trajetId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Trajet non trouvé']);
    exit();
}

$trajet = $result->fetch_assoc();
$basePrice = floatval($trajet['prix']);

// Calculer le prix total
$totalPrice = $basePrice * $nbPassagers;

// Appliquer un supplément pour la première classe
if ($classe === 'premiere') {
    $totalPrice *= 1.5; // 50% de plus pour la première classe
}

// Insérer la réservation dans la base de données
$sql = "INSERT INTO reservations (utilisateur_id, trajet_id, date_reservation, nb_passagers, classe, prix_total, statut) 
        VALUES (?, ?, NOW(), ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiisds", $clientId, $trajetId, $nbPassagers, $classe, $totalPrice, $statut);

if ($stmt->execute()) {
    $reservationId = $stmt->insert_id;
    
    // Créer un paiement pour cette réservation
    $sqlPaiement = "INSERT INTO paiements (reservation_id, utilisateur_id, montant, methode, date_paiement) 
                    VALUES (?, ?, ?, ?, NOW())";
    $stmtPaiement = $conn->prepare($sqlPaiement);
    $stmtPaiement->bind_param("iids", $reservationId, $clientId, $totalPrice, $methode);
    $stmtPaiement->execute();
    $stmtPaiement->close();
    
    // Journaliser l'opération réussie
    error_log("Nouvelle réservation créée: ID=$reservationId, Client=$clientId, Trajet=$trajetId");
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'reservation_id' => $reservationId]);
} else {
    // Journaliser l'erreur
    error_log("Erreur lors de l'ajout de la réservation: " . $stmt->error);
    
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de l\'ajout de la réservation']);
}

$stmt->close();
$conn->close();
?>
