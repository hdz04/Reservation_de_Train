<?php


// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Récupérer les informations de l'utilisateur
$userId = $_SESSION['user_id'];
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// Database connection
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "train";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Vérifier si la table utilisateurs existe
$tableExistsQuery = "SHOW TABLES LIKE 'utilisateurs'";
$tableExists = $conn->query($tableExistsQuery)->num_rows > 0;

if (!$tableExists) {
    $errorMessage = "La table 'utilisateurs' n'existe pas dans la base de données.";
} else {
    // Récupérer les informations complètes de l'utilisateur
    $sql = "SELECT * FROM utilisateurs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Vérifier si l'utilisateur existe dans la base de données
    if ($result->num_rows === 0) {
        // L'utilisateur n'existe pas dans la base de données
        $errorMessage = "Utilisateur non trouvé dans la base de données.";
    } else {
        $user = $result->fetch_assoc();
        
        // Mettre à jour le nom d'utilisateur dans la session
        if (!isset($_SESSION['user_name']) || empty($_SESSION['user_name'])) {
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            $userName = $_SESSION['user_name'];
        }
        
        // Mettre à jour l'email dans la session
        if (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) {
            $_SESSION['user_email'] = $user['email'];
            $userEmail = $_SESSION['user_email'];
        }
        
        // Mettre à jour le rôle dans la session
        if (!isset($_SESSION['user_role']) || empty($_SESSION['user_role'])) {
            $_SESSION['user_role'] = $user['role'];
            $userRole = $_SESSION['user_role'];
        }
    }
}

// Traitement du formulaire de mise à jour du profil
$successMessage = "";
$errorMessage = "";

