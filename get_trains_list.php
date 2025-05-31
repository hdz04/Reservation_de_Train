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
    echo json_encode(['error' => 'Erreur de connexion à la base de données: ' . $conn->connect_error]);
    exit();
}

// Définir l'encodage
$conn->set_charset("utf8");

try {
    // Requête pour récupérer tous les trains actifs
    $sql = "SELECT id, nom, numero, statut FROM trains WHERE statut = 'active' ORDER BY nom ASC";
    $result = $conn->query($sql);
    
    $trains = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $trains[] = [
                'id' => (int)$row['id'],
                'nom' => $row['nom'],
                'numero' => $row['numero'],
                'statut' => $row['statut']
            ];
        }
    }
    
    // Fermer la connexion
    $conn->close();
    
    // Retourner les données en JSON
    header('Content-Type: application/json');
    echo json_encode($trains);
    
} catch (Exception $e) {
    // En cas d'erreur
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de la récupération des trains: ' . $e->getMessage()]);
}
?>
