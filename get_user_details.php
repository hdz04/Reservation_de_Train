<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si l'ID utilisateur est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID utilisateur invalide']);
    exit();
}

$userId = intval($_GET['id']);

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
    
    // Récupérer les informations de l'utilisateur
    $stmt = $conn->prepare("SELECT id, nom, prenom, email, telephone, date_inscription FROM utilisateurs WHERE id = ? AND role = 'client'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Récupérer les réservations de l'utilisateur
    $stmt = $conn->prepare("
        SELECT r.id, r.date_reservation, r.prix_total, r.statut, r.classe,
               t.date_heure_depart, t.date_heure_arrivee,
               gd.nom as gare_depart, ga.nom as gare_arrivee,
               tr.nom as train_nom, tr.numero as train_numero
        FROM reservations r
        JOIN trajets t ON r.trajet_id = t.id
        JOIN gares gd ON t.id_gare_depart = gd.id_gare
        JOIN gares ga ON t.id_gare_arrivee = ga.id_gare
        JOIN trains tr ON t.train_id = tr.id
        WHERE r.utilisateur_id = ?
        ORDER BY r.date_reservation DESC
    ");
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'user' => $user,
        'reservations' => $reservations
    ]);
    
} catch (Exception $e) {
    error_log("Erreur dans get_user_details.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>
