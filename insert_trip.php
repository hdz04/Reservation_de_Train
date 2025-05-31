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

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
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

// Récupérer les données du formulaire
$idGareDepart = isset($_POST['gare_depart']) ? intval($_POST['gare_depart']) : 0;
$idGareArrivee = isset($_POST['gare_arrivee']) ? intval($_POST['gare_arrivee']) : 0;
$dateHeureDepart = isset($_POST['date_heure_depart']) ? $_POST['date_heure_depart'] : '';
$dateHeureArrivee = isset($_POST['date_heure_arrivee']) ? $_POST['date_heure_arrivee'] : '';
$train_id = isset($_POST['train_id']) ? intval($_POST['train_id']) : 0;
$prix = isset($_POST['Prix']) ? floatval($_POST['Prix']) : 0;
$economique = isset($_POST['economique']) ? intval($_POST['economique']) : 0;
$premiereClasse = isset($_POST['premiere_classe']) ? intval($_POST['premiere_classe']) : 0;

// Convertir les dates au format MySQL (YYYY-MM-DD HH:MM:SS)
$dateHeureDepart = date('Y-m-d H:i:s', strtotime($dateHeureDepart));
$dateHeureArrivee = date('Y-m-d H:i:s', strtotime($dateHeureArrivee));

// Valider les données
if ($idGareDepart <= 0 || $idGareArrivee <= 0 || empty($dateHeureDepart) || empty($dateHeureArrivee) || $train_id <= 0 || $prix <= 0 || $economique < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Tous les champs obligatoires doivent être remplis correctement']);
    exit();
}

// Vérifier si les gares existent
$stmt = $conn->prepare("SELECT id_gare FROM gares WHERE id_gare = ?");
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur de préparation de la requête gare départ: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $idGareDepart);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Gare de départ non trouvée (ID: ' . $idGareDepart . ')']);
    exit();
}
$stmt->close();

$stmt = $conn->prepare("SELECT id_gare FROM gares WHERE id_gare = ?");
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur de préparation de la requête gare arrivée: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $idGareArrivee);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Gare d\'arrivée non trouvée (ID: ' . $idGareArrivee . ')']);
    exit();
}
$stmt->close();

// Vérifier si le train existe
$stmt = $conn->prepare("SELECT id FROM trains WHERE id = ?");
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur de préparation de la requête train: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $train_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Train non trouvé']);
    exit();
}
$stmt->close();

// Préparer la requête SQL avec les noms de colonnes corrects
$sql = "INSERT INTO trajets (train_id, id_gare_depart, id_gare_arrivee, date_heure_depart, date_heure_arrivee, prix, economique, premiere_classe, statut) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur de préparation de la requête d\'insertion: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iiissdii", $train_id, $idGareDepart, $idGareArrivee, $dateHeureDepart, $dateHeureArrivee, $prix, $economique, $premiereClasse);

// Exécuter la requête
if ($stmt->execute()) {
    $tripId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Trajet ajouté avec succès', 'id' => $tripId]);
    exit();
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'ajout du trajet: ' . $error]);
    exit();
}
?>
