<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Rediriger si l'utilisateur n'est pas connecté
if (!$isLoggedIn) {
    header("Location: login.php");
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

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Récupérer les notifications pour l'utilisateur
$sql = "SELECT * FROM notifications 
        WHERE utilisateur_id = ? OR utilisateur_id IS NULL
        ORDER BY is_read ASC, date_creation DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
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
    <title>Notifications - Annaba Train</title>
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="search-results.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .notifications-container {
            padding: 30px 0;
            min-height: calc(100vh - 80px - 300px);
        }
        
        .notifications-header {
            margin-bottom: 20px;
        }
        
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .notification-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .notification-header {
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        
        .notification-info {
            background-color: #314f70;
        }
        
        .notification-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .notification-success {
            background-color: #198754;
        }
        
        .notification-error {
            background-color: #dc3545;
        }
        
        .notification-date {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .notification-content {
            padding: 15px;
        }
        
        .no-notifications {
            text-align: center;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .no-notifications i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }

        .notification-read {
            opacity: 0.7;
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
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
                    <a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a>
                    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="notifications-container">
        <div class="container">
            <div class="notifications-header">
                <h1>Mes notifications</h1>
            </div>

            <div class="notifications-list">
                <?php if (!empty($notifications)): ?>
                    <button id="markAllRead" class="btn btn-primary mb-3">Marquer tout comme lu</button>
                    
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?= $notification['is_read'] ? 'notification-read' : '' ?>" 
                        data-id="<?= $notification['id'] ?>">
                        <div class="notification-header notification-info">
                            <h3><?php echo isset($notification['content']) ? substr(htmlspecialchars($notification['content']), 0, 50) . '...' : 'Notification'; ?></h3>
                            <span class="notification-date"><?php echo date('d/m/Y H:i', strtotime($notification['date_creation'])); ?></span>
                        </div>
                        <div class="notification-content">
                            <p><?php echo nl2br(htmlspecialchars($notification['content'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <p>Vous n'avez aucune notification pour le moment.</p>
                    </div>
                <?php endif; ?>
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
            
            // Mobile menu toggle
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            
            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileMenu.classList.toggle('active');
                    mobileMenuBtn.classList.toggle('active');
                    
                    // Change icon
                    const icon = mobileMenuBtn.querySelector('i');
                    if (mobileMenu.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            }
            
            document.querySelectorAll('.notification-card').forEach(card => {
                card.addEventListener('click', async () => {
                    if (!card.classList.contains('notification-read')) {
                        const notificationId = card.dataset.id;
                        
                        try {
                            const response = await fetch('mark_notification_read.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ id: notificationId })
                            });
                            
                            if (response.ok) {
                                card.classList.add('notification-read');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                        }
                    }
                });
            });

            document.getElementById('markAllRead')?.addEventListener('click', async () => {
                try {
                    const response = await fetch('mark_notifications_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        document.querySelectorAll('.notification-card').forEach(card => {
                            card.classList.add('notification-read');
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });
    </script>
</body>
</html>
