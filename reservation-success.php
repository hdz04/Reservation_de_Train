<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion avec l'URL de retour
    header("Location: login.php?redirect=" . urlencode($_SERVER['HTTP_REFERER']));
    exit();
}

$userId = $_SESSION['user_id'];

// Récupérer les paramètres de réservation
$outboundId = isset($_POST['outbound_id']) ? intval($_POST['outbound_id']) : 0;
$returnId = isset($_POST['return_id']) ? intval($_POST['return_id']) : 0;
$passengersCount = isset($_POST['passengers_count']) ? intval($_POST['passengers_count']) : 1;
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';

// Vérifier si les IDs des trajets sont valides
if ($outboundId <= 0) {
    header("Location: utilisateur.php");
    exit();
}

// Vérifier si une méthode de paiement a été sélectionnée
if (empty($paymentMethod)) {
    $_SESSION['reservation_error'] = "Veuillez sélectionner une méthode de paiement";
    header("Location: reservation.php?outbound=" . $outboundId . 
           ($returnId > 0 ? "&return=" . $returnId : "") . 
           "&passengers=" . $passengersCount);
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
    die("Connection failed: " . $conn->connect_error);
}

// Début de la transaction
$conn->begin_transaction();

try {
    // 1. Créer une nouvelle réservation
    $sql = "INSERT INTO reservations (utilisateur_id, date_reservation, nb_passagers, classe, prix_total, statut) 
            VALUES (?, NOW(), ?, 'economique', 0, 'confirmee')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt->bind_param("ii", $userId, $passengersCount);
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la création de la réservation: " . $stmt->error);
    }
    $reservationId = $conn->insert_id;
    $stmt->close();

    // 2. Ajouter le trajet à la réservation
    $sql = "UPDATE reservations SET trajet_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt->bind_param("ii", $outboundId, $reservationId);
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de l'ajout du trajet: " . $stmt->error);
    }
    $stmt->close();

    // 3. Calculer le prix total
    $totalPrice = 0;

    // Récupérer le prix du trajet aller
    $sql = "SELECT prix FROM trajets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt->bind_param("i", $outboundId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalPrice += $row['prix'] * $passengersCount;
    } else {
        throw new Exception("Impossible de récupérer le prix du trajet aller");
    }
    $stmt->close();

    // 4. Mettre à jour le prix total dans la réservation
    $sql = "UPDATE reservations SET prix_total = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt->bind_param("di", $totalPrice, $reservationId);
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la mise à jour du prix total: " . $stmt->error);
    }
    $stmt->close();

    // 5. Créer un paiement
    $sql = "INSERT INTO paiements (reservation_id, utilisateur_id, montant, methode, date_paiement) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt->bind_param("iiis", $reservationId, $userId, $totalPrice, $paymentMethod);
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la création du paiement: " . $stmt->error);
    }
    $stmt->close();

    // 6. Ajouter les informations des passagers
    if (isset($_POST['passenger']) && is_array($_POST['passenger'])) {
        foreach ($_POST['passenger'] as $index => $passengerData) {
            $nom = isset($passengerData['nom']) ? $passengerData['nom'] : '';
            $prenom = isset($passengerData['prenom']) ? $passengerData['prenom'] : '';
            $type = isset($passengerData['type']) ? $passengerData['type'] : 'adulte';
            $classAller = isset($passengerData['class_aller']) ? $passengerData['class_aller'] : 'economique';
            
            $sql = "INSERT INTO passagers (reservation_id, nom, prenom, type, classe) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête passager: " . $conn->error);
            }
            $stmt->bind_param("issss", $reservationId, $nom, $prenom, $type, $classAller);
            $stmt->execute();
            $passengerId = $conn->insert_id;
            $stmt->close();
            
            // 7. Créer un billet pour chaque passager
            $codeBillet = 'TRN-' . date('Ymd') . '-' . $reservationId . '-' . $passengerId;
            $sql = "INSERT INTO billets (passager_id, code_billet, date_emission) 
                    VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête billet: " . $conn->error);
            }
            $stmt->bind_param("is", $passengerId, $codeBillet);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 8. Ajouter l'association réservation-trajet
    $sql = "INSERT INTO reservations_trajets (reservation_id, trajet_id, type) 
            VALUES (?, ?, 'aller')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt->bind_param("ii", $reservationId, $outboundId);
    $stmt->execute();
    $stmt->close();

    // Ajouter le trajet retour s'il existe
    if ($returnId > 0) {
        $returnIdOriginal = $returnId;
        if ($returnId > 1000) {
            $returnIdOriginal = $returnId - 1000; // Récupérer l'ID original
        }
        
        $sql = "INSERT INTO reservations_trajets (reservation_id, trajet_id, type) 
                VALUES (?, ?, 'retour')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête: " . $conn->error);
        }
        $stmt->bind_param("ii", $reservationId, $returnIdOriginal);
        $stmt->execute();
        $stmt->close();
    }

    // 9. Créer une notification pour l'utilisateur
    $message = "Votre réservation a été confirmée avec succès. Merci de votre confiance!";
    $sql = "INSERT INTO notifications (utilisateur_id, content, date_creation, is_read) 
            VALUES (?, ?, NOW(), 0)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête notification: " . $conn->error);
    }
    $stmt->bind_param("is", $userId, $message);
    $stmt->execute();
    $stmt->close();

    // Commit la transaction
    $conn->commit();

    // Stocker les informations de réservation dans la session
    $_SESSION['reservation_success'] = true;
    $_SESSION['reservation_id'] = $reservationId;
    $_SESSION['reservation_total'] = $totalPrice;
    $_SESSION['payment_method'] = $paymentMethod;

    // Rediriger vers la page de confirmation
    header("Location: billet_individuel.php?id=" . $reservationId);
    exit();

} catch (Exception $e) {
    // Rollback en cas d'erreur
    $conn->rollback();
    
    // Enregistrer l'erreur dans un fichier de log
    error_log("Erreur de réservation: " . $e->getMessage(), 0);
    
    // Stocker le message d'erreur dans la session
    $_SESSION['reservation_error'] = "Une erreur est survenue lors de la réservation: " . $e->getMessage();
    
    // Rediriger vers la page de réservation avec les mêmes paramètres
    header("Location: reservation.php?outbound=" . $outboundId . 
           ($returnId > 0 ? "&return=" . $returnId : "") . 
           "&passengers=" . $passengersCount);
    exit();
}

$conn->close();
?>
