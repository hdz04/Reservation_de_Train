<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Récupérer les paramètres de réservation
$outboundId = isset($_GET['outbound']) ? intval($_GET['outbound']) : 0;
$returnId = isset($_GET['return']) ? intval($_GET['return']) : 0;
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;

// Récupérer les informations de classe et de prix
$outboundClasses = isset($_GET['outbound_classes']) ? explode(',', $_GET['outbound_classes']) : [];
$outboundPrice = isset($_GET['outbound_price']) ? intval($_GET['outbound_price']) : 0;
$returnClasses = isset($_GET['return_classes']) ? explode(',', $_GET['return_classes']) : [];
$returnPrice = isset($_GET['return_price']) ? intval($_GET['return_price']) : 0;

// Utiliser les prix spécifiés s'ils sont disponibles
if ($outboundPrice > 0) {
    $totalPrice = $outboundPrice;
    if ($returnPrice > 0) {
        $totalPrice += $returnPrice;
    }
} else {
    // Fallback au calcul standard si les prix spécifiques ne sont pas disponibles
    $totalPrice = isset($outboundTrip['prix']) ? $outboundTrip['prix'] * $passengers : 0;
    if (isset($returnTrip) && $returnTrip) {
        $totalPrice += $returnTrip['prix'] * $passengers;
    }
}

// Vérifier si les IDs des trajets sont valides
if ($outboundId <= 0) {
    header("Location: utilisateur.php");
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

// Récupérer les informations du trajet aller
$outboundTrip = null;
$sql = "SELECT t.id, g1.nom AS gare_depart, g2.nom AS gare_arrivee, 
        t.date_heure_depart, t.date_heure_arrivee, 
        tr.nom AS train, t.prix, t.economique, 
        t.premiere_classe, t.statut
        FROM trajets t
        JOIN trains tr ON t.train_id = tr.id
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $outboundId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $outboundTrip = $result->fetch_assoc();
} else {
    // Trajet non trouvé
    header("Location: utilisateur.php");
    exit();
}
$stmt->close();

// Récupérer les informations du trajet retour si nécessaire
$returnTrip = null;
if ($returnId > 0) {
    $sql = "SELECT t.id, g1.nom AS gare_depart, g2.nom AS gare_arrivee, 
            t.date_heure_depart, t.date_heure_arrivee, 
            tr.nom AS train, t.prix, t.economique, 
            t.premiere_classe, t.statut
            FROM trajets t
            JOIN trains tr ON t.train_id = tr.id
            JOIN gares g1 ON t.id_gare_depart = g1.id_gare
            JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
            WHERE t.id = ?";
    $stmt = $conn->prepare($sql);
    $returnIdOriginal = $returnId;
    if ($returnId > 1000) {
        $returnIdOriginal = $returnId - 1000; // Récupérer l'ID original
    }
    $stmt->bind_param("i", $returnIdOriginal);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $returnTrip = $result->fetch_assoc();
    }
    $stmt->close();
}

// Déterminer la classe pour le trajet retour
$returnClass = isset($returnClasses[0]) ? $returnClasses[0] : 'economy';

// Récupérer les informations de l'utilisateur
$userInfo = null;
$sql = "SELECT nom, prenom, email, telephone FROM utilisateurs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userInfo = $result->fetch_assoc();
}
$stmt->close();

// Fermer la connexion
$conn->close();

// Formater les dates
$outboundDepartDate = new DateTime($outboundTrip['date_heure_depart']);
$outboundArriveDate = new DateTime($outboundTrip['date_heure_arrivee']);

$outboundDepartFormatted = $outboundDepartDate->format('d/m/Y H:i');
$outboundArriveFormatted = $outboundArriveDate->format('d/m/Y H:i');

$returnDepartFormatted = '';
$returnArriveFormatted = '';
if (isset($returnTrip) && $returnTrip) {
    $returnDepartDate = new DateTime($returnTrip['date_heure_depart']);
    $returnArriveDate = new DateTime($returnTrip['date_heure_arrivee']);
    
    $returnDepartFormatted = $returnDepartDate->format('d/m/Y H:i');
    $returnArriveFormatted = $returnArriveDate->format('d/m/Y H:i');
}

