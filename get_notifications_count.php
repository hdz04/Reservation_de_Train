<?php
session_start();

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

$conn = new mysqli($servername, $username, $password, $dbname);

$count = 0;
if (isset($_SESSION['user_id']) && !$conn->connect_error) {
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT COUNT(*) as count FROM notifications 
            WHERE (utilisateur_id = ? OR utilisateur_id IS NULL)
            AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $count = $row['count'];
        }
        $stmt->close();
    }
}

if (isset($conn)) {
    $conn->close();
}

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>
