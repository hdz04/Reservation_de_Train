<?php
// Démarrer la session
session_start();

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Journaliser les informations importantes
error_log("Traitement du paiement démarré à " . date('Y-m-d H:i:s'));

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Utilisateur non connecté, redirection vers login.php");
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Vérifier si les données de paiement sont présentes
if (!isset($_POST['transaction_id']) || !isset($_POST['payment_method'])) {
    error_log("Données de paiement manquantes");
    header("Location: utilisateur.php");
    exit();
}

// Récupérer les informations de la transaction depuis la session
if (!isset($_SESSION['transaction'])) {
    error_log("Aucune transaction en cours");
    header("Location: utilisateur.php");
    exit();
}

$transaction = $_SESSION['transaction'];
$userId = $_SESSION['user_id'];
$transactionId = $_POST['transaction_id'];
$paymentMethod = $_POST['payment_method'];
$outboundId = $transaction['outbound_id'];
$returnId = $transaction['return_id'];
$passengersCount = $transaction['passengers_count'];
$totalPrice = $transaction['total_price'];
$classe = $transaction['classe'];

error_log("Informations de transaction récupérées: ID=$transactionId, Méthode=$paymentMethod, UserID=$userId");

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

try {
    // Créer la connexion
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Vérifier la connexion
    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion à la base de données: " . $conn->connect_error);
    }

    // Définir le charset
    $conn->set_charset("utf8");

    // Début de la transaction
    $conn->begin_transaction();

    // 1. Créer une nouvelle réservation
    $sql = "INSERT INTO reservations (utilisateur_id, trajet_id, date_reservation, nb_passagers, classe, prix_total, statut) 
            VALUES (?, ?, NOW(), ?, ?, ?, 'confirmee')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $conn->error);
    }
    $stmt->bind_param("iiids", $userId, $outboundId, $passengersCount, $classe, $totalPrice);
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la création de la réservation: " . $stmt->error);
    }
    $reservationId = $conn->insert_id;
    error_log("Réservation créée avec ID: $reservationId");
    $stmt->close();

    // 2. Créer un paiement
    $sql = "INSERT INTO paiements (reservation_id, utilisateur_id, montant, methode, date_paiement) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête paiement: " . $conn->error);
    }
    $stmt->bind_param("iids", $reservationId, $userId, $totalPrice, $paymentMethod);
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la création du paiement: " . $stmt->error);
    }
    error_log("Paiement enregistré avec succès");
    $stmt->close();

    // 3. Créer des billets pour chaque passager
    for ($i = 0; $i < $passengersCount; $i++) {
        $codeBillet = 'TRN' . date('YmdHis') . $reservationId . sprintf('%02d', $i + 1);
        $sql = "INSERT INTO billets (reservation_id, trajet_id, code_billet, prix, classe, date_emission) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête billet: " . $conn->error);
        }
        $prixBillet = $totalPrice / $passengersCount;
        $stmt->bind_param("iisds", $reservationId, $outboundId, $codeBillet, $prixBillet, $classe);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la création du billet " . ($i + 1) . ": " . $stmt->error);
        }
        $stmt->close();
    }
    error_log("Billets créés avec succès pour $passengersCount passagers");

    // 4. Si c'est un aller-retour, ajouter l'entrée dans reservations_trajets
    if ($returnId > 0) {
        // Pour l'aller
        $sql = "INSERT INTO reservations_trajets (reservation_id, trajet_id, type) 
                VALUES (?, ?, 'aller')";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $reservationId, $outboundId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Pour le retour
        $sql = "INSERT INTO reservations_trajets (reservation_id, trajet_id, type) 
                VALUES (?, ?, 'retour')";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $reservationId, $returnId);
            $stmt->execute();
            $stmt->close();
        }
        
        error_log("Relations aller-retour créées avec succès");
    }

    // 5. Créer une notification pour l'utilisateur
    $message = "Votre réservation #$reservationId a été confirmée avec succès. Montant payé: " . number_format($totalPrice, 0, ',', ' ') . " DA";
    $sql = "INSERT INTO notifications (utilisateur_id, content, date_creation, is_read) 
            VALUES (?, ?, NOW(), 0)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("is", $userId, $message);
        $stmt->execute();
        $stmt->close();
        error_log("Notification créée avec succès");
    }

    // Commit la transaction
    $conn->commit();
    error_log("Transaction validée avec succès");

    // Stocker les informations de réservation dans la session
    $_SESSION['reservation_success'] = true;
    $_SESSION['reservation_id'] = $reservationId;
    $_SESSION['reservation_total'] = $totalPrice;
    $_SESSION['payment_method'] = $paymentMethod;
    $_SESSION['reservation_reference'] = $transactionId;

    // Nettoyer la transaction de la session
    unset($_SESSION['transaction']);
    unset($_SESSION['reservation_info']);

    // Fermer la connexion à la base de données
    $conn->close();

    // Journaliser la redirection
    error_log("Redirection vers billet_individuel.php avec ID: " . $reservationId);
    
    // Redirection directe avec header
    header("Location: billet_individuel.php?id=" . $reservationId);
    exit();

} catch (Exception $e) {
    // Rollback en cas d'erreur
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
        $conn->close();
    }
    
    // Enregistrer l'erreur dans un fichier de log
    error_log("Erreur de paiement: " . $e->getMessage());
    
    // Stocker le message d'erreur dans la session
    $_SESSION['payment_error'] = "Une erreur est survenue lors du traitement du paiement: " . $e->getMessage();
    
    // Rediriger vers la page de paiement avec un message d'erreur
    if ($paymentMethod === 'carte') {
        header("Location: paiement_carte.php?error=1");
    } else {
        header("Location: paiement_edahabia.php?error=1");
    }
    exit();
}
?>