// Calculer le prix total
$totalPrice = 0;
for ($i = 0; $i < $passengers; $i++): 
    $outboundClass = isset($outboundClasses[$i]) ? $outboundClasses[$i] : 'economique';
    $outboundPassengerPrice = ($outboundClass === 'premiere') ? round($outboundTrip['prix'] * 1.5) : $outboundTrip['prix'];
    $totalPrice += $outboundPassengerPrice;
    
    if (isset($returnTrip) && $returnTrip && isset($returnClasses[$i])) {
        $returnClass = $returnClasses[$i];
        $returnPassengerPrice = ($returnClass === 'premiere') ? round($returnTrip['prix'] * 1.5) : $returnTrip['prix'];
        $totalPrice += $returnPassengerPrice;
    }
endfor;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - Annaba Train</title>
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #06245b;
            --primary-light: #1a3a7a;
            --primary-dark: #041a4a;
            --accent-color: #ff6b35;
            --accent-hover: #ff5a1f;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-color: #333;
            --text-light: #666;
            --text-lighter: #888;
            --bg-color: #f8f9fa;
            --bg-light: #ffffff;
            --border-color: #e5e7eb;
            --border-light: #f0f0f0;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header Styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: var(--primary-color);
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
        }

        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.75rem;
            object-fit: cover;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: white;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover, 
        .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-menu {
            position: relative;
        }

        .user-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: var(--radius-md);
            padding: 0.5rem 1rem;
            color: white;
            cursor: pointer;
            transition: var(--transition);
        }

        .user-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background-color: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            width: 220px;
            overflow: hidden;
            display: none;
            z-index: 100;
            animation: fadeIn 0.2s ease;
        }

        .dropdown-menu.active {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .dropdown-menu a:hover {
            background-color: var(--bg-color);
        }

        .dropdown-menu a.logout {
            color: var(--danger-color);
            border-top: 1px solid var(--border-light);
            margin-top: 0.25rem;
        }

        .dropdown-menu a.logout:hover {
            background-color: #fff5f5;
        }

        /* Main Content Styles */
        .reservation-container {
            padding: 2rem 0;
            min-height: calc(100vh - 70px - 300px);
        }

        .reservation-card {
            background-color: var(--bg-light);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .reservation-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
        }

        .reservation-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .reservation-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .reservation-progress {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            position: relative;
        }

        .reservation-progress::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%);
            z-index: 1;
        }

        .progress-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .progress-step.active .step-number {
            background-color: var(--accent-color);
        }

        .progress-step.completed .step-number {
            background-color: var(--success-color);
        }

        .step-label {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .reservation-content {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            font-size: 1.25rem;
            color: var(--primary-light);
        }

        /* Trip Summary Styles */
        .trip-summary {
            margin-bottom: 2.5rem;
        }

        .trip-card {
            background-color: white;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .trip-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .trip-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--primary-color);
        }

        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-light);
        }

        .trip-type {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .trip-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .trip-details {
            display: flex;
            gap: 2rem;
        }

        .trip-stations {
            flex: 1;
            position: relative;
        }

        .station {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            position: relative;
            z-index: 2;
        }

        .station:not(:last-child) {
            margin-bottom: 2rem;
        }

        .station-time {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            min-width: 60px;
        }

        .station-info {
            flex: 1;
        }

        .station-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .station-date {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .journey-line {
            position: absolute;
            top: 1.5rem;
            bottom: 1.5rem;
            left: 30px;
            width: 2px;
            background-color: var(--border-color);
            z-index: 1;
        }

        .journey-line::before,
        .journey-line::after {
            content: '';
            position: absolute;
            left: 50%;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
            transform: translateX(-50%);
        }

        .journey-line::before {
            top: -6px;
        }

        .journey-line::after {
            bottom: -6px;
        }

        .duration-badge {
            position: absolute;
            top: 50%;
            left: 40px;
            transform: translateY(-50%);
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 500;
            z-index: 2;
        }

        .trip-price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            min-width: 120px;
            padding-left: 1.5rem;
            border-left: 1px solid var(--border-light);
        }

        .price-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .price-info {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        /* Passenger Info Styles */
        .passenger-info {
            margin-bottom: 2.5rem;
        }

        .passenger-form {
            background-color: white;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .passenger-form:hover {
            box-shadow: var(--shadow-sm);
        }

        .passenger-form h4 {
            margin-top: 0;
            margin-bottom: 1.25rem;
            color: var(--primary-color);
            font-weight: 600;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .passenger-fields {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.25rem;
        }

        .form-group {
            margin-bottom: 0.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.95rem;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
            color: var(--text-color);
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 36, 91, 0.1);
        }

        .form-group input.error,
        .form-group select.error {
            border-color: var(--danger-color);
        }

        .form-hint {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-lighter);
        }

        .form-hint i {
            color: var(--primary-light);
        }

        /* Payment Options Styles */
        .payment-options {
            margin-bottom: 2.5rem;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .payment-method {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .payment-method:hover {
            border-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .payment-method.selected {
            border-color: var(--primary-color);
            background-color: rgba(6, 36, 91, 0.03);
            box-shadow: var(--shadow-md);
        }

        .payment-method.selected::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--primary-color);
            font-size: 1rem;
        }

        .payment-method-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .payment-method-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(6, 36, 91, 0.1);
            border-radius: 50%;
            font-size: 1.25rem;
            color: var(--primary-color);
        }

        .payment-method-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .payment-method-description {
            font-size: 0.9rem;
            color: var(--text-light);
            line-height: 1.5;
        }

        /* Total Summary Styles */
        .total-summary {
            background-color: white;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .total-summary h3 {
            margin-top: 0;
            margin-bottom: 1.25rem;
            color: var(--primary-color);
            font-weight: 600;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            font-size: 1rem;
        }

        .summary-row:not(:last-child) {
            border-bottom: 1px solid var(--border-light);
        }

        .summary-label {
            color: var(--text-light);
        }

        .summary-value {
            font-weight: 500;
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary-color);
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 2px solid var(--border-color);
        }

        /* Action Buttons Styles */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
        }

        .btn-back {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-back:hover {
            background-color: var(--border-light);
        }

        .btn-confirm {
            background-color: var(--primary-color);
            color: white;
            flex: 1;
        }

        .btn-confirm:hover {
            background-color: var(--primary-dark);
        }

       

        /* Profile Info Card */
        .profile-info-card {
            background-color: white;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .profile-details {
            flex: 1;
        }

        .profile-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--primary-color);
        }

        .profile-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .profile-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .profile-item i {
            color: var(--primary-light);
            font-size: 1rem;
        }

        .profile-edit {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            background-color: rgba(6, 36, 91, 0.05);
            transition: var(--transition);
        }

        .profile-edit:hover {
            background-color: rgba(6, 36, 91, 0.1);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .nav-links {
                display: none;
            }
            
            .reservation-progress {
                overflow-x: auto;
                padding-bottom: 1rem;
            }
            
            .progress-step {
                min-width: 100px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }
            
            .logo h1 {
                font-size: 1.25rem;
            }
            
            .reservation-header {
                padding: 1.25rem;
            }
            
            .reservation-content {
                padding: 1.25rem;
            }
            
            .trip-details {
                flex-direction: column;
            }
            
            .trip-price {
                border-left: none;
                border-top: 1px solid var(--border-light);
                padding-left: 0;
                padding-top: 1rem;
                margin-top: 1rem;
                align-items: flex-start;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-back, .btn-confirm {
                width: 100%;
            }
            
            .profile-info-card {
                flex-direction: column;
                align-items: flex-start;
                text-align: center;
            }
            
            .profile-avatar {
                margin: 0 auto;
            }
            
            .profile-info {
                justify-content: center;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Styles for the unified summary */
        .unified-summary {
            margin-bottom: 2.5rem;
        }

        .summary-container {
            background-color: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .summary-trips {
            padding: 0;
        }

        .summary-trip-card {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-light);
            transition: var(--transition);
        }

        .summary-trip-card:hover {
            background-color: rgba(248, 249, 250, 0.5);
        }

        .trip-badge {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: rgba(6, 36, 91, 0.1);
            color: var(--primary-color);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .trip-badge.return {
            background-color: rgba(255, 107, 53, 0.1);
            color: var(--accent-color);
        }

        .trip-route {
            flex: 1;
            margin-top: 2.5rem;
            padding: 0 1rem;
        }

        .route-stations {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .station-point {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .station-point .time {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .station-point .date {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .route-line {
            flex: 1;
            height: 2px;
            background-color: var(--border-color);
            margin: 0 1rem;
            position: relative;
        }

        .duration-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 500;
            white-space: nowrap;
        }

        .route-cities {
            display: flex;
            justify-content: space-between;
        }

        .route-cities .city {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .trip-price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            padding-left: 1.5rem;
            margin-left: auto;
        }

        .price-tag {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .price-info {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .summary-details {
            background-color: #f8f9fa;
            padding: 1.5rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            font-size: 1rem;
        }

        .summary-item:not(:last-child) {
            border-bottom: 1px solid var(--border-light);
        }

        .item-label {
            color: var(--text-light);
        }

        .item-value {
            font-weight: 500;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0 0;
            margin-top: 0.5rem;
            border-top: 2px solid var(--border-color);
        }

        .total-label {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary-color);
        }

        .total-value {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .summary-trip-card {
                flex-direction: column;
            }
            
            .trip-route {
                margin-bottom: 1.5rem;
            }
            
            .trip-price {
                padding-left: 0;
                align-items: flex-start;
            }
        }

        .trip-class {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--text-light);
            background-color: rgba(6, 36, 91, 0.05);
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-flex;
        }

        .trip-class i {
            color: var(--primary-color);
        }

        .passenger-item {
            display: flex;
            flex-direction: column;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background-color: rgba(248, 249, 250, 0.5);
            border-radius: var(--radius-md);
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }

        .passenger-class {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .trip-class {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-light);
            background-color: rgba(6, 36, 91, 0.05);
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-flex;
        }

        .trip-class.return {
            background-color: rgba(255, 107, 53, 0.1);
        }

        .trip-class i {
            color: var(--primary-color);
        }

        .trip-class.return i {
            color: var(--accent-color);
        }

        .class-price {
            margin-left: auto;
            font-weight: 500;
            color: var(--text-color);
        }

        .passenger-total {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        /* Footer */
footer {
  background-color: var(--dark-background);
  color: white;
  padding: 40px 0 20px;
}

.footer-content {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  margin-bottom: 30px;
}

.footer-section {
  flex: 1;
  min-width: 250px;
}

.footer-section h3 {
  font-size: 1.2rem;
  margin-bottom: 15px;
  position: relative;
  padding-bottom: 10px;
}

.footer-section h3::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 50px;
  height: 2px;
  background-color: rgba(255, 255, 255, 0.3);
}

.footer-section p {
  margin-bottom: 10px;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
}

.footer-section p {
  margin-bottom: 10px;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
}

.social-icons {
  display: flex;
  gap: 15px;
  margin-top: 15px;
}



.social-icon:hover {
  background-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-3px);
}

.footer-bottom {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-bottom p {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.9rem;
}
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="logo">
                    <img src="logo.png" alt="Logo Annaba Train">
                    <h1>Annaba Train</h1>
            </div>
            
            <div class="nav-links">
                <a href="utilisateur.php" class="nav-link"><i class="fas fa-home"></i> Accueil</a>
                <a href="mes_reservations.php" class="nav-link"><i class="fas fa-ticket-alt"></i> Mes réservations</a>
                <a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a>
            </div>
            
            <div class="user-menu">
                <button class="user-button" id="userMenuButton">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu" id="userDropdown">
                    <a href="profil.php"><i class="fas fa-user"></i> Mon profil</a>
                    <a href="mes_reservations.php"><i class="fas fa-ticket-alt"></i> Mes réservations</a>
                    <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="reservation-container">
        <div class="container">
            <div class="reservation-card">
                <div class="reservation-header">
                    <h2>Finaliser votre réservation</h2>
                    <p>Complétez les informations ci-dessous pour confirmer votre voyage</p>
                    
                    <div class="reservation-progress">
                        <div class="progress-step completed">
                            <div class="step-number"><i class="fas fa-check"></i></div>
                            <div class="step-label">Recherche</div>
                        </div>
                        <div class="progress-step completed">
                            <div class="step-number"><i class="fas fa-check"></i></div>
                            <div class="step-label">Sélection</div>
                        </div>
                        <div class="progress-step active">
                            <div class="step-number">3</div>
                            <div class="step-label">Réservation</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-number">4</div>
                            <div class="step-label">Confirmation</div>
                        </div>
                    </div>
                </div>
                
                <div class="reservation-content">
                    

<div class="unified-summary">
    <h3 class="section-title"><i class="fas fa-receipt"></i> Récapitulatif de votre réservation</h3>
    
    <div class="summary-container">
        <div class="summary-trips">
            <!-- Trajet Aller -->
            <div class="summary-trip-card">
                <div class="trip-badge">
                    <i class="fas fa-train"></i>
                    <span>Aller</span>
                </div>
                
                <div class="trip-route">
                    <div class="route-stations">
                        <div class="station-point departure">
                            <div class="time"><?php echo $outboundDepartDate->format('H:i'); ?></div>
                            <div class="date"><?php echo $outboundDepartDate->format('d M Y'); ?></div>
                        </div>
                        
                        <div class="route-line">
                            <div class="duration-badge">
                                <?php 
                                    $interval = $outboundDepartDate->diff($outboundArriveDate);
                                    $hours = $interval->h;
                                    if ($interval->d > 0) {
                                        $hours += $interval->d * 24;
                                    }
                                    echo $hours . 'h ' . $interval->i . 'min';
                                ?>
                            </div>
                        </div>
                        
                        <div class="station-point arrival">
                            <div class="time"><?php echo $outboundArriveDate->format('H:i'); ?></div>
                            <div class="date"><?php echo $outboundArriveDate->format('d M Y'); ?></div>
                        </div>
                    </div>
                    
                    <div class="route-cities">
                        <div class="city departure"><?php echo htmlspecialchars($outboundTrip['gare_depart']); ?></div>
                        <div class="city arrival"><?php echo htmlspecialchars($outboundTrip['gare_arrivee']); ?></div>
                    </div>
                    
                    <div class="trip-class">
                        <i class="fas fa-<?php echo in_array('premiere', $outboundClasses) ? 'couch' : 'chair'; ?>"></i>
                        <span><?php 
                            if (count(array_unique($outboundClasses)) === 1) {
                                echo in_array('premiere', $outboundClasses) ? 'Première classe' : 'Classe économique';
                            } else {
                                echo 'Classes mixtes';
                            }
                        ?></span>
                    </div>
                </div>
                
                <div class="trip-price">
                    <div class="price-tag"><?php echo number_format($outboundTrip['prix'], 0, ',', ' '); ?> DA</div>
                    <div class="price-info"><?php echo $passengers; ?> passager<?php echo $passengers > 1 ? 's' : ''; ?></div>
                </div>
            </div>
            
            <?php if (isset($returnTrip) && $returnTrip): ?>
            <!-- Trajet Retour -->
            <div class="summary-trip-card">
                <div class="trip-badge return">
                    <i class="fas fa-train"></i>
                    <span>Retour</span>
                </div>
                
                <div class="trip-route">
                    <div class="route-stations">
                        <div class="station-point departure">
                            <div class="time"><?php echo $returnDepartDate->format('H:i'); ?></div>
                            <div class="date"><?php echo $returnDepartDate->format('d M Y'); ?></div>
                        </div>
                        
                        <div class="route-line">
                            <div class="duration-badge">
                                <?php 
                                    $interval = $returnDepartDate->diff($returnArriveDate);
                                    $hours = $interval->h;
                                    if ($interval->d > 0) {
                                        $hours += $interval->d * 24;
                                    }
                                    echo $hours . 'h ' . $interval->i . 'min';
                                ?>
                            </div>
                        </div>
                        
                        <div class="station-point arrival">
                            <div class="time"><?php echo $returnArriveDate->format('H:i'); ?></div>
                            <div class="date"><?php echo $returnArriveDate->format('d M Y'); ?></div>
                        </div>
                    </div>
                    
                    <div class="route-cities">
                        <div class="city departure"><?php echo htmlspecialchars($returnTrip['gare_depart']); ?></div>
                        <div class="city arrival"><?php echo htmlspecialchars($returnTrip['gare_arrivee']); ?></div>
                    </div>
                    
                    <div class="trip-class">
                        <i class="fas fa-<?php echo $returnClass === 'premiere' ? 'couch' : 'chair'; ?>"></i>
                        <span><?php 
                            echo $returnClass === 'premiere' ? 'Première classe' : 'Classe économique';
                        ?></span>
                    </div>
                </div>
                
                <div class="trip-price">
                    <div class="price-tag"><?php echo number_format($returnTrip['prix'], 0, ',', ' '); ?> DA</div>
                    <div class="price-info"><?php echo $passengers; ?> passager<?php echo $passengers > 1 ? 's' : ''; ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="summary-details">
    <?php 
    $totalPrice = 0;

    for ($i = 0; $i < $passengers; $i++): 
        $outboundClass = isset($outboundClasses[$i]) ? $outboundClasses[$i] : 'economique';
        $outboundPrice = ($outboundClass === 'premiere') ? round($outboundTrip['prix'] * 1.5) : $outboundTrip['prix'];
        $passengerTotal = $outboundPrice;
    ?>
        <div class="summary-item passenger-item">
            <div class="item-label">Passager <?php echo $i + 1; ?></div>
            <div class="item-details">
                <div class="passenger-class">
                    <div class="trip-class">
                        <i class="fas fa-<?php echo $outboundClass === 'premiere' ? 'couch' : 'chair'; ?>"></i>
                        <span><?php echo $outboundClass === 'premiere' ? 'Première classe' : 'Classe économique'; ?></span>
                        <span class="class-price"><?php echo number_format($outboundPrice, 0, ',', ' '); ?> DA</span>
                    </div>
                    <?php
                    if (isset($returnTrip) && $returnTrip && isset($returnClasses[$i])):
                        $returnClass = $returnClasses[$i];
                        $returnPrice = ($returnClass === 'premiere') ? round($returnTrip['prix'] * 1.5) : $returnTrip['prix'];
                        $passengerTotal += $returnPrice;
                    ?>
                        <div class="trip-class return">
                            <i class="fas fa-<?php echo $returnClass === 'premiere' ? 'couch' : 'chair'; ?>"></i>
                            <span><?php echo $returnClass === 'premiere' ? 'Première classe' : 'Classe économique'; ?> (Retour)</span>
                            <span class="class-price"><?php echo number_format($returnPrice, 0, ',', ' '); ?> DA</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="passenger-total"><?php echo number_format($passengerTotal, 0, ',', ' '); ?> DA</div>
            </div>
        </div>
    <?php 
        $totalPrice += $passengerTotal;
    endfor; 
    ?>

    <div class="summary-total">
        <div class="total-label">Total</div>
        <div class="total-value"><?php echo number_format($totalPrice, 0, ',', ' '); ?> DA</div>
    </div>
</div>


   <!-- Informations du profil -->
   <div class="profile-info-card">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($userInfo['prenom'] ?? $userName, 0, 1)); ?>
                        </div>
                        <div class="profile-details">
                            <div class="profile-name"><?php echo htmlspecialchars($userInfo['prenom'] ?? '') . ' ' . htmlspecialchars($userInfo['nom'] ?? $userName); ?></div>
                            <div class="profile-info">
                                <?php if (!empty($userInfo['email'])): ?>
                                <div class="profile-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($userInfo['email']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($userInfo['telephone'])): ?>
                                <div class="profile-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($userInfo['telephone']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="profil.php" class="profile-edit">
                            <i class="fas fa-edit"></i>
                            Modifier mon profil
                        </a>
                    </div>
    
    <form id="reservationForm" action="process_reservation.php" method="post">
        <input type="hidden" name="outbound_id" value="<?php echo $outboundId; ?>">
        <?php if ($returnId > 0): ?>
        <input type="hidden" name="return_id" value="<?php echo $returnId; ?>">
        <?php endif; ?>
        <input type="hidden" name="passengers_count" value="<?php echo $passengers; ?>">
        <input type="hidden" name="total_price" value="<?php echo $totalPrice; ?>">
        
        <!-- Ajouter les classes pour chaque passager -->
        <?php for ($i = 0; $i < $passengers; $i++): ?>
            <?php 
            $outboundClass = isset($outboundClasses[$i]) ? $outboundClasses[$i] : 'economique';
            ?>
            <input type="hidden" name="outbound_class_<?php echo $i; ?>" value="<?php echo $outboundClass; ?>">
            
            <?php if (isset($returnTrip) && $returnTrip && isset($returnClasses[$i])): ?>
                <input type="hidden" name="return_class_<?php echo $i; ?>" value="<?php echo $returnClasses[$i]; ?>">
            <?php endif; ?>
        <?php endfor; ?>

        <div class="payment-options">
            <h3 class="section-title"><i class="fas fa-credit-card"></i> Méthode de paiement</h3>
            
            <div class="payment-methods">
                <div class="payment-method selected" data-method="card">
                    <div class="payment-method-header">
                        <div class="payment-method-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="payment-method-name">Carte bancaire</div>
                    </div>
                    <div class="payment-method-description">
                        Payez en toute sécurité avec votre carte bancaire.
                    </div>
                </div>
                
                <div class="payment-method" data-method="edahabia">
                    <div class="payment-method-header">
                        <div class="payment-method-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="payment-method-name">Carte Edahabia</div>
                    </div>
                    <div class="payment-method-description">
                        Payez facilement avec votre carte Edahabia d'Algérie Poste.
                    </div>
                </div>
                
            </div>
            
            <input type="hidden" name="payment_method" id="payment_method" value="card">
        </div>

        
        <div class="action-buttons">
            <a href="javascript:history.back()" class="btn btn-back">
                <i class="fas fa-arrow-left"></i>
                Retour
            </a>
            
            <button type="submit" class="btn btn-confirm">
                <i class="fas fa-check"></i>
                Confirmer et payer
            </button>
            
        </div>
    </form>
</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>À propos</h3>
                    <p>Service de réservation de billets de train pour Annaba et toute l'Algérie.</p>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>Email: contact@annaba-train.dz</p>
                    <p>Tél: +213 XX XX XX XX</p>
                </div>
                <div class="footer-section">
                    <h3>Suivez-nous</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-google"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Annaba Trains. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    // User dropdown menu toggle
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');

    if (userMenuButton && userDropdown) {
        userMenuButton.addEventListener('click', function () {
            userDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }

    // Sélection de la méthode de paiement
    const paymentMethods = document.querySelectorAll('.payment-method');
    const paymentMethodInput = document.getElementById('payment_method');

    paymentMethods.forEach(method => {
        method.addEventListener('click', function () {
            // Supprimer la classe selected de toutes les méthodes
            paymentMethods.forEach(m => m.classList.remove('selected'));

            // Ajouter la classe selected à la méthode cliquée
            this.classList.add('selected');

            // Mettre à jour la valeur du champ caché
            paymentMethodInput.value = this.getAttribute('data-method');
        });
    });
});

</script>
</body>
</html>
