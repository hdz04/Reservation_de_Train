<?php
// Démarrer la session
session_start();

// Récupérer l'URL de redirection si elle existe
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

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

// Traitement du formulaire d'inscription
if (isset($_POST['register'])) {
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';
    
    // Validation des champs
    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($password) || empty($confirm_password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier si l'email existe déjà dans la table utilisateurs
        $sql = "SELECT id FROM utilisateurs WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Hasher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer le nouvel utilisateur avec le rôle 'client'
            $sql = "INSERT INTO utilisateurs (nom, prenom, email, telephone, mot_de_passe, role) VALUES (?, ?, ?, ?, ?, 'client')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nom, $prenom, $email, $telephone, $hashed_password);
            
            if ($stmt->execute()) {
                // Récupérer l'ID du nouvel utilisateur
                $user_id = $conn->insert_id;
                
                // Connexion automatique après inscription
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $prenom . ' ' . $nom;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'client';
                
                // Rediriger vers la page précédente ou la page utilisateur par défaut
                if (!empty($redirect_url)) {
                    // Vérifier si l'URL est valide et commence par http ou /
                    if (filter_var($redirect_url, FILTER_VALIDATE_URL) || substr($redirect_url, 0, 1) === '/') {
                        header("Location: " . $redirect_url);
                    } else {
                        header("Location: utilisateur.php");
                    }
                } else {
                    header("Location: utilisateur.php");
                }
                exit();
            } else {
                $error = "Erreur lors de l'inscription: " . $stmt->error;
            }
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Annaba Train</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
      :root {
            --primary-color: #1a477d;
            --button-primary-hover: #3b5f87;
            --background-color: #f5f5f5;
            --text-color: #333333;
        }
        
        body {
            background-color: var(--background-color);
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            color: var(--text-color);
        }
        
        .register-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
            text-align: center;
        }
        
        .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .logo img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin: 0;
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .register-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .input-group {
            flex: 1;
        }
        
        .input-group {
            position: relative;
            text-align: left;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #555;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 38px;
            transform: translateY(-50%);
            color: #777;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .input-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .register-form button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        .register-form button:hover {
            background-color: var(--button-primary-hover);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .divider span {
            padding: 0 10px;
            color: #777;
            font-size: 14px;
        }
        
        .social-register {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-register a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            color: #333;
            transition: all 0.3s;
            border: 1px solid #ddd;
        }
        
        .social-register a:hover {
            background-color: #eee;
        }
        
        .login-link {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: var(--button-primary-hover);
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: var(--button-primary-hover);
        }
        
        @media (max-width: 576px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <img src="logo.png" alt="Logo Annaba Train">
            <h1>Annaba Train</h1>
        </div>
        
        <h2>Inscription</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form class="register-form" action="register.php" method="post">
            <div class="form-row">
                <div class="input-group">
                    <label for="nom">Nom</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="nom" name="nom" placeholder="Votre nom" required>
                </div>
                
                <div class="input-group">
                    <label for="prenom">Prénom</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required>
                </div>
            </div>
            
            <div class="input-group">
                <label for="email">Email</label>
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Votre email" required>
            </div>
            
            <div class="input-group">
                <label for="telephone">Téléphone</label>
                <i class="fas fa-phone"></i>
                <input type="tel" id="telephone" name="telephone" placeholder="Votre numéro de téléphone" required>
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                </div>
                
                <div class="input-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmer votre mot de passe" required>
                </div>
            </div>
            
            <input type="hidden" id="redirect_url" name="redirect_url" value="<?php echo htmlspecialchars($redirect); ?>">
            
            <button type="submit" name="register">S'inscrire</button>
        </form>
        
        
        <div class="login-link">
            Vous avez déjà un compte? <a href="login.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>">Se connecter</a>
        </div>
        
        <a href="utilisateur.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour à l'accueil
        </a>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Récupérer le paramètre redirect de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const redirectParam = urlParams.get('redirect');
        
        const redirectField = document.getElementById('redirect_url');
        
        // Si le paramètre existe, l'utiliser
        if (redirectParam) {
            redirectField.value = decodeURIComponent(redirectParam);
            console.log("URL de redirection depuis paramètre: " + redirectField.value);
        } 
        // Sinon, utiliser le referrer s'il existe et n'est pas la page de login ou register
        else if (document.referrer && 
                !document.referrer.includes('login.php') && 
                !document.referrer.includes('register.php')) {
            redirectField.value = document.referrer;
            console.log("URL de redirection depuis referrer: " + redirectField.value);
        }
        
        // Vérifier si une URL a été stockée dans localStorage (pour les redirections depuis JavaScript)
        const storedRedirect = localStorage.getItem('redirect_after_login');
        if (storedRedirect) {
            redirectField.value = storedRedirect;
            console.log("URL de redirection depuis localStorage: " + redirectField.value);
            // Nettoyer après utilisation
            localStorage.removeItem('redirect_after_login');
        }
    });
</script>
</body>
</html>
