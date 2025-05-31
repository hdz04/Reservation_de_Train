<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Utilisateur non connecté']);
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

// Définir l'encodage des caractères
$conn->set_charset("utf8");

$userId = $_SESSION['user_id'];

// Récupérer les notifications pour l'utilisateur connecté
$sql = "SELECT id, content, date_creation, is_read 
        FROM notifications 
        WHERE (utilisateur_id = ? OR utilisateur_id IS NULL)
        ORDER BY date_creation DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

// Fermer la connexion
$stmt->close();
$conn->close();

// Renvoyer les notifications en JSON
header('Content-Type: application/json');
echo json_encode($notifications);
?>
