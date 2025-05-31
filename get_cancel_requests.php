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
    $sql = "SELECT dr.id, dr.reservation_id, dr.utilisateur_id as utilisateur_id, dr.date_demande, dr.statut,
                   u.nom as client_nom, u.prenom as client_prenom, u.email as client_email,
                   r.prix_total, r.classe,
                   CONCAT(gd.nom, ' → ', ga.nom) as trajet_info,
                   CONCAT(tr.nom, ' (N°', tr.numero, ')') as train_info
            FROM demandes_remboursement dr
            JOIN utilisateurs u ON dr.utilisateur_id = u.id
            JOIN reservations r ON dr.reservation_id = r.id
            JOIN trajets t ON r.trajet_id = t.id
            JOIN gares gd ON t.id_gare_depart = gd.id_gare
            JOIN gares ga ON t.id_gare_arrivee = ga.id_gare
            JOIN trains tr ON t.train_id = tr.id
            WHERE dr.statut = 'pending'";
    
    $params = [];
    $types = "";
    
    // Ajouter des filtres si nécessaire
    if (isset($_GET['date']) && !empty($_GET['date'])) {
        $sql .= " AND DATE(dr.date_demande) = ?";
        $params[] = $_GET['date'];
        $types .= "s";
    }
    
    if (isset($_GET['user']) && !empty($_GET['user'])) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%" . $_GET['user'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY dr.date_demande DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($requests);
    
} catch (Exception $e) {
    error_log("Erreur dans get_cancel_requests.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>
