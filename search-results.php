<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté (mais ne pas rediriger)
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Récupérer les paramètres de recherche
$departure = isset($_GET['departure']) ? $_GET['departure'] : '';
$arrival = isset($_GET['arrival']) ? $_GET['arrival'] : '';
$departDate = isset($_GET['depart-date']) ? $_GET['depart-date'] : '';
$returnDate = isset($_GET['return-date']) ? $_GET['return-date'] : '';
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
$tripType = isset($_GET['trip_type']) ? $_GET['trip_type'] : 'one-way';

// Vérifier si les paramètres nécessaires sont fournis
if (empty($departure) || empty($arrival) || empty($departDate)) {
    header("Location: utilisateur.php");
    exit();
}

// Formater les dates pour l'affichage
$formattedDepartDate = !empty($departDate) ? date('d/m/Y', strtotime($departDate)) : '';
$formattedReturnDate = !empty($returnDate) ? date('d/m/Y', strtotime($returnDate)) : '';

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

// Fonction pour calculer la durée entre deux heures
function calculateDuration($departTime, $arriveTime) {
    $depart = new DateTime($departTime);
    $arrive = new DateTime($arriveTime);
    $interval = $depart->diff($arrive);
    
    $hours = $interval->h;
    $minutes = $interval->i;
    
    // Ajuster pour les jours
    if ($interval->d > 0) {
        $hours += $interval->d * 24;
    }
    
    return $hours . 'h ' . ($minutes > 0 ? $minutes . 'min' : '');
}

// Récupérer les trajets aller depuis la base de données
$searchResults = [];
if (!empty($departure) && !empty($arrival) && !empty($departDate)) {
    // Convertir la date au format SQL
    $sqlDepartDate = date('Y-m-d', strtotime($departDate));
    
    // Requête SQL pour trouver les trajets correspondants
    $sql = "SELECT t.id, t.id_gare_depart, t.id_gare_arrivee, t.date_heure_depart, t.date_heure_arrivee, 
            tr.nom as train_nom, tr.numero as train_numero, t.prix, t.economique as places_economique, 
            t.premiere_classe as places_premiere, t.statut,
            g1.nom as gare_depart, g1.ville as ville_depart,
            g2.nom as gare_arrivee, g2.ville as ville_arrivee
            FROM trajets t
            JOIN trains tr ON t.train_id = tr.id
            JOIN gares g1 ON t.id_gare_depart = g1.id_gare
            JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
            WHERE (g1.nom LIKE ? OR g1.ville LIKE ?)
            AND (g2.nom LIKE ? OR g2.ville LIKE ?)
            AND DATE(t.date_heure_depart) = ?
            AND t.statut = 'active'
            ORDER BY t.date_heure_depart ASC";
    
    $stmt = $conn->prepare($sql);
    $departParam = "%$departure%";
    $arrivalParam = "%$arrival%";
    $stmt->bind_param("sssss", $departParam, $departParam, $arrivalParam, $arrivalParam, $sqlDepartDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Extraire l'heure de départ et d'arrivée
            $departDateTime = new DateTime($row['date_heure_depart']);
            $arriveDateTime = new DateTime($row['date_heure_arrivee']);
            
            $departTime = $departDateTime->format('H:i');
            $arriveTime = $arriveDateTime->format('H:i');
            
            // Calculer la durée
            $duration = calculateDuration($row['date_heure_depart'], $row['date_heure_arrivee']);
            
            // Déterminer le type de train
            $trainType = 'Standard';
            if (stripos($row['train_nom'], 'express') !== false) {
                $trainType = 'Express';
            } elseif (stripos($row['train_nom'], 'nuit') !== false) {
                $trainType = 'Train de nuit';
            }
            
            // Déterminer les classes disponibles
            $classOptions = ['Économique'];
            if ($row['places_premiere'] > 0) {
                $classOptions[] = 'Première classe';
            }
            
            // Simuler des arrêts (à remplacer par des données réelles si disponibles)
            $stops = [];
            if ($row['ville_depart'] == 'Annaba' && $row['ville_arrivee'] == 'Alger') {
                $stops = ['Constantine', 'Sétif'];
            } elseif ($row['ville_depart'] == 'Alger' && $row['ville_arrivee'] == 'Annaba') {
                $stops = ['Sétif', 'Constantine'];
            }
            
            // Ajouter le trajet aux résultats
            $searchResults[] = [
                'id' => $row['id'],
                'departure_city' => $row['gare_depart'] . ' (' . $row['ville_depart'] . ')',
                'arrival_city' => $row['gare_arrivee'] . ' (' . $row['ville_arrivee'] . ')',
                'departure_time' => $departTime,
                'arrival_time' => $arriveTime,
                'duration' => $duration,
                'price' => $row['prix'],
                'train_type' => $trainType,
                'train_name' => $row['train_nom'],
                'train_number' => $row['train_numero'],
                'available_seats' => intval($row['places_economique']) + intval($row['places_premiere']),
                'class_options' => $classOptions,
                'stops' => $stops
            ];
        }
    }
    $stmt->close();
}

