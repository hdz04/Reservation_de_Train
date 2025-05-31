<?php
session_start();

// Améliorer la gestion de la redirection vers la page de connexion
// Remplacer la partie qui gère la redirection en cas d'utilisateur non connecté par:

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Sauvegarder l'URL actuelle pour y revenir après connexion
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    // Rediriger vers la page de connexion avec l'URL de retour
    header("Location: login.php?redirect=" . urlencode($current_url));
    exit();
}

$userId = $_SESSION['user_id'];

// Récupérer les paramètres de réservation
$outboundId = isset($_POST['outbound_id']) ? intval($_POST['outbound_id']) : 0;
$returnId = isset($_POST['return_id']) ? intval($_POST['return_id']) : 0;
$passengersCount = isset($_POST['passengers_count']) ? intval($_POST['passengers_count']) : 1;
$totalPrice = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$classe = isset($_POST['outbound_class_0']) ? $_POST['outbound_class_0'] : 'economique';

// Vérifier si les IDs des trajets sont valides
if ($outboundId <= 0) {
    header("Location: utilisateur.php");
    exit();
}

// Stocker les informations de réservation dans la session
$_SESSION['reservation_info'] = [
    'outbound_id' => $outboundId,
    'return_id' => $returnId,
    'passengers_count' => $passengersCount,
    'total_price' => $totalPrice,
    'payment_method' => $paymentMethod,
    'classe' => $classe
];

// Rediriger vers la page de paiement appropriée
if ($paymentMethod === 'card') {
    header("Location: paiement_carte.php");
    exit();
} else if ($paymentMethod === 'edahabia') {
    header("Location: paiement_edahabia.php");
    exit();
} else {
    // Méthode de paiement non reconnue
    $_SESSION['reservation_error'] = "Méthode de paiement non reconnue";
    header("Location: reservation.php?outbound=" . $outboundId . 
           ($returnId > 0 ? "&return=" . $returnId : "") . 
           "&passengers=" . $passengersCount);
    exit();
}
?>