if (isset($_POST['updateProfile'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $telephone = $_POST['telephone'];
    
    // Mettre à jour le profil
    $updateSql = "UPDATE utilisateurs SET nom = ?, prenom = ?, telephone = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("sssi", $nom, $prenom, $telephone, $userId);
    
    if ($updateStmt->execute()) {
        $successMessage = "Profil mis à jour avec succès!";
        // Mettre à jour la session
        $_SESSION['user_name'] = $prenom . ' ' . $nom;
        
        // Récupérer les informations mises à jour
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $errorMessage = "Erreur lors de la mise à jour du profil: " . $conn->error;
    }
    
    $updateStmt->close();
}

// Récupérer les réservations de l'utilisateur
$reservationsResult = false;

// Vérifier si la table reservations existe
$tableExistsQuery = "SHOW TABLES LIKE 'reservations'";
$tableExists = $conn->query($tableExistsQuery)->num_rows > 0;

if ($tableExists) {
    $reservationsSql = "SELECT r.*, t.id_gare_depart, t.id_gare_arrivee, 
                       t.date_heure_depart, t.date_heure_arrivee, t.prix,
                       g1.nom as gare_depart, g2.nom as gare_arrivee
                       FROM reservations r 
                       JOIN trajets t ON r.trajet_id = t.id 
                       JOIN gares g1 ON t.id_gare_depart = g1.id_gare
                       JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
                       WHERE r.utilisateur_id = ? 
                       ORDER BY r.date_reservation DESC 
                       LIMIT 5";
    
    $reservationsStmt = $conn->prepare($reservationsSql);
    $reservationsStmt->bind_param("i", $userId);
    $reservationsStmt->execute();
    $reservationsResult = $reservationsStmt->get_result();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Annaba Train</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --color-gray: #a3aaa6;
            --color-steel-blue: #314f70;
            --color-medium-blue: #3b5f87;
            --color-navy: #1a477d;
            --color-dark-navy: #06245b;
            --color-deep-navy: #051641;
            --color-deep-blue: #051641;
            --color-light-gray: #e0e0e0;
            --button-danger-bg: #dc3545;
            --button-danger-hover: #bb2d3b;
            --status-active: #198754;
            --status-inactive: #dc3545;
            --status-archived: #ffc107;
            --text-color: #333;
            --color-blue: #1a477d;

  --primary-color: #06245b;
  --button-primary-hover: #051641;
  --text-color: #333;
  --background-color: #f8f9fa;
  --dark-background: #051641;
  --sidebar-hover: rgba(255, 255, 255, 0.1);
  --sidebar-active: rgba(255, 255, 255, 0.2);
  --table-header-bg: #f0f4f9;
  --table-header-text: #314f70;


        }
        
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .profile-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--color-deep-blue);
            margin-bottom: 10px;
        }
        
        .profile-header p {
            color: var(--color-medium-blue);
            font-size: 16px;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 30px;
        }
        
        .profile-tab {
            padding: 15px 25px;
            cursor: pointer;
            font-weight: 600;
            color: var(--color-medium-blue);
            position: relative;
            transition: all 0.3s ease;
        }
        
        .profile-tab.active {
            color: var(--color-deep-blue);
        }
        
        .profile-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--color-deep-blue);
            border-radius: 3px 3px 0 0;
        }
        
        .profile-tab:hover {
            background-color: rgba(59, 95, 135, 0.05);
        }
        
        .profile-content {
            display: none;
        }
        
        .profile-content.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }
        
        .profile-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(6, 36, 91, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .profile-card h2 {
            color: var(--color-deep-blue);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: left;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-group {
            margin-bottom: 20px;
        }
        
        .info-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--color-medium-blue);
            font-size: 14px;
        }
        
        .info-group p {
            font-size: 16px;
            color: var(--text-color);
            padding: 10px 0;
        }
        
        .info-group input,
        .info-group select,
        .info-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }
        
        .info-group input:focus,
        .info-group select:focus,
        .info-group textarea:focus {
            outline: none;
            border-color: var(--color-deep-blue);
            box-shadow: 0 0 0 3px rgba(26, 71, 125, 0.1);
            background-color: #ffffff;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 15px;
        }
        
        .btn-secondary {
            background-color: var(--color-light-gray);
            color: var(--text-color);
        }
        
        .btn-secondary:hover {
            background-color: #8a9196;
            color: white;
        }
        
        .btn-danger {
            background-color: var(--button-danger-bg);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: var(--button-danger-hover);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .reservation-card {
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .reservation-card:hover {
            box-shadow: 0 5px 15px rgba(6, 36, 91, 0.1);
            transform: translateY(-3px);
        }
        
        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--color-deep-blue), var(--color-navy));
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .reservation-title {
            font-weight: 600;
            color: var(--color-deep-blue);
            font-size: 16px;
        }
        
        .reservation-date {
            font-size: 14px;
            color: var(--color-medium-blue);
        }
        
        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-group {
            margin-bottom: 10px;
        }
        
        .detail-group label {
            display: block;
            font-size: 12px;
            color: var(--color-medium-blue);
            margin-bottom: 5px;
        }
        
        .detail-group p {
            font-weight: 500;
            font-size: 14px;
        }
        
        .reservation-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
            gap: 10px;
        }
        
        .reservation-actions button {
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .no-reservations {
            text-align: center;
            padding: 30px;
            color: var(--color-medium-blue);
            font-style: italic;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--color-medium-blue);
            color: white;
            font-size: 48px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(6, 36, 91, 0.2);
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(6, 36, 91, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(6, 36, 91, 0.15);
        }
        
        .stat-icon {
            font-size: 30px;
            color: var(--color-deep-blue);
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-deep-blue);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--color-medium-blue);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }
        
    

/* Navigation */
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background-color: var(--primary-color);
  box-shadow: 0 4px 12px hsla(0, 0%, 0%, 0.500);
  position: relative;
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
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 15px;
}

.logo h1 {
  font-size: 1.5rem;
  margin: 0;
  color: white;
}

.nav-links {
  display: flex;
  gap: 20px;
}

.nav-link {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 5px;
  transition: all 0.3s ease;
  font-weight: 500;
}

.nav-link:hover,
.nav-link.active {
  color: white;
  background-color: rgba(255, 255, 255, 0.1);
}

.auth-buttons {
  display: flex;
  gap: 10px;
}

