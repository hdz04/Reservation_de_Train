<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Connexion à la base de données - Déplacée ici pour être disponible partout
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $_POST['notification_id']);
    $stmt->execute();
    exit();
}

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Vérifier s'il y a des notifications non lues
$notificationCount = 0;
if ($isLoggedIn) {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE 
    utilisateur_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $userId = $_SESSION['user_id'];
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $notificationCount = $row['count'];
    }
    
    $stmt->close();
}

// Récupérer les destinations populaires
$popularDestinations = [];
$sql = "SELECT t.id, t.date_heure_depart, t.date_heure_arrivee, t.prix, 
        gd.nom AS gare_depart, gd.ville AS ville_depart, 
        ga.nom AS gare_arrivee, ga.ville AS ville_arrivee,
        TIMESTAMPDIFF(HOUR, t.date_heure_depart, t.date_heure_arrivee) AS duree
        FROM trajets t
        JOIN gares gd ON t.id_gare_depart = gd.id_gare
        JOIN gares ga ON t.id_gare_arrivee = ga.id_gare
        WHERE t.date_heure_depart > NOW() 
        AND t.statut = 'active'
        ORDER BY t.date_heure_depart ASC
        LIMIT 6";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $popularDestinations[] = $row;
    }
}

// Redirection si non connecté
if(isset($_POST['go']) && !$isLoggedIn) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation de Train - Annaba</title>
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="search-results.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        /* Styles généraux améliorés */
   
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        section {
            padding: 60px 0;
            position: relative;
        }
        
        h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            position: relative;
            color: var(--primary-color);
        }
        
        h2:after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--accent-color);
            margin: 15px auto;
            border-radius: 2px;
        }
        
        p {
            margin-bottom: 1rem;
            font-size: 1.05rem;
        }
        
        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-button {
            position: relative;
        }
        
        .notification-badge:empty {
            display: none;
        }
        
        /* Animation classes */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Délais d'animation pour les cartes */
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
        .delay-6 { transition-delay: 0.6s; }
        
        /* Sections d'information */
        .info-section {
            background-color: var(--light-bg);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }
        
        .info-section:nth-child(even) {
            background-color: #fff;
        }
        
        .info-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            
        }
        
        .info-text {
            flex: 1;
            min-width: 300px;
        }
        
        .info-image {
            flex: 1;
            min-width: 300px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        
        .info-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
        }
        
        .info-image:hover img {
            transform: scale(1.03);
        }
        
        .info-card {
            background-color: #fff;
            border-radius: var(--border-radius);
            border-radius:10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .info-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.4rem;
        }
        
        .info-card i {
            color: var(--accent-color);
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }
        
        /* Destinations populaires - Design amélioré */
        .routes-section {
            background-color: var(--light-bg);
            padding: 80px 0;
        }
        
        .routes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .route-card {
            background-color: #fff;
            border-radius: var(--border-radius);
            border-radius:10px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .route-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .route-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .route-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .route-card:hover .route-image img {
            transform: scale(1.1);
        }
        
        .route-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .route-cities {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .route-cities i {
            margin: 0 10px;
            color: var(--accent-color);
        }
        
        .route-details {
            margin-top: 15px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .route-detail {
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        
        .route-detail i {
            margin-right: 8px;
            color: var(--accent-color);
        }
        
        .route-price {
            margin-top: 20px;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--accent-color);
        }
        
        .route-date {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .route-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
            align-self: flex-start;
            transition: var(--transition);
        }
        
        .route-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        /* Benefits Section */
        .benefits-section {
            padding: 80px 0;
            background-color: #fff;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .benefit-card {
            background-color: var(--light-bg);
            border-radius: var(--border-radius);
            border-radius:10px;
            padding: 30px;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .benefit-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .benefit-card i {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        
        .benefit-card h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        /* Carte des lignes ferroviaires */
        .map-section {
            padding: 80px 0;
            background-color: var(--light-bg);
        }
        
        #train-map {
            height: 500px;
            width: 100%;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-top: 40px;
        }
        
        .map-legend {
            background-color: white;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            box-shadow: var(--box-shadow);
        }
        
        .map-legend h4 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .legend-color {
            width: 20px;
            height: 10px;
            margin-right: 10px;
            border-radius: 2px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .info-container {
                flex-direction: column;
            }
            
            .routes-grid {
                grid-template-columns: 1fr;
            }
            
            h2 {
                font-size: 2rem;
            }
            
            .benefit-card {
                padding: 20px;
            }
            
            #train-map {
                height: 350px;
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
            
            <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <button class="user-button" id="userMenuButton">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($userName) ?></span>
                        <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge" id="notificationCounter"><?= $notificationCount ?></span>
                        <?php endif; ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="profil.php"><i class="fas fa-user"></i> Mon profil</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="search-container">
          <form id="searchForm" class="search-form" action="search-results.php" method="GET">
            <div class="trip-type">
              <button type="button" class="trip-type-btn active" data-type="one-way">Aller simple</button>
              <button type="button" class="trip-type-btn" data-type="round-trip">Aller-retour</button>
            </div>
            
            <div class="form-content">
              <div class="form-group">
                <label for="departure">De</label>
                <input type="text" id="departure" name="departure" placeholder="Ville de départ" required>
              </div>
              
              <button type="button" class="exchange-btn" id="exchangeBtn">
                <i class="fas fa-exchange-alt"></i>
              </button>
              
              <div class="form-group">
                <label for="arrival">Vers</label>
                <input type="text" id="arrival" name="arrival" placeholder="Ville d'arrivée" required>
              </div>
              
              <div class="form-group">
                <label for="depart-date">Date aller</label>
                <input type="date" id="depart-date" name="depart-date" required>
              </div>
              
              <div class="form-group return-date-group" style="display: none;">
                <label for="return-date">Date retour</label>
                <input type="date" id="return-date" name="return-date">
              </div>
              
              <div class="form-group passengers-dropdown">
                <button type="button" class="passenger-trigger" id="passengerTrigger">
                  <span id="totalPassengers">1 passager</span>
                  <i class="fas fa-chevron-down"></i>
                </button>
                <input type="hidden" id="passengers" name="passengers" value="1">
                <input type="hidden" id="trip_type" name="trip_type" value="one-way">
                
                <div class="passengers-popup" id="passengersPopup">
                  <div class="passenger-type-card">
                    <div class="passenger-info">
                      <div class="passenger-icon">
                        <i class="fas fa-user"></i>
                      </div>
                      <div class="passenger-details">
                        <label>Passagers</label>
                      </div>
                    </div>
                    <div class="passenger-counter">
                      <button type="button" class="counter-btn decrease-passenger">-</button>
                      <span class="passenger-count" id="passenger-count">1</span>
                      <button type="button" class="counter-btn increase-passenger">+</button>
                    </div>
                  </div>
                </div>
              </div>
              
            </div>
            <button type="submit" class="btn btn-search">Rechercher</button>
          </form>
        </div>
    </section>

    <!-- Section 1: Introduction au secteur ferroviaire algérien -->
    <section class="info-section">
        <div class="container">
            <h2 class="animate-on-scroll">Le Secteur Ferroviaire en Algérie</h2>
            <div class="info-container">
                <div class="info-text animate-on-scroll">
                    <p>Le réseau ferroviaire algérien, avec Annaba comme l'un de ses principaux hubs, représente un pilier essentiel du système de transport national. S'étendant sur plus de 4.200 km de voies, ce réseau relie les principales villes du pays et joue un rôle crucial dans le développement économique et social.</p>
                    <p>La Société Nationale des Transports Ferroviaires (SNTF) gère ce vaste réseau, assurant quotidiennement le transport de milliers de voyageurs et de tonnes de marchandises. Annaba, avec sa position stratégique à l'est du pays, constitue un nœud ferroviaire majeur reliant plusieurs wilayas importantes.</p>
                    <p>Le secteur ferroviaire algérien connaît actuellement une phase de modernisation importante, avec des investissements significatifs dans l'infrastructure, le matériel roulant et les systèmes de gestion.</p>
                </div>
                <div class="info-image animate-on-scroll delay-2">
                    <img src=" Train algérien.jpg?height=400&width=600" alt="Train algérien">
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits-section">
        <div class="container">
            <h2 class="animate-on-scroll">Pourquoi voyager en train ?</h2>
            <div class="benefits-grid">
                <div class="benefit-card animate-on-scroll delay-1">
                    <i class="fas fa-clock"></i>
                    <h3>Rapide et ponctuel</h3>
                    <p>Gagnez du temps et évitez les embouteillages. Nos trains respectent les horaires pour vous garantir une arrivée à l'heure.</p>
                </div>
                <div class="benefit-card animate-on-scroll delay-2">
                    <i class="fas fa-leaf"></i>
                    <h3>Écologique</h3>
                    <p>Réduisez votre empreinte carbone en choisissant le train, le mode de transport collectif le plus respectueux de l'environnement.</p>
                </div>
                <div class="benefit-card animate-on-scroll delay-3">
                    <i class="fas fa-couch"></i>
                    <h3>Confortable</h3>
                    <p>Profitez d'un espace confortable pour travailler, lire ou vous détendre pendant votre voyage, avec des sièges ergonomiques.</p>
                </div>
                <div class="benefit-card animate-on-scroll delay-4">
                    <i class="fas fa-wallet"></i>
                    <h3>Économique</h3>
                    <p>Bénéficiez de tarifs avantageux et d'offres spéciales, rendant le train l'une des options de voyage les plus abordables.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Routes - Design amélioré -->
    <section class="routes-section">
        <div class="container">
            <h2 class="animate-on-scroll">Destinations populaires</h2>
            
            <?php if (empty($popularDestinations)): ?>
                <div class="animate-on-scroll" style="text-align: center; padding: 30px;">
                    <i class="fas fa-info-circle" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 20px;"></i>
                    <p>Aucun trajet n'est disponible pour le moment. Veuillez vérifier ultérieurement.</p>
                </div>
            <?php else: ?>
                <div class="routes-grid">
                    <?php 
                    $delay = 1;
                    foreach ($popularDestinations as $destination): 
                        // Formatage des dates
                        $departDate = new DateTime($destination['date_heure_depart']);
                        $arriveDate = new DateTime($destination['date_heure_arrivee']);
                        
                        // Calcul de la durée
                        $duration = $destination['duree'];
                        $durationText = $duration . 'h';
                        if ($duration < 1) {
                            $durationMinutes = $departDate->diff($arriveDate)->i;
                            $durationText = $durationMinutes . ' min';
                        } elseif ($departDate->diff($arriveDate)->i > 0) {
                            $durationText .= ' ' . $departDate->diff($arriveDate)->i . 'min';
                        }
                    ?>
                        <div class="route-card animate-on-scroll delay-<?= $delay ?>">
                            <div class="route-info">
                                <div class="route-cities">
                                    <?= htmlspecialchars($destination['ville_depart']) ?> 
                                    <i class="fas fa-long-arrow-alt-right"></i> 
                                    <?= htmlspecialchars($destination['ville_arrivee']) ?>
                                </div>
                                
                                <div class="route-details">
                                    <div class="route-detail">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?= $departDate->format('d/m/Y') ?></span>
                                    </div>
                                    <div class="route-detail">
                                        <i class="fas fa-clock"></i>
                                        <span><?= $departDate->format('H:i') ?> - <?= $arriveDate->format('H:i') ?></span>
                                    </div>
                                    <div class="route-detail">
                                        <i class="fas fa-hourglass-half"></i>
                                        <span><?= $durationText ?></span>
                                    </div>
                                    <div class="route-detail">
                                        <i class="fas fa-train"></i>
                                        <span>Direct</span>
                                    </div>
                                </div>
                                
                                <div class="route-price">
                                    À partir de <span><?= number_format($destination['prix'], 2, ',', ' ') ?> DA</span>
                                </div>
                                
                                <a href="search-results.php?departure=<?= urlencode($destination['ville_depart']) ?>&arrival=<?= urlencode($destination['ville_arrivee']) ?>&depart-date=<?= $departDate->format('Y-m-d') ?>" class="route-btn">
                                    Réserver maintenant
                                </a>
                            </div>
                        </div>
                    <?php 
                        $delay++;
                        if ($delay > 6) $delay = 1;
                    endforeach; 
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Carte des lignes ferroviaires -->
    <section class="map-section">
        <div class="container">
            <h2 class="animate-on-scroll">Réseau Ferroviaire Algérien</h2>
            <p class="animate-on-scroll" style="text-align: center; max-width: 800px; margin: 0 auto 30px;">Découvrez le réseau ferroviaire algérien avec ses principales lignes reliant les grandes villes du pays. Cette carte interactive vous permet de visualiser les connexions disponibles et de mieux planifier vos voyages.</p>
            
            <div id="train-map" class="animate-on-scroll"></div>
            
            <div class="map-legend animate-on-scroll">
                <h4>Légende</h4>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #0056b3;"></div>
                    <span>Lignes principales</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #28a745;"></div>
                    <span>Lignes secondaires</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #dc3545;"></div>
                    <span>Lignes en construction</span>
                </div>
            </div>
        </div>
    </section>

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

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Elements
            const tripTypeBtns = document.querySelectorAll('.trip-type-btn');
            const returnDateGroup = document.querySelector('.return-date-group');
            const exchangeBtn = document.getElementById('exchangeBtn');
            const departureInput = document.getElementById('departure');
            const arrivalInput = document.getElementById('arrival');
            const departDateInput = document.getElementById('depart-date');
            const returnDateInput = document.getElementById('return-date');
            const passengerTrigger = document.getElementById('passengerTrigger');
            const passengersPopup = document.getElementById('passengersPopup');
            const searchForm = document.getElementById('searchForm');
            const passengersInput = document.getElementById('passengers');
            const tripTypeInput = document.getElementById('trip_type');

            // Set minimum dates
            const today = new Date().toISOString().split('T')[0];
            departDateInput.min = today;
            returnDateInput.min = today;

            // Trip type toggle
            tripTypeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    tripTypeBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    if (btn.dataset.type === 'round-trip') {
                        returnDateGroup.style.display = 'block';
                        returnDateInput.required = true;
                        tripTypeInput.value = 'round-trip';
                    } else {
                        returnDateGroup.style.display = 'none';
                        returnDateInput.required = false;
                        returnDateInput.value = '';
                        tripTypeInput.value = 'one-way';
                    }
                });
            });

            // Exchange button
            exchangeBtn.addEventListener('click', () => {
                const tempValue = departureInput.value;
                departureInput.value = arrivalInput.value;
                arrivalInput.value = tempValue;
            });

            // Date validation
            departDateInput.addEventListener('change', (e) => {
                returnDateInput.min = e.target.value;
                if (returnDateInput.value && new Date(returnDateInput.value) < new Date(e.target.value)) {
                    returnDateInput.value = e.target.value;
                }
            });

            // Passenger selection
            const passengers = {
                passenger: { min: 1, max: 9, current: 1 }
            };

            function updatePassengerCount(increment) {
                const passenger = passengers.passenger;
                const newValue = passenger.current + (increment ? 1 : -1);

                if (newValue >= passenger.min && newValue <= passenger.max) {
                    passenger.current = newValue;
                    document.getElementById('passenger-count').textContent = newValue;
                    
                    // Update hidden field
                    passengersInput.value = newValue;

                    // Update button states
                    document.querySelector('.decrease-passenger').disabled = newValue <= passenger.min;
                    document.querySelector('.increase-passenger').disabled = newValue >= passenger.max;

                    updateTotalPassengers();
                }
            }

            function updateTotalPassengers() {
                const total = passengers.passenger.current;
                document.getElementById('totalPassengers').textContent = `${total} passager${total > 1 ? 's' : ''}`;
            }

            // Initialize passenger counter buttons
            const increaseBtn = document.querySelector('.increase-passenger');
            const decreaseBtn = document.querySelector('.decrease-passenger');

            if (increaseBtn && decreaseBtn) {
                increaseBtn.addEventListener('click', () => updatePassengerCount(true));
                decreaseBtn.addEventListener('click', () => updatePassengerCount(false));

                // Initialize button states
                decreaseBtn.disabled = passengers.passenger.current <= passengers.passenger.min;
                increaseBtn.disabled = passengers.passenger.current >= passengers.passenger.max;
            }

            // Passenger popup toggle
            passengerTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                passengersPopup.classList.toggle('active');
            });

            // Close passenger popup when clicking outside
            document.addEventListener('click', (e) => {
                if (!passengersPopup.contains(e.target) && !passengerTrigger.contains(e.target)) {
                    passengersPopup.classList.remove('active');
                }
            });
            
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

            // Animation au défilement
            function checkScroll() {
                const elements = document.querySelectorAll('.animate-on-scroll');
                const windowHeight = window.innerHeight;
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    if (elementPosition < windowHeight * 0.85) {
                        element.classList.add('visible');
                    }
                });
            }
            
            // Vérifier les éléments visibles au chargement
            checkScroll();
            
            // Vérifier les éléments visibles au défilement
            window.addEventListener('scroll', checkScroll);
            
            // Initialiser la carte
            const map = L.map('train-map').setView([36.7, 3.0], 6);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Ajouter les principales villes
            const cities = [
                {name: "Alger", lat: 36.7538, lng: 3.0588},
                {name: "Oran", lat: 35.6969, lng: -0.6331},
                {name: "Constantine", lat: 36.3650, lng: 6.6147},
                {name: "Annaba", lat: 36.9264, lng: 7.7525},
                {name: "Sétif", lat: 36.1898, lng: 5.4108},
                {name: "Blida", lat: 36.4702, lng: 2.8299},
                {name: "Batna", lat: 35.5552, lng: 6.1742},
                {name: "Béjaïa", lat: 36.7515, lng: 5.0557},
                {name: "Tlemcen", lat: 34.8884, lng: -1.3143}
            ];
            
            // Ajouter des marqueurs pour les villes
            cities.forEach(city => {
                L.marker([city.lat, city.lng])
                    .addTo(map)
                    .bindPopup(`<b>${city.name}</b>`);
            });
            
            // Ajouter les lignes ferroviaires principales
            const mainLines = [
                // Alger - Oran
                [[36.7538, 3.0588], [35.6969, -0.6331]],
                // Alger - Constantine
                [[36.7538, 3.0588], [36.3650, 6.6147]],
                // Constantine - Annaba
                [[36.3650, 6.6147], [36.9264, 7.7525]],
                // Alger - Blida
                [[36.7538, 3.0588], [36.4702, 2.8299]],
                // Constantine - Sétif
                [[36.3650, 6.6147], [36.1898, 5.4108]]
            ];
            
            // Ajouter les lignes secondaires
            const secondaryLines = [
                // Sétif - Béjaïa
                [[36.1898, 5.4108], [36.7515, 5.0557]],
                // Oran - Tlemcen
                [[35.6969, -0.6331], [34.8884, -1.3143]],
                // Constantine - Batna
                [[36.3650, 6.6147], [35.5552, 6.1742]]
            ];
            
            // Ajouter les lignes en construction
            const constructionLines = [
                // Batna - Biskra (exemple)
                [[35.5552, 6.1742], [34.8504, 5.7282]],
                // Béjaïa - Jijel (exemple)
                [[36.7515, 5.0557], [36.8213, 5.7662]]
            ];
            
            // Dessiner les lignes sur la carte
            mainLines.forEach(line => {
                L.polyline(line, {color: '#0056b3', weight: 4}).addTo(map);
            });
            
            secondaryLines.forEach(line => {
                L.polyline(line, {color: '#28a745', weight: 3, dashArray: '5, 10'}).addTo(map);
            });
            
            constructionLines.forEach(line => {
                L.polyline(line, {color: '#dc3545', weight: 3, dashArray: '3, 6'}).addTo(map);
            });

            function updateNotificationCounter() {
                fetch('get_notifications_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const counter = document.getElementById('notificationCounter');
                        if (data.count > 0) {
                            if (!counter) {
                                const badge = document.createElement('span');
                                badge.className = 'notification-badge';
                                badge.id = 'notificationCounter';
                                badge.textContent = data.count;
                                document.querySelector('.user-button').appendChild(badge);
                            } else {
                                counter.textContent = data.count;
                            }
                        } else if (counter) {
                            counter.remove();
                        }
                    });
            }
        });
    </script>
</body>
</html>
