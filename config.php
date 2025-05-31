<?php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "train"; 

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

// Définir le jeu de caractères
$conn->set_charset("utf8mb4");
?>