.user-menu {
  position: relative;
}

.user-button {
  display: flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 5px;
  transition: background-color 0.3s;
  color: white;
}

.user-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background-color: white;
  border-radius: 5px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  width: 200px;
  padding: 10px 0;
  display: none;
  z-index: 1000;
}

.dropdown-menu.active {
  display: block;
  animation: fadeIn 0.3s ease;
}

.dropdown-menu a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 15px;
  color: var(--text-color);
  text-decoration: none;
  transition: background-color 0.3s;
}

.dropdown-menu a:hover {
  background-color: #f0f4f9;
}

.dropdown-menu a.logout {
  color: #e74c3c;
}

.dropdown-menu a.logout:hover {
  background-color: #fde9e7;
}
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            background-color: var(--color-deep-blue);
            color: white;
        }
        
        .btn:hover {
            background-color: var(--color-blue);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--color-deep-blue);
            color: var(--color-deep-blue);
        }
        
        .btn-outline:hover {
            background-color: rgba(26, 71, 125, 0.05);
        }
        
        @media (max-width: 768px) {
            .profile-tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
            
            .profile-tab {
                padding: 12px 15px;
                font-size: 14px;
            }
            
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions button {
                width: 100%;
            }
            
            .reservation-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .reservation-date {
                margin-top: 5px;
            }
            
            .reservation-details {
                grid-template-columns: 1fr;
            }
            
            .user-stats {
                grid-template-columns: 1fr;
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
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
                <a href="utilisateur.php" class="nav-link active"><i class="fas fa-home"></i> Accueil</a>
                <a href="mes_reservations.php" class="nav-link"><i class="fas fa-ticket-alt"></i> Mes réservations</a>
                <a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <button class="user-button" id="userMenuButton">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($userName); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdown">
                        <?php if ($userRole === 'admin'): ?>
                            <a href="admin.php"><i class="fas fa-cog"></i> Administration</a>
                        <?php endif; ?>
                        <a href="profil.php" class="active"><i class="fas fa-user"></i> Mon profil</a>
                        <a href="mes_reservations.php"><i class="fas fa-ticket-alt"></i> Mes réservations</a>
                        <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">Se connecter</a>
                    <a href="register.php" class="btn btn-primary">S'inscrire</a>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo isset($user) ? strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) : 'UT'; ?>
            </div>
            <h1>Bienvenue, <?php echo isset($user) ? htmlspecialchars($user['prenom'] . ' ' . $user['nom']) : htmlspecialchars($userName); ?></h1>
            <p>Membre depuis <?php echo isset($user) ? date('F Y', strtotime($user['date_inscription'])) : date('F Y'); ?></p>
            
        </div>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-tabs">
            <div class="profile-tab active" data-tab="personal-info">
                <i class="fas fa-user"></i> Informations personnelles
            </div>
            <div class="profile-tab" data-tab="reservations">
                <i class="fas fa-ticket-alt"></i> Réservations récentes
            </div>
        </div>
        
        <!-- Informations personnelles -->
        <div class="profile-content active" id="personal-info">
            <div class="profile-card">
                <h2>Informations personnelles</h2>
                
                <form action="profil.php" method="post">
                    <div class="profile-info">
                        <div class="info-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" value="<?php echo isset($user) ? htmlspecialchars($user['nom']) : ''; ?>" disabled>
                            <small>Le nom ne peut pas être modifié.</small>
                        </div>
                        
                        <div class="info-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" value="<?php echo isset($user) ? htmlspecialchars($user['prenom']) : ''; ?>" disabled>
                            <small>Le prénom ne peut pas être modifié.</small>
                        </div>
                        
                        <div class="info-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo isset($user) ? htmlspecialchars($user['email']) : $userEmail; ?>" disabled>
                            <small>L'email ne peut pas être modifié.</small>
                        </div>
                        
                        <div class="info-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?php echo isset($user) ? htmlspecialchars($user['telephone']) : ''; ?>" disabled>
                            <small>Le numéro de téléphone ne peut pas être modifié.</small>
                        </div>
                    </div>
                    
                </form>
            </div>
        </div>
        
        
        <!-- Réservations récentes -->
        <div class="profile-content" id="reservations">
            <div class="profile-card">
                <h2>Réservations récentes</h2>
                
                <?php if ($reservationsResult && $reservationsResult->num_rows > 0): ?>
                    <?php while ($reservation = $reservationsResult->fetch_assoc()): ?>
                        <div class="reservation-card">
                            <div class="reservation-header">
                                <div class="reservation-title">
                                    <?php echo htmlspecialchars($reservation['gare_depart']); ?> → <?php echo htmlspecialchars($reservation['gare_arrivee']); ?>
                                </div>
                                <div class="reservation-date">
                                    Réservé le <?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?>
                                </div>
                            </div>
                            
                            <div class="reservation-details">
                                <div class="detail-group">
                                    <label>Date de départ</label>
                                    <p><?php echo date('d/m/Y H:i', strtotime($reservation['date_heure_depart'])); ?></p>
                                </div>
                                
                                <div class="detail-group">
                                    <label>Date d'arrivée</label>
                                    <p><?php echo date('d/m/Y H:i', strtotime($reservation['date_heure_arrivee'])); ?></p>
                                </div>
                                
                                <div class="detail-group">
                                    <label>Classe</label>
                                    <p><?php echo isset($reservation['classe']) ? ($reservation['classe'] == 'economique' ? 'Économique' : '1ère classe') : 'Non spécifiée'; ?></p>
                                </div>
                                
                                <div class="detail-group">
                                    <label>Nombre de passagers</label>
                                    <p><?php echo isset($reservation['nb_passagers']) ? $reservation['nb_passagers'] : '1'; ?></p>
                                </div>
                                
                                <div class="detail-group">
                                    <label>Prix total</label>
                                    <p><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> DA</p>
                                </div>
                                
                                <div class="detail-group">
                                    <label>Statut</label>
                                    <p>
                                        <?php 
                                            switch($reservation['statut']) {
                                                case 'confirmee':
                                                    echo '<span class="status-active">Confirmée</span>';
                                                    break;
                                                case 'annulee':
                                                    echo '<span class="status-inactive">Annulée</span>';
                                                    break;
                                                case 'terminee':
                                                    echo '<span class="status-archived">Terminée</span>';
                                                    break;
                                                default:
                                                    echo ucfirst($reservation['statut']);
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="reservation-actions">
                                <button class="btn btn-secondary" onclick="window.location.href='billet.php?id=<?php echo $reservation['id']; ?>'">
                                    <i class="fas fa-eye"></i> Voir le billet
                                </button>
                                
                                <?php if ($reservation['statut'] == 'confirmee'): ?>
                                    <button class="btn btn-danger" onclick="if(confirm('Êtes-vous sûr de vouloir annuler cette réservation?')) window.location.href='demande_remboursement.php?id=<?php echo $reservation['id']; ?>'">
                                        <i class="fas fa-times"></i> Annuler
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div class="form-actions">
                        <button class="btn" onclick="window.location.href='mes_reservations.php'">
                            <i class="fas fa-list"></i> Voir toutes mes réservations
                        </button>
                    </div>
                <?php else: ?>
                    <div class="no-reservations">
                        <i class="fas fa-ticket-alt" style="font-size: 48px; margin-bottom: 15px; color: var(--color-light-gray);"></i>
                        <p>Vous n'avez pas encore de réservations.</p>
                        <button class="btn" style="margin-top: 20px;" onclick="window.location.href='utilisateur.php'">
                            <i class="fas fa-search"></i> Rechercher un trajet
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            // User dropdown menu toggle
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userMenuButton && userDropdown) {
                userMenuButton.addEventListener('click', function() {
                    userDropdown.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                        userDropdown.classList.remove('active');
                    }
                });
            }
            
            // Profile tabs
            const tabs = document.querySelectorAll('.profile-tab');
            const contents = document.querySelectorAll('.profile-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    document.getElementById(target).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