// Récupérer les trajets retour depuis la base de données
$returnResults = [];
if (!empty($returnDate) && !empty($departure) && !empty($arrival) && !empty($returnDate)) {
    // Convertir la date au format SQL
    $sqlReturnDate = date('Y-m-d', strtotime($returnDate));
    
    // Requête SQL pour trouver les trajets correspondants (inverser départ et arrivée)
    $sql = "SELECT t.id, t.id_gare_depart, t.id_gare_arrivee, t.date_heure_depart, t.date_heure_arrivee, 
            tr.nom as train_nom, tr.numero as train_numero, t.prix, t.economique as places_economique, 
            t.premiere_classe as places_premiere, t.statut,
            g1.nom as gare_depart, g1.ville as ville_depart,
            g2.nom as gare_arrivee, g2.ville as ville_arrivee
            FROM trajets t
            JOIN trains tr ON t.train_id = tr.id
            JOIN gares g1 ON t.id_gare_depart = g1.id_gare
            JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
            WHERE (g1.nom LIKE ? OR g1.ville LIKE ?)
            AND (g2.nom LIKE ? OR g2.ville LIKE ?)
            AND DATE(t.date_heure_depart) = ?
            AND t.statut = 'active'
            ORDER BY t.date_heure_depart ASC";
    
    $stmt = $conn->prepare($sql);
    $departParam = "%$arrival%"; // Inverser départ et arrivée pour le retour
    $arrivalParam = "%$departure%";
    $stmt->bind_param("sssss", $departParam, $departParam, $arrivalParam, $arrivalParam, $sqlReturnDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Extraire l'heure de départ et d'arrivée
            $departDateTime = new DateTime($row['date_heure_depart']);
            $arriveDateTime = new DateTime($row['date_heure_arrivee']);
            
            $departTime = $departDateTime->format('H:i');
            $arriveTime = $arriveDateTime->format('H:i');
            
            // Calculer la durée
            $duration = calculateDuration($row['date_heure_depart'], $row['date_heure_arrivee']);
            
            // Déterminer le type de train
            $trainType = 'Standard';
            if (stripos($row['train_nom'], 'express') !== false) {
                $trainType = 'Express';
            } elseif (stripos($row['train_nom'], 'nuit') !== false) {
                $trainType = 'Train de nuit';
            }
            
            // Déterminer les classes disponibles
            $classOptions = ['Économique'];
            if ($row['places_premiere'] > 0) {
                $classOptions[] = 'Première classe';
            }
            
            // Simuler des arrêts (à remplacer par des données réelles si disponibles)
            $stops = [];
            if ($row['ville_depart'] == 'Annaba' && $row['ville_arrivee'] == 'Alger') {
                $stops = ['Constantine', 'Sétif'];
            } elseif ($row['ville_depart'] == 'Alger' && $row['ville_arrivee'] == 'Annaba') {
                $stops = ['Sétif', 'Constantine'];
            }
            
            // Ajouter le trajet aux résultats avec ID > 1000 pour identifier les trajets retour
            $returnResults[] = [
                'id' => $row['id'] + 1000, // Ajouter 1000 pour distinguer les trajets retour
                'departure_city' => $row['gare_depart'] . ' (' . $row['ville_depart'] . ')',
                'arrival_city' => $row['gare_arrivee'] . ' (' . $row['ville_arrivee'] . ')',
                'departure_time' => $departTime,
                'arrival_time' => $arriveTime,
                'duration' => $duration,
                'price' => $row['prix'],
                'train_type' => $trainType,
                'train_name' => $row['train_nom'],
                'train_number' => $row['train_numero'],
                'available_seats' => intval($row['places_economique']) + intval($row['places_premiere']),
                'class_options' => $classOptions,
                'stops' => $stops
            ];
        }
    }
    $stmt->close();
}

