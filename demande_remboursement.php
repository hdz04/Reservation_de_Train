<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Vérifier si l'ID de réservation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: mes_reservations.php");
    exit();
}

$reservationId = intval($_GET['id']);
$userId = $_SESSION['user_id'];
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

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

// Vérifier si la réservation existe et appartient à l'utilisateur
$sql = "SELECT r.id, r.date_reservation, r.prix_total, r.statut, r.classe,
               t.date_heure_depart, t.date_heure_arrivee,
               g1.nom AS gare_depart, g2.nom AS gare_arrivee,
               tr.nom AS train_nom, tr.numero AS train_numero
        FROM reservations r
        JOIN trajets t ON r.trajet_id = t.id
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        JOIN trains tr ON t.train_id = tr.id
        WHERE r.id = ? AND r.utilisateur_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reservationId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: mes_reservations.php");
    exit();
}

$reservation = $result->fetch_assoc();

// Vérifier si la réservation est déjà annulée
if ($reservation['statut'] !== 'confirmee') {
    header("Location: mes_reservations.php");
    exit();
}

// Vérifier si une demande de remboursement existe déjà
$sql = "SELECT id FROM demandes_remboursement WHERE reservation_id = ? AND utilisateur_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reservationId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header("Location: mes_reservations.php");
    exit();
}

// Traiter le formulaire de demande de remboursement
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Insérer la demande de remboursement
    $sql = "INSERT INTO demandes_remboursement (reservation_id, utilisateur_id, date_demande, statut) 
            VALUES (?, ?, NOW(), 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $reservationId, $userId);
    
    if ($stmt->execute()) {
        // Mettre à jour le statut de la réservation
        $sql = "UPDATE reservations SET statut = 'en_attente_annulation' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        
        $message = "Votre demande de remboursement a été envoyée avec succès. Vous serez notifié dès qu'elle sera traitée.";
    } else {
        $error = "Une erreur est survenue lors de l'envoi de votre demande. Veuillez réessayer.";
    }
}

// Fermer la connexion
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de remboursement - Annaba Train</title>
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

       


        .refund-container {
            padding: 30px 0;
            min-height: calc(100vh - 80px - 300px);
        }
        
        .refund-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .refund-header {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            color: white;
            padding: 20px;
        }
        
        .refund-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .refund-header p {
            margin: 5px 0 0;
            opacity: 0.8;
        }
        
        .refund-body {
            padding: 20px;
        }
        
        .reservation-summary {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .summary-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            color: #666;
        }
        
        .summary-value {
            font-weight: 500;
        }
        
        .refund-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(6, 36, 91, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn-cancel {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background-color: #e9ecef;
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background-color: var(--primary-dark);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .refund-policy {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 30px;
        }
        
        .policy-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .policy-text {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn-cancel, .btn-submit {
                width: 100%;
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
            background-color: #314f70;
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
        </nav>
    </header>

    <!-- Main Content -->
    <main class="refund-container">
        <div class="container">
            <div class="refund-card">
                <div class="refund-header">
                    <h2>Demande de remboursement</h2>
                    <p>Veuillez remplir ce formulaire pour demander le remboursement de votre réservation</p>
                </div>
                
                <div class="refund-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($message)): ?>
                        <div class="reservation-summary">
                            <div class="summary-title">
                                <i class="fas fa-info-circle"></i> Détails de la réservation
                            </div>
                            
                            <div class="summary-row">
                                <div class="summary-label">Numéro de réservation</div>
                                <div class="summary-value">#<?php echo $reservation['id']; ?></div>
                            </div>
                            
                            <div class="summary-row">
                                <div class="summary-label">Trajet</div>
                                <div class="summary-value"><?php echo $reservation['gare_depart'] . ' → ' . $reservation['gare_arrivee']; ?></div>
                            </div>
                            
                            <div class="summary-row">
                                <div class="summary-label">Date et heure</div>
                                <div class="summary-value"><?php echo date('d/m/Y H:i', strtotime($reservation['date_heure_depart'])); ?></div>
                            </div>
                            
                            <div class="summary-row">
                                <div class="summary-label">Train</div>
                                <div class="summary-value"><?php echo $reservation['train_nom'] . ' (N°' . $reservation['train_numero'] . ')'; ?></div>
                            </div>
                            
                            <div class="summary-row">
                                <div class="summary-label">Classe</div>
                                <div class="summary-value"><?php echo ucfirst($reservation['classe']); ?></div>
                            </div>
                            
                            <div class="summary-row">
                                <div class="summary-label">Montant total</div>
                                <div class="summary-value"><?php echo number_format($reservation['prix_total'], 2, ',', ' '); ?> DA</div>
                            </div>
                        </div>
                        
                        <form method="post" action="" class="refund-form">
                            <div class="form-actions">
                                <a href="mes_reservations.php" class="btn-cancel">
                                    <i class="fas fa-arrow-left"></i> Annuler
                                </a>
                                
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i> Envoyer la demande
                                </button>
                            </div>
                        </form>
                        
                        <div class="refund-policy">
                            <div class="policy-title">
                                <i class="fas fa-exclamation-triangle"></i> Politique de remboursement
                            </div>
                            <div class="policy-text">
                                <p>Veuillez noter que les demandes de remboursement sont soumises aux conditions suivantes :</p>
                                <ul>
                                    <li>Les demandes doivent être effectuées au moins 24 heures avant le départ du train.</li>
                                    <li>Des frais d'annulation peuvent s'appliquer selon la date de votre demande.</li>
                                    <li>Le remboursement sera effectué sur le même mode de paiement que celui utilisé lors de l'achat.</li>
                                    <li>Le traitement de votre demande peut prendre jusqu'à 7 jours ouvrables.</li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-actions" style="justify-content: center;">
                            <a href="mes_reservations.php" class="btn-submit">
                                <i class="fas fa-arrow-left"></i> Retour à mes réservations
                            </a>
                        </div>
                    <?php endif; ?>
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
