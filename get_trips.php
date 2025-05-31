<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur pour certaines fonctionnalités
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

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
    echo json_encode(['error' => 'Erreur de connexion à la base de données: ' . $conn->connect_error]);
    exit();
}

// Lire les filtres éventuels
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$statut = isset($_GET['statut']) ? $conn->real_escape_string($_GET['statut']) : '';

// Construire la requête SQL
$sql = "SELECT t.id, g1.nom as gare_depart, g2.nom as gare_arrivee, 
        t.date_heure_depart, t.statut 
        FROM trajets t
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        WHERE 1";

if (!empty($search)) {
    $sql .= " AND (g1.nom LIKE '%$search%' OR g2.nom LIKE '%$search%')";
}

if (!empty($statut)) {
    $sql .= " AND t.statut = '$statut'";
}

$sql .= " ORDER BY t.date_heure_depart DESC";

$result = $conn->query($sql);

$trips = [];
while ($row = $result->fetch_assoc()) {
    $trips[] = $row;
}

header('Content-Type: application/json');
echo json_encode($trips);
$conn->close();
?>
