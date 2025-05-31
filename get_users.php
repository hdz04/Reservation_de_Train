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

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Erreur de connexion: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
    // Construire la requête SQL
    $sql = "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.date_inscription,
                   COUNT(r.id) as reservations_count
            FROM utilisateurs u
            LEFT JOIN reservations r ON u.id = r.utilisateur_id
            WHERE u.role = 'client'";
    
    $params = [];
    $types = "";
    
    // Ajouter la recherche si fournie
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%" . $_GET['search'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
    
    $sql .= " GROUP BY u.id ORDER BY u.date_inscription DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($users);
    
} catch (Exception $e) {
    error_log("Erreur dans get_users.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>
