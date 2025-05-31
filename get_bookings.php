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

// Préparer la requête SQL de base avec la nouvelle structure
$sql = "SELECT r.id, r.date_reservation, r.prix_total, r.statut, r.classe,
        u.nom AS client_nom, u.prenom AS client_prenom, u.email AS client_email,
        CONCAT(g1.nom, ' → ', g2.nom, ' (', DATE_FORMAT(t.date_heure_depart, '%d/%m/%Y %H:%i'), ')') AS trip_info,
        r.nb_passagers AS total_passengers
        FROM reservations r
        JOIN utilisateurs u ON r.utilisateur_id = u.id
        JOIN trajets t ON r.trajet_id = t.id
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        WHERE u.role = 'client'";

// Ajouter des filtres si nécessaire
$params = [];
$types = "";

// Filtre par date
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $sql .= " AND DATE(r.date_reservation) = ?";
    $params[] = $_GET['date'];
    $types .= "s";
}

// Filtre par statut
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $sql .= " AND r.statut = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Filtre par utilisateur
if (isset($_GET['user']) && !empty($_GET['user'])) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%" . $_GET['user'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Recherche
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR g1.nom LIKE ? OR g2.nom LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssss";
}

// Ajouter l'ordre
$sql .= " ORDER BY r.date_reservation DESC";

// Préparer et exécuter la requête
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Récupérer les résultats
$bookings = [];
while ($row = $result->fetch_assoc()) {
    // Formater les données pour l'affichage
    $row['client_name'] = $row['client_prenom'] . ' ' . $row['client_nom'];
    $bookings[] = $row;
}

// Fermer la connexion
$stmt->close();
$conn->close();

// Renvoyer les résultats en JSON
header('Content-Type: application/json');
echo json_encode($bookings);
?>
