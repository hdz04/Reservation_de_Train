<?php
// 🟡 Démarrer la session pour gérer les données utilisateur
session_start();

// 🟡 Récupérer l'URL de redirection si elle a été fournie (pour retourner à la page précédente)
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// 🟡 Si l'utilisateur est déjà connecté, on le redirige automatiquement
if (isset($_SESSION['user_id'])) {
    if (!empty($redirect)) {
        header("Location: " . $redirect); // Revenir à la page précédente
    } else if ($_SESSION['user_role'] == 'admin') {
        header("Location: admin.php"); // Accès admin
    } else {
        header("Location: utilisateur.php"); // Accès utilisateur normal
    }
    exit(); // Fin du script après redirection
}

// 🟡 Connexion à la base de données
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "train";

// 🟡 Création de la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// 🟡 Vérifier la connexion
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// 🟡 Si le formulaire a été soumis (bouton nommé "login")
if (isset($_POST['login'])) {
    // Sécuriser l'email
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    // Mot de passe brut (pas de filtrage ici pour ne pas le modifier)
    $password = $_POST['password'];

    // Récupérer l'URL de redirection depuis le formulaire
    $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';

    // Préparer une requête SQL avec un paramètre sécurisé (évite les injections SQL)
    $sql = "SELECT id, nom, prenom, email, mot_de_passe, role FROM utilisateurs WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email); // On insère l'email dans la requête
    $stmt->execute(); // Exécuter la requête
    $result = $stmt->get_result(); // Récupérer le résultat

    // 🟡 Vérifier si un utilisateur avec cet email existe
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); // Récupérer les infos de l'utilisateur

        // Vérifier que le mot de passe saisi correspond à celui enregistré
        if (password_verify($password, $user['mot_de_passe']) || $password === $user['mot_de_passe']) {
            session_regenerate_id(true); // Empêche le vol de session

            // Sauvegarder les données dans la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = htmlspecialchars($user['prenom'] . ' ' . $user['nom']);
            $_SESSION['user_email'] = htmlspecialchars($user['email']);
            $_SESSION['user_role'] = $user['role'];

            // Journaliser la connexion réussie (utile pour l’administrateur ou la sécurité)
            error_log("Connexion réussie pour l'utilisateur ID: " . $user['id']);

            // Redirection après connexion selon le rôle ou l'origine
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } else if (!empty($redirect_url)) {
                header("Location: " . $redirect_url);
            } else {
                header("Location: utilisateur.php");
            }
            exit(); // Important d'arrêter le script après la redirection
        } else {
            // Mot de passe incorrect
            error_log("Tentative échouée pour l'email: " . $email);
            $error = "Email ou mot de passe incorrect.";
        }
    } else {
        // Email non trouvé
        error_log("Tentative échouée pour l'email: " . $email);
        $error = "Email ou mot de passe incorrect.";
    }

    // Libérer la mémoire
    $stmt->close();
}

// Fermer la connexion à la base de données
$conn->close();
?>

<!-- Le reste est en HTML : interface utilisateur -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Annaba Train</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
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
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .login-form .input-group {
            position: relative;
        }
        
        .login-form .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .login-form input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .login-form button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-form button:hover {
            background-color: var(--button-primary-hover);
        }
        
        .social-login {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-login a {
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
        
        .social-login a:hover {
            background-color: #eee;
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
        
        .form-links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .form-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .form-links a:hover {
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
    </style>

    <!-- 🟡 Script JavaScript pour insérer l'URL précédente -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const redirectParam = urlParams.get('redirect');
            if (redirectParam) {
                document.getElementById('redirect_url').value = redirectParam;
            } else if (document.referrer &&
                       !document.referrer.includes('login.php') &&
                       !document.referrer.includes('register.php')) {
                document.getElementById('redirect_url').value = document.referrer;
            }
        });
    </script>
</head>
<body>
    <div class="login-container">
        <!-- 🟡 Logo et nom de l'application -->
        <div class="logo">
            <img src="logo.png" alt="Logo Annaba Train">
            <h1>Annaba Train</h1>
        </div>

        <h2>Connexion</h2>

        <!-- 🟡 Affichage des messages d'erreur -->
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- 🟡 Formulaire de connexion -->
        <form class="login-form" action="login.php" method="post">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required />
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Mot de passe" required />
            </div>
            <!-- 🟡 Champ caché pour redirection après login -->
            <input type="hidden" id="redirect_url" name="redirect_url" value="<?php echo htmlspecialchars($redirect); ?>">
            <button type="submit" name="login">Se connecter</button>
        </form>

        <div class="divider"><span>ou</span></div>

        <!-- 🟡 Lien vers la page d'inscription -->
        <div class="form-links">
            <a href="register.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>">S'inscrire</a>
        </div>

        <!-- 🟡 Retour à la page d’accueil -->
        <a href="utilisateur.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour à l'accueil
        </a>
    </div>
</body>
</html>