// Fermer la connexion
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de recherche - Annaba Train</title>
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="search-results.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            
            <?php if ($isLoggedIn): ?>
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
            <?php else: ?>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-outline">Se connecter</a>
                <a href="register.php" class="btn btn-primary">S'inscrire</a>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="search-results-container">
        <!-- Search Summary -->
        <section class="search-summary">
            <div class="container">
                <div class="search-details">
                    <h2>Résultats de votre recherche</h2>
                    <div class="journey-details">
                        <div class="route">
                            <span class="city"><?php echo $departure; ?></span>
                            <i class="fas fa-long-arrow-alt-right"></i>
                            <span class="city"><?php echo $arrival; ?></span>
                        </div>
                        <div class="dates">
                            <div class="date-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Aller: <?php echo $formattedDepartDate; ?></span>
                            </div>
                            <?php if (!empty($returnDate)): ?>
                            <div class="date-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Retour: <?php echo $formattedReturnDate; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="passengers-info">
                            <i class="fas fa-users"></i>
                            <span><?php echo $passengers; ?> passager<?php echo $passengers > 1 ? 's' : ''; ?></span>
                        </div>
                    </div>
                    <a href="utilisateur.php" class="modify-search-btn">
                        <i class="fas fa-edit"></i> Modifier la recherche
                    </a>
                </div>
            </div>
        </section>


        <!-- Results Section - Outbound -->
        <section class="results-section">
            <div class="container">
                <h3 class="section-title">Trajets aller - <?php echo $formattedDepartDate; ?></h3>
                
                <div class="results-list">
                    <?php if (empty($searchResults)): ?>
                    <div class="no-results">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Aucun trajet trouvé pour cette recherche. Veuillez essayer avec d'autres critères.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($searchResults as $train): ?>
                        <div class="train-card" data-departure-time="<?php echo $train['departure_time']; ?>" data-price="<?php echo $train['price']; ?>" data-duration="<?php echo $train['duration']; ?>" data-train-type="<?php echo strtolower($train['train_type']); ?>">
                            <div class="train-info">
                                <div class="train-type">
                                    <span class="train-badge <?php echo strtolower(str_replace(' ', '-', $train['train_type'])); ?>">
                                        <?php echo $train['train_type']; ?>
                                    </span>
                                    <span class="train-name">
                                        <?php echo $train['train_name']; ?> (N°<?php echo $train['train_number']; ?>)
                                    </span>
                                </div>
                                <div class="time-info">
                                    <div class="departure">
                                        <span class="time"><?php echo $train['departure_time']; ?></span>
                                        <span class="city"><?php echo $train['departure_city']; ?></span>
                                    </div>
                                    <div class="journey-line">
                                        <span class="duration"><?php echo $train['duration']; ?></span>
                                        <div class="line"></div>
                                    </div>
                                    <div class="arrival">
                                        <span class="time"><?php echo $train['arrival_time']; ?></span>
                                        <span class="city"><?php echo $train['arrival_city']; ?></span>
                                    </div>
                                </div>
                                <div class="train-details">
                                    <div class="seats-info">
                                        <i class="fas fa-chair"></i>
                                        <span><?php echo $train['available_seats']; ?> places disponibles</span>
                                    </div>
                                    <div class="class-info">
                                        <i class="fas fa-ticket-alt"></i>
                                        <span>
                                            <?php echo implode(', ', $train['class_options']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="price-action">
                                <div class="price">
                                    <span class="amount"><?php echo number_format($train['price'], 0, ',', ' '); ?> DA</span>
                                    <span class="per-person">A partir de personne</span>
                                </div>
                                <button class="select-btn" data-train-id="<?php echo $train['id']; ?>">
                                    Sélectionner
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($returnDate)): ?>
        <!-- Results Section - Return -->
        <section class="results-section">
            <div class="container">
                <h3 class="section-title">Trajets retour - <?php echo $formattedReturnDate; ?></h3>
                
                <div class="results-list">
                    <?php if (empty($returnResults)): ?>
                    <div class="no-results">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Aucun trajet trouvé pour le retour. Veuillez essayer avec d'autres critères.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($returnResults as $train): ?>
                        <div class="train-card" data-departure-time="<?php echo $train['departure_time']; ?>" data-price="<?php echo $train['price']; ?>" data-duration="<?php echo $train['duration']; ?>" data-train-type="<?php echo strtolower($train['train_type']); ?>">
                            <div class="train-info">
                                <div class="train-type">
                                    <span class="train-badge <?php echo strtolower(str_replace(' ', '-', $train['train_type'])); ?>">
                                        <?php echo $train['train_type']; ?>
                                    </span>
                                    <span class="train-name">
                                        <?php echo $train['train_name']; ?> (N°<?php echo $train['train_number']; ?>)
                                    </span>
                                </div>
                                <div class="time-info">
                                    <div class="departure">
                                        <span class="time"><?php echo $train['departure_time']; ?></span>
                                        <span class="city"><?php echo $train['departure_city']; ?></span>
                                    </div>
                                    <div class="journey-line">
                                        <span class="duration"><?php echo $train['duration']; ?></span>
                                        <div class="line"></div>
                                        
                                    </div>
                                    <div class="arrival">
                                        <span class="time"><?php echo $train['arrival_time']; ?></span>
                                        <span class="city"><?php echo $train['arrival_city']; ?></span>
                                    </div>
                                </div>
                                <div class="train-details">
                                    <div class="seats-info">
                                        <i class="fas fa-chair"></i>
                                        <span><?php echo $train['available_seats']; ?> places disponibles</span>
                                    </div>
                                    <div class="class-info">
                                        <i class="fas fa-ticket-alt"></i>
                                        <span>
                                            <?php echo implode(', ', $train['class_options']); ?>
                                        </span>
                                    </div>
                                    
                                </div>
                            </div>
                            <div class="price-action">
                                <div class="price">
                                    <span class="amount"><?php echo number_format($train['price'], 0, ',', ' '); ?> DA</span>
                                    <span class="per-person">A partir de personne</span>
                                </div>
                                <button class="select-btn" data-train-id="<?php echo $train['id']; ?>">
                                    Sélectionner
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Overlay pour bloquer l'arrière-plan -->
        <div id="summaryOverlay" class="overlay" style="display: none;"></div>

        <!-- Selected Trains Summary - Version modernisée avec sélection de classe -->
        <section id="selectedTrainsSummary" class="selected-trains-summary" style="display: none;">
            <div class="summary-header">
                <h3>Trajets sélectionnés</h3>
                <div class="price-indicator" id="totalPriceIndicator">0 DA</div>
            </div>
            
            <div class="summary-content">
                <!-- Train aller -->
                <div id="selectedOutbound">
                    <h4>Aller</h4>
                    <div class="train-summary">
                        <p>Veuillez sélectionner un train pour l'aller</p>
                    </div>
                    <div class="class-selection-container" style="display: none;">
                        <h5><i class="fas fa-ticket-alt"></i> Choisissez votre classe</h5>
                        <div class="class-options" id="outbound-class-options">
                            <!-- Les options de classe seront générées dynamiquement par JavaScript -->
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($returnDate)): ?>
                <!-- Train retour -->
                <div id="selectedReturn">
                    <h4>Retour</h4>
                    <div class="train-summary">
                        <p>Veuillez sélectionner un train pour le retour</p>
                    </div>
                    <div class="class-selection-container" style="display: none;">
                        <h5><i class="fas fa-ticket-alt"></i> Choisissez votre classe</h5>
                        <div class="class-options" id="return-class-options">
                            <!-- Les options de classe seront générées dynamiquement par JavaScript -->
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="summary-footer">
                <button id="btnBack" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Retour
                </button>
                <button id="continueBtn" class="continue-btn" disabled>
                    Continuer <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </section>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Variables pour stocker les trains sélectionnés
            let selectedOutbound = null;
            let selectedReturn = null;
            const isRoundTrip = <?php echo !empty($returnDate) ? 'true' : 'false'; ?>;
            const passengerCount = <?php echo $passengers; ?>;
            
            
            // Sélection des trains
            const selectBtns = document.querySelectorAll('.select-btn');
            selectBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const trainId = this.getAttribute('data-train-id');
                    const trainCard = this.closest('.train-card');
                    const isReturn = parseInt(trainId) > 1000; // Identifier si c'est un train retour (ID > 1000)
                    
                    // Réinitialiser les sélections précédentes
                    const section = trainCard.closest('.results-section');
                    const allCards = section.querySelectorAll('.train-card');
                    allCards.forEach(card => {
                        card.classList.remove('selected');
                    });
                    
                    // Marquer ce train comme sélectionné
                    trainCard.classList.add('selected');
                    
                    // Stocker les informations du train sélectionné
                    const trainInfo = {
                        id: trainId,
                        departureCity: trainCard.querySelector('.departure .city').textContent,
                        arrivalCity: trainCard.querySelector('.arrival .city').textContent,
                        departureTime: trainCard.querySelector('.departure .time').textContent,
                        arrivalTime: trainCard.querySelector('.arrival .time').textContent,
                        duration: trainCard.querySelector('.duration').textContent,
                        price: parseInt(trainCard.querySelector('.price .amount').textContent.replace(/\s+/g, ''))
                    };
                    
                    if (isReturn) {
                        selectedReturn = trainInfo;
                        updateSelectedTrainSummary('return', trainInfo);
                    } else {
                        selectedOutbound = trainInfo;
                        updateSelectedTrainSummary('outbound', trainInfo);
                    }
                    
                    // Afficher le résumé des trains sélectionnés et l'overlay
                    document.getElementById('selectedTrainsSummary').style.display = 'block';
                    document.getElementById('summaryOverlay').style.display = 'block';
                    
                    // Bloquer le défilement du corps de la page
                    document.body.style.overflow = 'hidden';
                    
                    // Mettre à jour le prix total et l'état du bouton Continuer
                    updateTotalPrice();
                    updateContinueButtonState();
                });
            });
            
            // Mettre à jour le résumé d'un train sélectionné
            function updateSelectedTrainSummary(type, trainInfo) {
                const container = document.getElementById(`selected${type.charAt(0).toUpperCase() + type.slice(1)}`);
                const summary = container.querySelector('.train-summary');
                const classSelectionContainer = container.querySelector('.class-selection-container');
                const optionsContainer = container.querySelector(`#${type}-class-options`);

                // Afficher le résumé du train
                summary.innerHTML = `
                    <div class="train-card">
                        <div class="train-icon"><i class="fas fa-train"></i></div>
                        <div class="train-details">
                            <div class="route-details">
                                <div class="cities">
                                    <span>${trainInfo.departureCity}</span>
                                    <span class="separator">→</span>
                                    <span>${trainInfo.arrivalCity}</span>
                                </div>
                                <div class="times">
                                    <span>${trainInfo.departureTime}</span>
                                    <span class="duration">${trainInfo.duration}</span>
                                    <span>${trainInfo.arrivalTime}</span>
                                </div>
                            </div>
                        </div>
                        <div class="train-price">
                            ${trainInfo.price.toLocaleString('fr-FR')} DA
                        </div>
                    </div>
                `;

                // Générer les options de classe pour chaque passager
                const economyPrice = trainInfo.price;
                const firstClassPrice = Math.round(economyPrice * 1.5);

                let classOptionsHTML = '';
                for (let i = 0; i < passengerCount; i++) {
                    classOptionsHTML += `
                        <div class="passenger-class" data-passenger="${i}">
                            <h5>Passager ${i + 1}</h5>
                            <div class="class-options">
                                <label class="class-option">
                                    <input type="radio" name="${type}-class-${i}" value="economique" checked>
                                    <div class="class-option-content">
                                        <div class="class-icon"><i class="fas fa-chair"></i></div>
                                        <div class="class-details">
                                            <span class="class-name">Économique</span>
                                            <span class="class-price">${economyPrice.toLocaleString('fr-FR')} DA</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="class-option">
                                    <input type="radio" name="${type}-class-${i}" value="premiere">
                                    <div class="class-option-content">
                                        <div class="class-icon premium"><i class="fas fa-couch"></i></div>
                                        <div class="class-details">
                                            <span class="class-name">Première classe</span>
                                            <span class="class-price">${firstClassPrice.toLocaleString('fr-FR')} DA</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    `;
                }

                optionsContainer.innerHTML = classOptionsHTML;
                classSelectionContainer.style.display = 'block';

                // Enregistrer les écouteurs pour les changements
                optionsContainer.querySelectorAll('input[type="radio"]').forEach(input => {
                    input.addEventListener('change', updateTotalPrice);
                });

                if (type === 'outbound') {
                    selectedOutbound = trainInfo;
                } else {
                    selectedReturn = trainInfo;
                }

                updateTotalPrice();
            }

            function updateTotalPrice() {
                let total = 0;

                ['outbound', 'return'].forEach(type => {
                    const selectedTrain = (type === 'outbound') ? selectedOutbound : selectedReturn;
                    if (!selectedTrain) return;

                    for (let i = 0; i < passengerCount; i++) {
                        const selectedInput = document.querySelector(`input[name="${type}-class-${i}"]:checked`);
                        if (selectedInput) {
                            const basePrice = selectedTrain.price;
                            total += selectedInput.value === 'premiere' ? Math.round(basePrice * 1.5) : basePrice;
                        }
                    }
                });

                document.getElementById('totalPriceIndicator').textContent = `${total.toLocaleString('fr-FR')} DA`;
                updateContinueButtonState();
            }

            // Mettre à jour l'état du bouton retour
            document.getElementById('btnBack').addEventListener('click', function() {
                document.getElementById('summaryOverlay').style.display = 'none';
                document.getElementById('selectedTrainsSummary').style.display = 'none';
                document.body.style.overflow = 'auto';
            });

            // Mettre à jour l'état du bouton Continuer
            function updateContinueButtonState() {
                const continueBtn = document.getElementById('continueBtn');
                
                if (isRoundTrip) {
                    continueBtn.disabled = !(selectedOutbound && selectedReturn);
                } else {
                    continueBtn.disabled = !selectedOutbound;
                }
            }

            // Gérer le clic sur le bouton Continuer
            document.getElementById('continueBtn').addEventListener('click', function () {
                // S'assurer que le train sélectionné existe
                if (!selectedOutbound || (isRoundTrip && !selectedReturn)) return;

                let outboundClasses = [];
                let returnClasses = [];

                let outboundPrice = 0;
                let returnPrice = 0;

                for (let i = 0; i < passengerCount; i++) {
                    // Classe sélectionnée pour l'aller
                    const outboundInput = document.querySelector(`input[name="outbound-class-${i}"]:checked`);
                    if (outboundInput) {
                        outboundClasses.push(outboundInput.value);
                        outboundPrice += (outboundInput.value === 'premiere') 
                            ? Math.round(selectedOutbound.price * 1.5) 
                            : selectedOutbound.price;
                    }

                    // Classe sélectionnée pour le retour
                    if (isRoundTrip) {
                        const returnInput = document.querySelector(`input[name="return-class-${i}"]:checked`);
                        if (returnInput) {
                            returnClasses.push(returnInput.value);
                            returnPrice += (returnInput.value === 'premiere') 
                                ? Math.round(selectedReturn.price * 1.5) 
                                : selectedReturn.price;
                        }
                    }
                }

                // Construire l'URL
                let url = 'reservation.php?';
                url += `outbound=${selectedOutbound.id}`;
                url += `&outbound_classes=${outboundClasses.join(',')}`;
                url += `&outbound_price=${outboundPrice}`;

                if (isRoundTrip && selectedReturn) {
                    url += `&return=${selectedReturn.id}`;
                    url += `&return_classes=${returnClasses.join(',')}`;
                    url += `&return_price=${returnPrice}`;
                }

                url += `&passengers=${passengerCount}`;

                // Rediriger vers la page de réservation
                window.location.href = url;
            });
            
            // Menu utilisateur
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userMenuButton && userDropdown) {
                userMenuButton.addEventListener('click', function() {
                    userDropdown.classList.toggle('active');
                });
                
                // Fermer le menu lorsqu'on clique ailleurs
                document.addEventListener('click', function(event) {
                    if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                        userDropdown.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
