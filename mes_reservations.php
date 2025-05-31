<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Récupérer les réservations de l'utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.date_reservation, r.prix_total, r.statut, r.classe,
            r.nb_passagers, p.methode as methode_paiement,
            t.date_heure_depart, t.date_heure_arrivee,
            g1.nom as gare_depart, g2.nom as gare_arrivee,
            tr.nom as train_nom, tr.numero as train_numero
        FROM reservations r
        JOIN trajets t ON r.trajet_id = t.id
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        JOIN trains tr ON t.train_id = tr.id
        LEFT JOIN paiements p ON r.id = p.reservation_id
        WHERE r.utilisateur_id = ?
        ORDER BY r.date_reservation DESC
    ");
    $stmt->execute([$user_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vérifier s'il y a des demandes de remboursement en attente
    $stmtRemboursement = $pdo->prepare("
        SELECT reservation_id, statut
        FROM demandes_remboursement
        WHERE utilisateur_id = ?
    ");
    $stmtRemboursement->execute([$user_id]);
    $remboursements = $stmtRemboursement->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des réservations: " . $e->getMessage();
    $reservations = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - Annaba Train</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --color-gray: #a3aaa6;
            --color-steel-blue: #314f70;
            --color-medium-blue: #3b5f87;
            --color-navy: #06245b;
            --color-dark-navy: #06245b;
            --color-deep-navy: #333;
            --color-deep-blue: #051641;
            --color-light-gray: #e0e0e0;
            --color-blue: #1a477d;
        }
        
        .reservations-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 40px;
            color: var(--color-deep-blue);
            font-family: 'Raleway', sans-serif;
        }
        
        .reservation-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .reservation-header {
            background: linear-gradient(to right, var(--color-deep-blue), var(--color-navy));
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .reservation-id {
            font-weight: 600;
            font-size: 18px;
        }
        
        .reservation-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-confirmee {
            background-color: #2ecc71;
        }
        
        .status-pending {
            background-color: #f39c12;
        }
        
        .status-annulee {
            background-color: #e74c3c;
        }
        
        .status-terminee {
            background-color: #3498db;
        }
        
        .status-pending_cancellation {
            background-color: #e67e22;
        }
        
        .reservation-body {
            padding: 20px;
        }
        
        .journey-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .journey-stations {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .station {
            text-align: center;
        }
        
        .station-name {
            font-weight: 600;
            color: #333;
            font-size: 18px;
        }
        
        .station-time {
            color: #777;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .journey-arrow {
            margin: 0 30px;
            color: var(--color-deep-blue);
            font-size: 24px;
        }
        
        .journey-date {
            background-color: #f9f9f9;
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .journey-date-day {
            font-weight: 600;
            color: var(--color-deep-blue);
            font-size: 16px;
        }
        
        .journey-date-time {
            color: #777;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .detail-item {
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .reservation-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .reservation-actions button {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .view-btn {
            background-color: var(--color-deep-blue);
            color: white;
            border: none;
        }
        
        .view-btn:hover {
            background-color: var(--color-blue);
        }
        
        .cancel-btn {
            background-color: white;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        
        .cancel-btn:hover {
            background-color: #fff5f5;
        }
        
        .pending-badge {
            background-color: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .no-reservations {
            text-align: center;
            padding: 50px 20px;
            background-color: #f9f9f9;
            border-radius: 15px;
            margin-top: 30px;
        }
        
        .no-reservations i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-reservations h3 {
            color: #555;
            margin-bottom: 10px;
        }
        
        .no-reservations p {
            color: #777;
            margin-bottom: 20px;
        }
        
        .search-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--color-deep-blue);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background-color: var(--color-blue);
            transform: translateY(-2px);
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            border-left: 4px solid #c62828;
        }
        
        @media (max-width: 768px) {
            .journey-info {
                flex-direction: column;
                gap: 20px;
            }
            
            .journey-stations {
                flex-direction: column;
                gap: 20px;
            }
            
            .journey-arrow {
                transform: rotate(90deg);
                margin: 10px 0;
            }
            
            .reservation-details {
                grid-template-columns: 1fr;
            }
            
            .reservation-actions {
                flex-direction: column;
            }
        }
         /* Footer Styles */


        .footer-content {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
        }

        .footer-section h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 0.5rem;
            font-weight: 600;
        }

        .footer-section h3::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--accent-color);
        }

        .footer-section p {
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
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
                <a href="mes_reservations.php" class="nav-link active"><i class="fas fa-ticket-alt"></i> Mes réservations</a>
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
                        <a href="profil.php"><i class="fas fa-user"></i> Mon profil</a>
                        <a href="mes_reservations.php" class="active"><i class="fas fa-ticket-alt"></i> Mes réservations</a>
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
    
    
    <div class="reservations-container">
        <h1 class="page-title">Mes Réservations</h1>
        
        <?php if (isset($errorMessage)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($reservations)): ?>
            <div class="no-reservations">
                <i class="fas fa-ticket-alt"></i>
                <h3>Vous n'avez pas encore de réservations</h3>
                <p>Réservez votre premier voyage en train dès maintenant !</p>
                <a href="utilisateur.php" class="search-btn">
                    <i class="fas fa-search"></i> Rechercher un trajet
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($reservations as $reservation): ?>
                <div class="reservation-card">
                    <div class="reservation-header">
                        <div class="reservation-id">Réservation #<?php echo $reservation['id']; ?></div>
                        <div class="reservation-status status-<?php echo $reservation['statut']; ?>">
                            <?php 
                                switch ($reservation['statut']) {
                                    case 'confirmee':
                                        echo 'Confirmée';
                                        break;
                                    case 'annulee':
                                        echo 'Annulée';
                                        break;
                                    case 'terminee':
                                        echo 'Terminée';
                                        break;
                                    default:
                                        echo ucfirst($reservation['statut']);
                                }
                            ?>
                        </div>
                    </div>
                    
                    <div class="reservation-body">
                        <div class="journey-info">
                            <div class="journey-stations">
                                <div class="station">
                                    <div class="station-name"><?php echo $reservation['gare_depart']; ?></div>
                                    <div class="station-time"><?php echo date('H:i', strtotime($reservation['date_heure_depart'])); ?></div>
                                </div>
                                
                                <div class="journey-arrow">
                                    <i class="fas fa-long-arrow-alt-right"></i>
                                </div>
                                
                                <div class="station">
                                    <div class="station-name"><?php echo $reservation['gare_arrivee']; ?></div>
                                    <div class="station-time"><?php echo date('H:i', strtotime($reservation['date_heure_arrivee'])); ?></div>
                                </div>
                            </div>
                            
                            <div class="journey-date">
                                <div class="journey-date-day"><?php echo date('d/m/Y', strtotime($reservation['date_heure_depart'])); ?></div>
                                <div class="journey-date-time">
                                    <?php echo $reservation['train_nom']; ?> 
                                    (N°<?php echo $reservation['train_numero']; ?>)
                                </div>
                            </div>
                        </div>
                        
                        <div class="reservation-details">
                            <div class="detail-item">
                                <div class="detail-label">Date de réservation</div>
                                <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Nombre de passagers</div>
                                <div class="detail-value"><?php echo $reservation['nb_passagers']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Classe</div>
                                <div class="detail-value"><?php echo ucfirst($reservation['classe']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Prix total</div>
                                <div class="detail-value"><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> DA</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Méthode de paiement</div>
                                <div class="detail-value"><?php echo ucfirst($reservation['methode_paiement'] ?? 'Non spécifiée'); ?></div>
                            </div>
                        </div>
                        
                        <div class="reservation-actions">
                            <button class="view-btn" onclick="window.location.href='billet_individuel.php?id=<?php echo $reservation['id']; ?>'">
                                <i class="fas fa-eye"></i> Voir le billet
                            </button>
                            
                            <?php if ($reservation['statut'] === 'confirmee' && strtotime($reservation['date_heure_depart']) > time()): ?>
                                <?php if (isset($remboursements[$reservation['id']]) && $remboursements[$reservation['id']] === 'pending'): ?>
                                    <span class="cancel-btn" style="cursor: default;">
                                        <i class="fas fa-clock"></i> Demande d'annulation en cours
                                        <span class="pending-badge">En attente</span>
                                    </span>
                                <?php else: ?>
                                    <button class="cancel-btn" onclick="window.location.href='demande_remboursement.php?id=<?php echo $reservation['id']; ?>'">
                                        <i class="fas fa-undo"></i> Demander un remboursement
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
        });
    </script>
</body>
</html>
