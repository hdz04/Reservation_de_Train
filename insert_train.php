<?php
// Ajouter des logs de débogage au début du fichier pour voir ce qui est reçu
error_log('POST data: ' . print_r($_POST, true));
error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
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

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $numero = isset($_POST['numero']) ? trim($_POST['numero']) : '';
    $nom = isset($_POST['train_name']) ? trim($_POST['train_name']) : '';
    $capacite_economique = isset($_POST['capacite_economique']) ? intval($_POST['capacite_economique']) : 0;
    $capacite_premiere = isset($_POST['capacite_premiere']) ? intval($_POST['capacite_premiere']) : 0;
    $statut = isset($_POST['train_status']) ? trim($_POST['train_status']) : 'active';

    // Valider les données
    if (empty($numero) || empty($nom)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Tous les champs obligatoires doivent être remplis']);
        exit();
    }

    if ($capacite_economique < 0 || $capacite_premiere < 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Les capacités ne peuvent pas être négatives']);
        exit();
    }

    try {
        // Vérifier si le numéro de train existe déjà
        $stmt = $conn->prepare("SELECT id FROM trains WHERE numero = ?");
        $stmt->bind_param("s", $numero);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Ce numéro de train existe déjà']);
            exit();
        }
        
        // Insérer le nouveau train
        $sql = "INSERT INTO trains (numero, nom, capacite_economique, capacite_premiere, statut) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiis", $numero, $nom, $capacite_economique, $capacite_premiere, $statut);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Train ajouté avec succès']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'ajout du train: ' . $stmt->error]);
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée. Utilisez POST.']);
}

// Fermer la connexion
$conn->close();
?>
