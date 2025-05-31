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

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Récupérer les données du formulaire
$content = isset($_POST['notification_content']) ? $_POST['notification_content'] : '';
$target_user = isset($_POST['target_user']) ? $_POST['target_user'] : null;

// Valider les données
if (empty($content)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Veuillez remplir le contenu de la notification']);
    exit();
}

// Si target_user est 'all' ou vide, mettre NULL pour une notification générale
if ($target_user === 'all' || empty($target_user)) {
    $target_user = null;
} else {
    $target_user = intval($target_user);
}

// Préparer la requête SQL
$sql = "INSERT INTO notifications (utilisateur_id, content, date_creation, is_read) VALUES (?, ?, NOW(), 0)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $target_user, $content);

// Exécuter la requête
if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Notification ajoutée avec succès']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de l\'ajout de la notification: ' . $stmt->error]);
}

// Fermer la connexion
$stmt->close();
$conn->close();
?>
