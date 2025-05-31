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

// Préparer la requête SQL de base
$sql = "SELECT * FROM trains";
$params = [];
$types = "";

// Ajouter des filtres si nécessaires
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $sql .= " WHERE (numero LIKE ? OR nom LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
    
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $sql .= " AND statut = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }
} else if (isset($_GET['status']) && !empty($_GET['status'])) {
    $sql .= " WHERE statut = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Ajouter un ordre de tri
$sql .= " ORDER BY id DESC";

try {
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trains = [];
    while ($row = $result->fetch_assoc()) {
        $trains[] = [
            'id' => $row['id'],
            'numero' => $row['numero'],
            'nom' => $row['nom'],
            'capacite_premiere' => $row['capacite_premiere'],
            'capacite_economique' => $row['capacite_economique'],
            'statut' => $row['statut'],
            'date_creation' => $row['date_creation']
        ];
    }
    
    // Renvoyer les trains au format JSON
    header('Content-Type: application/json');
    echo json_encode($trains);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}

// Fermer la connexion
$conn->close();
?>
