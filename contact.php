<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';
$userEmail = $isLoggedIn ? $_SESSION['user_email'] : '';

// Variables pour les messages
$success = false;
$error = "";

// Traitement du formulaire de contact
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Validation simple
    if (empty($name) || empty($email) || empty($message)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide";
    } else {
        // Connexion à la base de données
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "train";
        
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            $error = "Erreur de connexion à la base de données";
        } else {
            // Insérer le message dans la base de données
            $sql = "INSERT INTO messages_contact (nom, email, message, date_envoi, utilisateur_id) 
                    VALUES (?, ?, ?, NOW(), ?)";
            $stmt = $conn->prepare($sql);
            
            $userId = $isLoggedIn ? $_SESSION['user_id'] : null;
            $stmt->bind_param("sssi", $name, $email, $message, $userId);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Erreur lors de l'envoi du message: " . $stmt->error;
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Annaba Train</title>
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="search-results.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .contact-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .contact-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .contact-header p {
            color: #666;
        }
        
        .contact-content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .contact-form {
            flex: 1;
            min-width: 300px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .contact-info {
            flex: 1;
            min-width: 300px;
        }
        
        .info-card {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .info-card h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .info-text h4 {
            margin-bottom: 5px;
        }
        
        .info-text p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(5, 22, 65, 0.1);
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .submit-btn:hover {
            background-color: var(--button-primary-hover);
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .contact-content {
                flex-direction: column;
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
                <a href="utilisateur.php" class="nav-link"><i class="fas fa-home"></i> Accueil</a>
                <?php if ($isLoggedIn): ?>
                    <a href="mes_reservations.php" class="nav-link"><i class="fas fa-ticket-alt"></i> Mes réservations</a>
                <?php endif; ?>
                <a href="contact.php" class="nav-link active"><i class="fas fa-envelope"></i> Contact</a>
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
                        <a href="reservation.php"><i class="fas fa-ticket-alt"></i> Mes réservations</a>
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
    <main>
        <div class="contact-container">
            <div class="contact-header">
                <h1>Contactez-nous</h1>
                <p>Nous sommes là pour répondre à toutes vos questions</p>
            </div>
            
            <div class="contact-content">
                <div class="contact-form">
                    <?php if ($success): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i> Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="contact.php">
                        <div class="form-group">
                            <label for="name">Nom complet</label>
                            <input type="text" id="name" name="name" value="<?php echo $isLoggedIn ? htmlspecialchars($userName) : ''; ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Adresse email</label>
                            <input type="email" id="email" name="email" value="<?php echo $isLoggedIn ? htmlspecialchars($userEmail) : ''; ?>" readonly>
                        </div>     
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">Envoyer le message</button>
                    </form>
                </div>
                
                <div class="contact-info">
                    <div class="info-card">
                        <h3>Informations de contact</h3>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-text">
                                <h4>Adresse</h4>
                                <p>Gare d'Annaba, Boulevard de l'ALN, Annaba, Algérie</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-text">
                                <h4>Téléphone</h4>
                                <p>+213 XX XX XX XX</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-text">
                                <h4>Email</h4>
                                <p>contact@annaba-train.dz</p>
                            </div>
                        </div>
                    
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