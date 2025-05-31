<?php

// Démarrer la session
session_start();


// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Vérifier s'il y a un paramètre de redirection
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
    // Vérifier que la redirection est vers un fichier local pour éviter les redirections malveillantes
    if (preg_match('/^[a-zA-Z0-9_-]+\.php$/', $redirect)) {
        header("Location: " . $redirect);
        exit();
    }
}

// Redirection par défaut vers la page de connexion
header("Location: login.php");
exit();
?>
