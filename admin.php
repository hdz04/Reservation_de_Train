<?php

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Récupérer les informations de l'utilisateur
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Administrateur';
$userRole = $_SESSION['user_role'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Annaba Train</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="logo.png" alt="Logo Annaba Train">
                <h1>Annaba Train</h1>
            </div>

            <ul>
                <li><a href="#users" id="usersLink"><i class="fas fa-users"></i> Utilisateurs</a></li>
                <li><a href="#trips" id="tripsLink"><i class="fas fa-route"></i> Trajets</a>
                    <ul>
                        <li><a href="#" class="tab-link" data-tab="addTrip">Ajouter un trajet</a></li>
                        <li><a href="#" class="tab-link" data-tab="listTrips">Liste des trajets</a></li>
                    </ul>
                </li>
                <li><a href="#bookings" id="bookingsLink"><i class="fas fa-ticket-alt"></i> Réservations</a>
                    <ul>
                        <li><a href="#" class="tab-link" data-tab="cancelRequests">Demandes d'annulation</a></li>
                    </ul>
                </li>
                <li><a href="#trains" id="trainsLink"><i class="fas fa-train"></i> Trains</a></li>
                <li><a href="logout.php?redirect=utilisateur.php" class="logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Section Utilisateurs -->
            <div id="usersSection" class="section-content" style="display: none;">
                <h2>Liste des Utilisateurs</h2>
                
                <div class="filters">
                    <input type="text" id="searchUsers" placeholder="Rechercher un utilisateur...">
                    <button onclick="searchUsers()"><i class="fas fa-search"></i> Rechercher</button>
                </div>
                
                <div class="users-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Date d'inscription</th>
                                <th>Réservations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Les utilisateurs seront chargés ici dynamiquement -->
                            <tr>
                                <td colspan="8" style="text-align: center;">Chargement des utilisateurs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Trip Management Section -->
            <div id="tripsSection" class="section-content" style="display: none;">
                <!-- Add Trip Form -->
<div id="addTrip" class="tab-content">
    <form action="insert_trip.php" method="post" id="addTripForm">
        <h2>Ajouter un nouveau trajet</h2>
        <div class="form-group">
            <!-- Gare de départ -->
            <select name="gare_depart" id="gare_depart" required>
                <option value="">Sélectionner la gare de départ</option>
                <?php
                $conn = new mysqli("localhost", "root", "", "train");
                if ($conn->connect_error) {
                    echo "<option value=''>Erreur de connexion à la base de données</option>";
                } else {
                    $result = $conn->query("SELECT id_gare, nom, ville FROM gares ORDER BY ville ASC");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $label = htmlspecialchars($row['nom'] . " (" . $row['ville'] . ")");
                            echo "<option value='{$row['id_gare']}'>{$label}</option>";
                        }
                    } else {
                        echo "<option value=''>Aucune gare trouvée</option>";
                    }
                }
                ?>
            </select>

            <!-- Gare d'arrivée -->
            <select name="gare_arrivee" id="gare_arrivee" required>
                <option value="">Sélectionner la gare d'arrivée</option>
                <?php
                // Réutiliser la même connexion ou en créer une nouvelle
                if (!$conn || $conn->connect_error) {
                    $conn = new mysqli("localhost", "root", "", "train");
                }
                
                if ($conn->connect_error) {
                    echo "<option value=''>Erreur de connexion à la base de données</option>";
                } else {
                    $result = $conn->query("SELECT id_gare, nom, ville FROM gares ORDER BY ville ASC");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $label = htmlspecialchars($row['nom'] . " (" . $row['ville'] . ")");
                            echo "<option value='{$row['id_gare']}'>{$label}</option>";
                        }
                    } else {
                        echo "<option value=''>Aucune gare trouvée</option>";
                    }
                    // Fermer la connexion seulement à la fin
                    $conn->close();
                }
                ?>
            </select>

            <input type="datetime-local" placeholder="Date et heure de départ" name="date_heure_depart" required>
            <input type="datetime-local" placeholder="Date et heure d'arrivée" name="date_heure_arrivee" required>
            <select id="train-select" name="train_id" required>
                <option value="">Sélectionner un train</option>
            </select>

            <input type="number" placeholder="Prix" name="Prix" required>
            <input type="number" placeholder="Nombre de sièges en classe économique" name="economique" required>
            <input type="number" placeholder="Nombre de sièges en 1er classe" name="premiere_classe">
            <button type="submit" name="Ajouter"><i class="fas fa-plus-circle"></i> Ajouter le trajet</button>
        </div>
    </form>
</div>

            <!-- Edit Trip Modal -->
<div id="editTripModal" class="modal">
  <div class="modal-content">

    <div class="modal-header">
      <h3>Modifier le trajet</h3>
      <button class="close" type="button" onclick="closeEditTripModal()">&times;</button>
    </div>

    <!-- Formulaire de modification -->
    <form id="editTripFormModal">
 
      <input type="hidden" id="modal_tripId" name="trip_id">

  
      <div class="form-row">
        <div class="form-group">
          <label for="modal_edit_gare_depart">Gare de départ</label>
          <select id="modal_edit_gare_depart" name="gare_depart" required></select>
        </div>

        <div class="form-group">
          <label for="modal_edit_gare_arrivee">Gare d'arrivée</label>
          <select id="modal_edit_gare_arrivee" name="gare_arrivee" required></select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="modal_edit_date_depart">Date & Heure de départ</label>
          <input type="datetime-local" id="modal_edit_date_depart" name="date_heure_depart" required>
        </div>

        <div class="form-group">
          <label for="modal_edit_date_arrivee">Date & Heure d'arrivée</label>
          <input type="datetime-local" id="modal_edit_date_arrivee" name="date_heure_arrivee" required>
        </div>
      </div>

      <div class="form-group">
        <label for="modal_edit_train">Train</label>
        <select id="modal_edit_train" name="train_id" required></select>
      </div>

      <div class="form-group">
        <label for="modal_edit_prix">Prix (DA)</label>
        <input type="number" id="modal_edit_prix" name="Prix" min="0" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="modal_edit_economique">Places économiques</label>
          <input type="number" id="modal_edit_economique" name="economique" min="0" required>
        </div>

        <div class="form-group">
          <label for="modal_edit_premiere">Places 1ère classe</label>
          <input type="number" id="modal_edit_premiere" name="premiere_classe" min="0">
        </div>
      </div>

      <div class="form-group">
        <label for="modal_edit_statut">Statut</label>
        <select id="modal_edit_statut" name="statut" required>
          <option value="active">Actif</option>
          <option value="annulee">Annulé</option>
          <option value="terminee">Terminé</option>
        </select>
      </div>

      <!-- Boutons d'action -->
      <div class="modal-actions">
        <button type="submit">Enregistrer</button>
        <button type="button" onclick="closeEditTripModal()">Annuler</button>
      </div>
    </form>
  </div>
</div>

                <!-- List Trips -->
                <div id="listTrips" class="tab-content">
                    <h2>Liste des Trajets</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>N° de Trajet</th>
                                <th>Départ</th>
                                <th>Arrivée</th>
                                <th>Date et Heure</th>
                                <th>Statut</th>
                                <th>Réservations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tripsTableBody">
                            <!-- Table rows will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Booking Management Section -->
            <div id="bookingsSection" class="section-content" style="display: none;">
                <!-- Demandes d'annulation -->
                <div id="cancelRequests" class="tab-content active">
                    <h2>Demandes d'annulation de réservation</h2>
                    
                    <div id="cancelRequestsContainer">
                        <!-- Les demandes d'annulation seront affichées ici -->
                    </div>
                </div>
            </div>

          
    
    <!-- Modal pour les détails de l'utilisateur -->
    <div id="userDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Détails de l'utilisateur</h3>
                <span class="close" onclick="closeUserDetailsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="user-details">
                    <div class="user-info">
                        <h4>Informations personnelles</h4>
                        <div id="userInfoContent">
                            <!-- Les informations de l'utilisateur seront chargées ici -->
                        </div>
                    </div>
                    
                    <div class="user-reservations">
                        <h4>Réservations</h4>
                        <div id="userReservationsContent">
                            <!-- Les réservations de l'utilisateur seront chargées ici -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trains Management Section -->
<div id="trainsSection" class="section-content" style="display: none;">
    <h2>Gestion des Trains</h2>
    <div class="filters">
        <input type="text" id="searchTrain" placeholder="Rechercher un train...">
        <select id="filterTrainStatus">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="retired">Retiré</option>
        </select>
        <button onclick="searchTrains()"><i class="fas fa-search"></i> Rechercher</button>
        <button onclick="showAddTrainForm()" id="addTrainButton"><i class="fas fa-plus-circle"></i> Ajouter un train</button>
    </div>
    
    <div class="train-cards" id="trainsList">
        <!-- Train cards will be populated dynamically -->
    </div>
    
    </div>

    <!-- Add Train Form -->
<!-- Fenêtre modale pour l'ajout d'un train -->
<div id="addTrainForm" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Ajouter un nouveau train</h3>
      <button class="close" type="button" onclick="hideAddTrainForm()">&times;</button>
    </div>

    <form id="trainForm">
      <!-- Numéro et nom du train -->
      <div class="form-row">
        <div class="form-group">
          <label for="numero">Numéro du train</label>
          <input type="text" id="numero" name="numero" required>
        </div>

        <div class="form-group">
          <label for="train_name">Nom du train</label>
          <input type="text" id="train_name" name="train_name" required>
        </div>
      </div>

      <!-- Type caché (par défaut : régional) -->
      <input type="hidden" name="train_type" value="regional">

      <!-- Capacité 1ère classe et économique -->
      <div class="form-row">
        <div class="form-group">
          <label for="capacite_premiere">1ère classe</label>
          <input type="number" id="capacite_premiere" name="capacite_premiere" min="0" required>
        </div>

        <div class="form-group">
          <label for="capacite_economique">Classe économique</label>
          <input type="number" id="capacite_economique" name="capacite_economique" min="0" required>
        </div>
      </div>

      <!-- Capacité totale (lecture seule) -->
      <div class="form-group">
        <label for="train_capacity">Capacité totale</label>
        <input type="number" id="train_capacity" name="train_capacity" min="1" readonly required>
      </div>

      <!-- Statut du train -->
      <div class="form-group">
        <label for="train_status">Statut</label>
        <select id="train_status" name="train_status" required>
          <option value="active">Actif</option>
          <option value="retired">Retiré</option>
        </select>
      </div>

      <!-- Boutons d'action -->
      <div class="modal-actions">
        <button type="submit" name="AddTrain">Enregistrer</button>
        <button type="button" onclick="hideAddTrainForm()">Annuler</button>
      </div>
    </form>
  </div>
</div>


    <!-- Modal pour envoyer une notification -->
    <div id="notificationModal" class="modal-notification">
        <div class="modal-notification-content">
            <div class="modal-notification-header">
                <h3>Envoyer une notification</h3>
                <span class="close-modal" onclick="closeNotificationModal()">&times;</span>
            </div>
            <form id="notificationForm">
                <input type="hidden" id="notification_utilisateur_id" name="utilisateur_id">
                <input type="hidden" id="notification_request_id" name="request_id">
                <input type="hidden" id="notification_action" name="action">
                
                <div class="form-group" id="montant_group">
                    <label for="montant_rembourse">Montant à rembourser (DA):</label>
                    <input type="number" id="montant_rembourse" name="montant_rembourse" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="notification_message">Message:</label>
                    <textarea id="notification_message" name="message" rows="4" required></textarea>
                </div>
                
                <div class="form-actions" style="text-align: right; margin-top: 15px;">
                    <button type="button" onclick="closeNotificationModal()">Annuler</button>
                    <button type="submit">Envoyer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
          
            
            // Afficher la section utilisateurs par défaut
            showSection('users');
            
            // Navigation entre les sections
            const navLinks = document.querySelectorAll('.sidebar > ul > li > a:not(.logout)');

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Identifier la section à afficher
                    const sectionId = this.getAttribute('href').substring(1);
                    
                    // Si le lien a un sous-menu, simplement basculer l'affichage du sous-menu
                    const submenu = this.nextElementSibling;
                    if (submenu && submenu.tagName === 'UL') {
                        // Fermer tous les autres sous-menus
                        document.querySelectorAll('.sidebar > ul > li > ul').forEach(menu => {
                            if (menu !== submenu) {
                                menu.style.display = 'none';
                            }
                        });
                        
                        // Basculer l'affichage du sous-menu actuel
                        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
                        return;
                    }
                    
                    // Sinon, afficher la section correspondante
                    showSection(sectionId);
                });
            });
            
            // Gestion des onglets
            const tabLinks = document.querySelectorAll('.tab-link');
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const tabId = this.getAttribute('data-tab');
                    
                    // Déterminer la section parente
                    let sectionId;
                    if (tabId.includes('Trip')) {
                        sectionId = 'trips';
                    } else if (tabId === 'cancelRequests') {
                        sectionId = 'bookings';
                    } else if (tabId.includes('User')) {
                        sectionId = 'users';
                    } else if (tabId.includes('Train')) {
                        sectionId = 'trains';
                    }
                    
                    // Afficher la section parente
                    showSection(sectionId);
                    
                    // Afficher l'onglet spécifique
                    showTab(tabId, sectionId);
                });
            });
            
            // Ajouter un écouteur d'événement pour la touche Entrée dans le champ de recherche
            const searchInput = document.getElementById('searchUsers');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchUsers();
                    }
                });
            }
            
            // Fermer les modals lorsqu'on clique en dehors
            window.addEventListener('click', function(event) {
                const userModal = document.getElementById('userDetailsModal');
                const notificationModal = document.getElementById('notificationModal');
                const addTrainModal = document.getElementById('addTrainForm');
                
                if (event.target === userModal) {
                    userModal.style.display = 'none';
                }
                
                if (event.target === notificationModal) {
                    notificationModal.style.display = 'none';
                }
                
                if (event.target === addTrainModal) {
                    addTrainModal.style.display = 'none';
                }
            });
            
            // Initialiser les écouteurs d'événements pour les formulaires
            initFormEventListeners();
            
            // Charger les trains pour le formulaire d'ajout de trajet
            chargerTrains();
            
            // Recharger les trains quand on affiche la section trajets
            const tripsLink = document.getElementById('tripsLink');
            if (tripsLink) {
                tripsLink.addEventListener('click', function() {
                    setTimeout(chargerTrains, 100); // Petit délai pour s'assurer que le DOM est prêt
                });
            }
        });

        // Fonction pour afficher une section et masquer les autres
        function showSection(sectionId) {
            console.log('Affichage de la section:', sectionId);
            
            // Masquer toutes les sections
            document.querySelectorAll('.section-content').forEach(section => {
                section.style.display = 'none';
            });
            
            // Supprimer la classe active de tous les liens
            document.querySelectorAll('.sidebar > ul > li > a').forEach(link => {
                link.classList.remove('active');
            });
            
            // Afficher la section demandée
            const section = document.getElementById(sectionId + 'Section');
            if (section) {
                section.style.display = 'block';
                
                // Ajouter la classe active au lien correspondant
                const link = document.getElementById(sectionId + 'Link');
                if (link) {
                    link.classList.add('active');
                }
                
                // Charger les données spécifiques à la section
                if (sectionId === 'users') {
                    loadUsers();
                } else if (sectionId === 'bookings') {
                    loadCancelRequests();
                } else if (sectionId === 'trains') {
                    loadTrains();
                } else if (sectionId === 'trips') {
                    loadTrips();
                }
            }
        }

        // Fonction pour afficher un onglet spécifique dans une section
        function showTab(tabId, sectionId) {
            console.log('Affichage de l\'onglet:', tabId, 'dans la section:', sectionId);
            
            // Masquer tous les onglets de la section
            const section = document.getElementById(sectionId + 'Section');
            if (section) {
                section.querySelectorAll('.tab-content').forEach(tab => {
                    tab.style.display = 'none';
                });
                
                // Afficher l'onglet demandé
                const tab = document.getElementById(tabId);
                if (tab) {
                    tab.style.display = 'block';
                    
                    // Charger les données spécifiques à l'onglet
                    if (tabId === 'cancelRequests') {
                        loadCancelRequests();
                    } else if (tabId === 'listTrips') {
                        loadTrips();
                    }
                }
            }
        }

        // Fonction pour initialiser les écouteurs d'événements pour les formulaires
        function initFormEventListeners() {
            // Formulaire d'ajout de trajet
            const addTripForm = document.getElementById('addTripForm');
            if (addTripForm) {
                addTripForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Create FormData object
                    const formData = new FormData(this);
                    
                    // Create an AJAX request
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'insert_trip.php', true);
                    
                    xhr.onload = function() {
                        if (this.status === 200) {
                            try {
                                const response = JSON.parse(this.responseText);
                                
                                if (response.success) {
                                    alert('Trajet ajouté avec succès');
                                    addTripForm.reset();
                                    loadTrips();
                                    showTab('listTrips', 'trips');
                                } else {
                                    alert('Erreur: ' + (response.error || 'Une erreur est survenue'));
                                }
                            } catch (e) {
                                console.error('Erreur lors du traitement des données:', e);
                                alert('Erreur lors de l\'ajout du trajet');
                            }
                        } else {
                            alert('Erreur lors de l\'ajout du trajet');
                        }
                    };
                    
                    xhr.onerror = function() {
                        alert('Erreur de connexion au serveur');
                    };
                    
                    xhr.send(formData);
                });
            }
            
            // Formulaire d'édition de trajet
            const editTripForm = document.getElementById('editTripForm');
            if (editTripForm) {
                editTripForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Create FormData object
                    const formData = new FormData(this);
                    
                    // Create an AJAX request
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'update_trip.php', true);
                    
                    xhr.onload = function() {
                        if (this.status === 200) {
                            try {
                                const response = JSON.parse(this.responseText);
                                
                                if (response.success) {
                                    alert('Trajet mis à jour avec succès');
                                    editTripForm.style.display = 'none';
                                    loadTrips();
                                    showTab('listTrips', 'trips');
                                } else {
                                    alert('Erreur: ' + (response.error || 'Une erreur est survenue'));
                                }
                            } catch (e) {
                                console.error('Erreur lors du traitement des données:', e);
                                alert('Erreur lors de la mise à jour du trajet');
                            }
                        } else {
                            alert('Erreur lors de la mise à jour du trajet');
                        }
                    };
                    
                    xhr.onerror = function() {
                        alert('Erreur de connexion au serveur');
                    };
                    
                    xhr.send(formData);
                });
            }
    
    // Formulaire d'ajout de train
    const trainForm = document.getElementById('trainForm');
    if (trainForm) {
        trainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validation côté client
            const numero = document.getElementById('numero').value;
            const trainName = document.getElementById('train_name').value;
            const trainCapacity = parseInt(document.getElementById('train_capacity').value) || 0;
            
            if (!numero) {
                alert('Veuillez saisir un numéro de train');
                return;
            }
            
            if (!trainName) {
                alert('Veuillez saisir un nom de train');
                return;
            }
            
            if (trainCapacity <= 0) {
                alert('La capacité totale doit être supérieure à 0');
                return;
            }
            
            // Create FormData object
            const formData = new FormData(this);
            
            // Create an AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'insert_train.php', true);
            
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        
                        if (response.success) {
                            alert('Train ajouté avec succès');
                            trainForm.reset();
                            hideAddTrainForm();
                            loadTrains();
                        } else {
                            alert('Erreur: ' + (response.error || 'Une erreur est survenue'));
                        }
                    } catch (e) {
                        console.error('Erreur lors du traitement des données:', e);
                        alert('Erreur lors de l\'ajout du train');
                    }
                } else {
                    alert('Erreur lors de l\'ajout du train');
                }
            };
            
            xhr.onerror = function() {
                alert('Erreur de connexion au serveur');
            };
            
            xhr.send(formData);
        });
    }
    
    // Écouteurs d'événements pour la capacité du train
    const capacitePremiereInput = document.getElementById('capacite_premiere');
    const capaciteEconomiqueInput = document.getElementById('capacite_economique');
    
    if (capacitePremiereInput && capaciteEconomiqueInput) {
        capacitePremiereInput.addEventListener('input', updateTotalCapacity);
        capaciteEconomiqueInput.addEventListener('input', updateTotalCapacity);
    }
    
    // Formulaire de notification
    const notificationForm = document.getElementById('notificationForm');
    if (notificationForm) {
        notificationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Create FormData object
            const formData = new FormData(this);
            
            // Create an AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'process_cancel_request.php', true);
            
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        
                        if (response.success) {
                            alert('La demande a été traitée avec succès et une notification a été envoyée au client.');
                            closeNotificationModal();
                            loadCancelRequests();
                        } else {
                            alert('Erreur: ' + (response.error || 'Une erreur est survenue'));
                        }
                    } catch (e) {
                        console.error('Erreur lors du traitement des données:', e);
                        alert('Erreur lors du traitement de la demande');
                    }
                } else {
                    alert('Erreur lors du traitement de la demande');
                }
            };
            
            xhr.onerror = function() {
                alert('Erreur de connexion au serveur');
            };
            
            xhr.send(formData);
        });
    }
}

// Fonction pour charger les utilisateurs
function loadUsers() {
    const tableBody = document.getElementById('usersTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Chargement des utilisateurs...</td></tr>';
    
    fetch('get_users.php')
        .then(response => response.json())
        .then(users => {
            if (users.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Aucun utilisateur trouvé</td></tr>';
                return;
            }
            
            let html = '';
            users.forEach(user => {
                const date = new Date(user.date_inscription).toLocaleDateString('fr-FR');
                
                html += `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.nom}</td>
                    <td>${user.prenom}</td>
                    <td>${user.email}</td>
                    <td>${user.telephone}</td>
                    <td>${date}</td>
                    <td>${user.reservations_count}</td>
                    <td>
                        <button onclick="viewUserDetails(${user.id})" class="btn-view" title="Voir les détails">
                            <i class="fas fa-eye"></i>
                        </button>
                       
                    </td>
                </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des utilisateurs:', error);
            tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Erreur lors du chargement des utilisateurs</td></tr>';
        });
}

// Fonction pour rechercher des utilisateurs
function searchUsers() {
    const searchInput = document.getElementById('searchUsers');
    if (!searchInput) return;
    
    const searchValue = searchInput.value.trim();
    
    const tableBody = document.getElementById('usersTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Recherche en cours...</td></tr>';
    
    fetch(`get_users.php?search=${encodeURIComponent(searchValue)}`)
        .then(response => response.json())
        .then(users => {
            if (users.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Aucun utilisateur trouvé</td></tr>';
                return;
            }
            
            let html = '';
            users.forEach(user => {
                const date = new Date(user.date_inscription).toLocaleDateString('fr-FR');
                
                html += `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.nom}</td>
                    <td>${user.prenom}</td>
                    <td>${user.email}</td>
                    <td>${user.telephone}</td>
                    <td>${date}</td>
                    <td>${user.reservations_count}</td>
                    <td>
                        <button onclick="viewUserDetails(${user.id})" class="btn-view" title="Voir les détails">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur lors de la recherche des utilisateurs:', error);
            tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Erreur lors de la recherche des utilisateurs</td></tr>';
        });
}

// Fonction pour afficher les détails d'un utilisateur
function viewUserDetails(userId) {
    const modal = document.getElementById('userDetailsModal');
    const userInfoContent = document.getElementById('userInfoContent');
    const userReservationsContent = document.getElementById('userReservationsContent');
    
    if (!modal || !userInfoContent || !userReservationsContent) return;
    
    userInfoContent.innerHTML = '<div style="text-align: center; padding: 20px;">Chargement des informations...</div>';
    userReservationsContent.innerHTML = '<div style="text-align: center; padding: 20px;">Chargement des réservations...</div>';
    
    modal.classList.add('active');

    
    fetch(`get_user_details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                userInfoContent.innerHTML = `<div style="color: #e74c3c; padding: 10px;">${data.error || 'Erreur lors du chargement des détails'}</div>`;
                userReservationsContent.innerHTML = '';
                return;
            }
            
            const user = data.user;
            const date = new Date(user.date_inscription).toLocaleDateString('fr-FR');
            
            userInfoContent.innerHTML = `
                <div class="user-detail-row">
                    <div class="user-detail-label">ID:</div>
                    <div class="user-detail-value">${user.id}</div>
                </div>
                <div class="user-detail-row">
                    <div class="user-detail-label">Nom:</div>
                    <div class="user-detail-value">${user.nom}</div>
                </div>
                <div class="user-detail-row">
                    <div class="user-detail-label">Prénom:</div>
                    <div class="user-detail-value">${user.prenom}</div>
                </div>
                <div class="user-detail-row">
                    <div class="user-detail-label">Email:</div>
                    <div class="user-detail-value">${user.email}</div>
                </div>
                <div class="user-detail-row">
                    <div class="user-detail-label">Téléphone:</div>
                    <div class="user-detail-value">${user.telephone || 'Non renseigné'}</div>
                </div>
                <div class="user-detail-row">
                    <div class="user-detail-label">Date d'inscription:</div>
                    <div class="user-detail-value">${date}</div>
                </div>
            `;
            
            if (data.reservations.length === 0) {
                userReservationsContent.innerHTML = '<div style="padding: 10px;">Aucune réservation trouvée pour cet utilisateur.</div>';
                return;
            }
            
            let reservationsHtml = '<table class="reservations-table"><thead><tr>' +
                '<th>N°</th>' +
                '<th>Date</th>' +
                '<th>Trajet</th>' +
                '<th>Train</th>' +
                '<th>Classe</th>' +
                '<th>Prix</th>' +
                '<th>Statut</th>' +
                '</tr></thead><tbody>';
                
            data.reservations.forEach(reservation => {
                const reservationDate = new Date(reservation.date_reservation).toLocaleDateString('fr-FR');
                const departDate = new Date(reservation.date_heure_depart).toLocaleString('fr-FR');
                
                let statusClass = '';
                switch (reservation.statut) {
                    case 'confirmee':
                        statusClass = 'confirmee';
                        break;
                    case 'annulee':
                        statusClass = 'annulee';
                        break;
                    case 'terminee':
                        statusClass = 'terminee';
                        break;
                    case 'en_attente_annulation':
                        statusClass = 'en_attente_annulation';
                        break;
                }
                
                reservationsHtml += `
                <tr>
                    <td>${reservation.id}</td>
                    <td>${reservationDate}</td>
                    <td>${reservation.gare_depart} → ${reservation.gare_arrivee}<br><small>${departDate}</small></td>
                    <td>${reservation.train_nom} (N°${reservation.train_numero})</td>
                    <td>${reservation.classe}</td>
                    <td>${reservation.prix_total} DA</td>
                    <td><span class="status-badge ${statusClass}">${reservation.statut.replace('_', ' ')}</span></td>
                </tr>
                `;
            });
            
            reservationsHtml += '</tbody></table>';
            userReservationsContent.innerHTML = reservationsHtml;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails de l\'utilisateur:', error);
            userInfoContent.innerHTML = '<div style="color: #e74c3c; padding: 10px;">Erreur lors du chargement des détails</div>';
            userReservationsContent.innerHTML = '';
        });
}

// Fonction pour fermer le modal des détails utilisateur
function closeUserDetailsModal() {
    const modal = document.getElementById('userDetailsModal');
    if (modal) {
        modal.classList.remove('active');

    }
}


// Fonction pour charger les trajets
function loadTrips() {
    const tableBody = document.getElementById('tripsTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Chargement des trajets...</td></tr>';
    
    // Récupérer les filtres
    const dateFilter = document.getElementById('filterDate') ? document.getElementById('filterDate').value : '';
    const statusFilter = document.getElementById('filterTripStatus') ? document.getElementById('filterTripStatus').value : '';
    
    let url = 'get_trips.php';
    if (dateFilter || statusFilter) {
        url += '?';
        if (dateFilter) {
            url += 'date=' + encodeURIComponent(dateFilter);
        }
        
        if (statusFilter) {
            url += (dateFilter ? '&' : '') + 'status=' + encodeURIComponent(statusFilter);
        }
    }
    
    fetch(url)
        .then(response => response.json())
        .then(trips => {
            if (trips.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Aucun trajet trouvé</td></tr>';
                return;
            }
            
            let html = '';
            trips.forEach(trip => {
                const departDate = new Date(trip.date_heure_depart).toLocaleString('fr-FR');
                
                let statusClass = '';
                switch (trip.statut) {
                    case 'active':
                        statusClass = 'confirmee';
                        break;
                    case 'annulee':
                        statusClass = 'annulee';
                        break;
                    case 'terminee':
                        statusClass = 'terminee';
                        break;
                }
                
                html += `
                <tr>
                    <td>${trip.id}</td>
                    <td>${trip.gare_depart}</td>
                    <td>${trip.gare_arrivee}</td>
                    <td>${departDate}</td>
                    <td><span class="status-badge ${statusClass}">${trip.statut}</span></td>
                    <td>${trip.reservations_count}</td>
                    <td class="actions-cell">
                        <button onclick="editTrip(${trip.id})" class="btn-view" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="confirmDeleteTripDirect(${trip.id})" class="btn-view danger" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button onclick="changeStatus(${trip.id})" class="btn-view" style="background-color: #f39c12;" title="Changer le statut">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                    </td>
                </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des trajets:', error);
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Erreur lors du chargement des trajets</td></tr>';
        });
}

// Fonction pour supprimer directement un trajet sans passer par le formulaire d'édition
function confirmDeleteTripDirect(tripId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce trajet ? Cette action est irréversible.')) {
        fetch('delete_trip.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: tripId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Trajet supprimé avec succès');
                loadTrips();
            } else {
                alert('Erreur: ' + (data.error || 'Une erreur est survenue lors de la suppression'));
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression du trajet:', error);
            alert('Erreur lors de la suppression du trajet');
        });
    }
}

// Fonction pour changer le statut d'un trajet
function changeStatus(tripId) {
    // Créer un modal pour changer le statut
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.classList.add('active');

    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Changer le statut du trajet #${tripId}</h3>
                <span class="close" onclick="this.parentNode.parentNode.parentNode.remove()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="changeStatusForm">
                    <input type="hidden" name="trip_id" value="${tripId}">
                    <div class="form-group">
                        <label for="new_status">Nouveau statut:</label>
                        <select id="new_status" name="new_status" class="form-control" required>
                            <option value="active">Actif</option>
                            <option value="annulee">Annulé</option>
                            <option value="terminee">Terminé</option>
                        </select>
                    </div>
                    <div class="form-actions" style="text-align: right; margin-top: 20px;">
                        <button type="button" onclick="this.parentNode.parentNode.parentNode.parentNode.remove()" class="btn-cancel">Annuler</button>
                        <button type="submit" class="btn-submit">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Gérer la soumission du formulaire
    document.getElementById('changeStatusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newStatus = document.getElementById('new_status').value;
        
        fetch('update_trip_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                id: tripId,
                status: newStatus 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Statut du trajet mis à jour avec succès');
                modal.remove();
                loadTrips();
            } else {
                alert('Erreur: ' + (data.error || 'Une erreur est survenue lors de la mise à jour du statut'));
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut:', error);
            alert('Erreur lors de la mise à jour du statut');
        });
    });
}

// Fonction pour filtrer les trajets
function filterTrips() {
    loadTrips();
}


// Fonction pour éditer un trajet
function editTrip(tripId) {
    // Afficher un indicateur de chargement dans le modal
    document.getElementById('editTripModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-open');
    
    // Charger les gares pour les sélecteurs
    fetch('get_gares.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau lors de la récupération des gares');
            }
            return response.json();
        })
        .then(gares => {
            let optionsDepart = '<option value="">Sélectionner la gare de départ</option>';
            let optionsArrivee = '<option value="">Sélectionner la gare d\'arrivée</option>';
            
            gares.forEach(gare => {
                const label = `${gare.nom} (${gare.ville})`;
                optionsDepart += `<option value="${gare.id_gare}">${label}</option>`;
                optionsArrivee += `<option value="${gare.id_gare}">${label}</option>`;
            });
            
            document.getElementById('modal_edit_gare_depart').innerHTML = optionsDepart;
            document.getElementById('modal_edit_gare_arrivee').innerHTML = optionsArrivee;
            
            // Charger les trains pour le sélecteur
            return fetch('get_trains.php?status=active');
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau lors de la récupération des trains');
            }
            return response.json();
        })
        .then(trains => {
            let optionsTrain = '<option value="">Sélectionner un train</option>';
            
            trains.forEach(train => {
                optionsTrain += `<option value="${train.id}">${train.nom} (${train.numero})</option>`;
            });
            
            document.getElementById('modal_edit_train').innerHTML = optionsTrain;
            
            // Maintenant, charger les détails du trajet
            return fetch(`get_trip.php?id=${tripId}`);
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau lors de la récupération des détails du trajet');
            }
            return response.text(); // Récupérer d'abord en tant que texte pour déboguer
        })
        .then(text => {
            try {
                // Essayer de parser le JSON
                const data = JSON.parse(text);
                
                if (data.error) {
                    throw new Error(data.error || 'Impossible de récupérer les détails du trajet');
                }
                
                const trip = data;
                
                // Remplir les champs du formulaire avec les données du trajet
                document.getElementById('modal_tripId').value = trip.id;
                document.getElementById('modal_edit_gare_depart').value = trip.id_gare_depart;
                document.getElementById('modal_edit_gare_arrivee').value = trip.id_gare_arrivee;
                
                // Formater les dates pour l'input datetime-local
                const formatDate = date => {
                    if (!date) return '';
                    try {
                        const d = new Date(date);
                        return d.toISOString().slice(0, 16);
                    } catch (e) {
                        console.error('Erreur de formatage de date:', e);
                        return '';
                    }
                };
                
                document.getElementById('modal_edit_date_depart').value = formatDate(trip.date_heure_depart);
                document.getElementById('modal_edit_date_arrivee').value = formatDate(trip.date_heure_arrivee);
                document.getElementById('modal_edit_train').value = trip.train_id;
                document.getElementById('modal_edit_prix').value = trip.prix;
                document.getElementById('modal_edit_economique').value = trip.economique;
                document.getElementById('modal_edit_premiere').value = trip.premiere_classe || 0;
                document.getElementById('modal_edit_statut').value = trip.statut;
            } catch (e) {
                console.error('Erreur lors du parsing JSON:', e);
                console.log('Réponse brute du serveur:', text);
                throw new Error('Erreur lors du parsing des données du trajet: ' + e.message);
            }
        })
        .catch(err => {
            console.error('Erreur lors du chargement des détails du trajet:', err);
            alert('Erreur: ' + err.message);
            closeEditTripModal();
        });
}

// Amélioration de la fonction de fermeture du modal
function closeEditTripModal() {
    document.getElementById('editTripModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    document.body.classList.remove('modal-open');
}

// Ajoutez ce code pour le gestionnaire d'événements du formulaire d'édition
document.addEventListener('DOMContentLoaded', function() {
    const editTripFormModal = document.getElementById('editTripFormModal');
    if (editTripFormModal) {
        editTripFormModal.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('update_trip.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau lors de la mise à jour du trajet');
                }
                return response.text(); // Récupérer d'abord en tant que texte pour déboguer
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert('Trajet mis à jour avec succès');
                        closeEditTripModal();
                        loadTrips();
                        showTab('listTrips', 'trips');
                    } else {
                        alert('Erreur: ' + (data.error || 'Une erreur est survenue'));
                    }
                } catch (e) {
                    console.error('Erreur lors du parsing JSON:', e);
                    console.log('Réponse brute du serveur:', text);
                    alert('Erreur lors du traitement de la réponse: ' + e.message);
                }
            })
            .catch(err => {
                alert('Erreur lors de la mise à jour du trajet: ' + err.message);
                console.error(err);
            });
        });
    }
});



// Fonction pour confirmer la suppression d'un trajet
function confirmDeleteTrip() {
    const tripId = document.getElementById('tripId').value;

    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action est irréversible !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_trip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: tripId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: data.message
                    });
                    document.getElementById('editTripForm').style.display = 'none';
                    loadTrips();
                    showTab('listTrips', 'trips');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: data.error || 'Une erreur est survenue lors de la suppression'
                    });
                }
            })
            .catch(error => {
                console.error('Erreur lors de la suppression du trajet:', error);
                Swal.fire('Erreur', 'Erreur de connexion au serveur', 'error');
            });
        }
    });
}


// Fonction pour rechercher des trajets
function searchTrips() {
    const searchInput = document.getElementById('searchTrip');
    const resultsContainer = document.getElementById('searchTripResults');
    
    if (!searchInput || !resultsContainer) return;
    
    const searchValue = searchInput.value.trim();
    
    if (searchValue.length < 2) {
        resultsContainer.innerHTML = '<div style="padding: 10px;">Veuillez saisir au moins 2 caractères pour la recherche.</div>';
        return;
    }
    
    resultsContainer.innerHTML = '<div style="padding: 10px; text-align: center;">Recherche en cours...</div>';
    
    fetch(`search_trips.php?q=${encodeURIComponent(searchValue)}`)
        .then(response => response.json())
        .then(trips => {
            if (trips.length === 0) {
                resultsContainer.innerHTML = '<div style="padding: 10px;">Aucun trajet trouvé.</div>';
                return;
            }
            
            let html = '<div class="search-results-list">';
            trips.forEach(trip => {
                const departDate = new Date(trip.date_heure_depart).toLocaleString('fr-FR');
                
                html += `
                <div class="search-result-item" onclick="editTrip(${trip.id})">
                    <div class="search-result-title">${trip.gare_depart} → ${trip.gare_arrivee}</div>
                    <div class="search-result-info">
                        <span>Train: ${trip.train_nom}</span>
                        <span>Départ: ${departDate}</span>
                        <span class="status-badge ${trip.statut}">${trip.statut}</span>
                    </div>
                </div>
                `;
            });
            html += '</div>';
            
            resultsContainer.innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur lors de la recherche des trajets:', error);
            resultsContainer.innerHTML = '<div style="padding: 10px; color: #e74c3c;">Erreur lors de la recherche.</div>';
        });
}



 // Fonctions pour afficher et masquer le formulaire d'ajout de train

 function showAddTrainForm() {
    const modal = document.getElementById('addTrainForm');
    modal.classList.add('active');
    document.getElementById('trainForm').reset();
    updateTotalCapacity();
}

function hideAddTrainForm() {
    const modal = document.getElementById('addTrainForm');
    modal.classList.remove('active');
}






 // Ajouter un gestionnaire d'événements pour le formulaire d'ajout de train
 document.addEventListener('DOMContentLoaded', function() {
            const trainForm = document.getElementById('trainForm');
            if (trainForm) {
                trainForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Formulaire soumis');
                    
                    // Validation côté client
                    const numero = document.getElementById('numero').value;
                    const trainName = document.getElementById('train_name').value;
                    const trainCapacity = parseInt(document.getElementById('train_capacity').value) || 0;
                    const capacitePremiere = parseInt(document.getElementById('capacite_premiere').value) || 0;
                    const capaciteEconomique = parseInt(document.getElementById('capacite_economique').value) || 0;
                    
                    console.log('Valeurs:', { numero, trainName, trainCapacity, capacitePremiere, capaciteEconomique });
                    
                    if (!numero) {
                        alert('Veuillez saisir un numéro de train');
                        return;
                    }
                    
                    if (!trainName) {
                        alert('Veuillez saisir un nom de train');
                        return;
                    }
                    
                    if (trainCapacity <= 0) {
                        alert('La capacité totale doit être supérieure à 0');
                        return;
                    }
                    
                    if (capacitePremiere < 0 || capaciteEconomique < 0) {
                        alert('Le nombre de sièges ne peut pas être négatif');
                        return;
                    }
                    
                    if (capacitePremiere + capaciteEconomique !== trainCapacity) {
                        alert('La somme des sièges doit être égale à la capacité totale');
                        return;
                    }
                    
                    // Create FormData object
                    const formData = new FormData(this);
                    
                    // Create an AJAX request
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'insert_train.php', true);
                    
                    xhr.onload = function() {
                        console.log('Réponse du serveur:', this.responseText);
                        if (this.status === 200) {
                            try {
                                const response = JSON.parse(this.responseText);
                                
                                if (response.success) {
                                    alert('Train ajouté avec succès');
                                    document.getElementById('trainForm').reset();
                                    hideAddTrainForm();
                                    loadTrains(); // Reload the trains list
                                } else {
                                    alert('Erreur: ' + (response.error || 'Une erreur est survenue'));
                                }
                            } catch (e) {
                                console.error('Erreur lors du traitement des données:', e);
                                alert('Erreur lors de l\'ajout du train');
                            }
                        } else {
                            alert('Erreur lors de l\'ajout du train');
                        }
                    };
                    
                    xhr.onerror = function() {
                        alert('Erreur de connexion au serveur');
                    };
                    
                    xhr.send(formData);
                });
            }
            
            // Ajouter des écouteurs d'événements pour mettre à jour automatiquement la capacité totale
            const capacitePremiereInput = document.getElementById('capacite_premiere');
            const capaciteEconomiqueInput = document.getElementById('capacite_economique');
            const capaciteTotaleInput = document.getElementById('train_capacity');
            
            if (capacitePremiereInput && capaciteEconomiqueInput && capaciteTotaleInput) {
                capacitePremiereInput.addEventListener('input', updateTotalCapacity);
                capaciteEconomiqueInput.addEventListener('input', updateTotalCapacity);
            }
            
            function updateTotalCapacity() {
                const premiere = parseInt(capacitePremiereInput.value) || 0;
                const economique = parseInt(capaciteEconomiqueInput.value) || 0;
                capaciteTotaleInput.value = premiere + economique;
            }
            
            // Initialize trains section when clicking on the trains link
            const trainsLink = document.getElementById('trainsLink');
            if (trainsLink) {
                trainsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Hide all sections
                    document.querySelectorAll('.section-content').forEach(section => {
                        section.style.display = 'none';
                    });
                    
                    // Show trains section
                    document.getElementById('trainsSection').style.display = 'block';
                    
                    // Remove active class from all links
                    document.querySelectorAll('.sidebar > ul > li > a').forEach(link => {
                        link.classList.remove('active');
                    });
                    
                    // Add active class to trains link
                    this.classList.add('active');
                    
                    // Load trains
                    loadTrains();
                });
            }
        });


        // Fonction pour rechercher des trains
        function searchTrains() {
            const searchTerm = document.getElementById('searchTrain').value;
            const status = document.getElementById('filterTrainStatus').value;
            
            // Create an AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_trains.php?search=${encodeURIComponent(searchTerm)}&status=${encodeURIComponent(status)}`, true);
            
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const trains = JSON.parse(this.responseText);
                        const trainsContainer = document.getElementById('trainsList');
                        trainsContainer.innerHTML = '';
                        
                        if (trains.length === 0) {
                            trainsContainer.innerHTML = '<p style="text-align: center; padding: 20px;">Aucun train trouvé</p>';
                            return;
                        }
                        
                        trains.forEach(train => {
                            const trainCard = document.createElement('div');
                            trainCard.className = 'train-card';
                            
                            let statusClass = '';
                            let statusText = '';
                            let serviceButtonText = '';
                            let serviceButtonIcon = '';
                            let maintenanceButtonText = '';
                            let maintenanceButtonIcon = '';
                            
                            switch(train.statut) {
                                case 'active':
                                    statusClass = 'status-active';
                                    statusText = 'Actif';
                                    serviceButtonText = 'Mettre hors service';
                                    serviceButtonIcon = 'fa-tools';
                                    break;
                                case 'retired':
                                    statusClass = 'status-inactive';
                                    statusText = 'Retiré';
                                    serviceButtonText = 'Remettre en service';
                                    serviceButtonIcon = 'fa-play-circle';
                                    break;
                            }
                            
                            trainCard.innerHTML = `
                                <h3>${train.nom}</h3>
                                <div class="train-info">
                                    <p><span>Numéro:</span> <span>${train.numero}</span></p>
                                    <p><span>Capacité totale:</span> <span>${parseInt(train.capacite_economique) + parseInt(train.capacite_premiere)} passagers</span></p>
                                    <p><span>1ère classe:</span> <span>${train.capacite_premiere} sièges</span></p>
                                    <p><span>Classe économique:</span> <span>${train.capacite_economique} sièges</span></p>
                                    <p><span>Statut:</span> <span class="${statusClass}">${statusText}</span></p>
                                </div>
                                <div class="actions">
                                    <button onclick="toggleTrainService(${train.id}, '${train.statut}')"><i class="fas ${serviceButtonIcon}"></i> ${serviceButtonText}</button>
                                </div>
                            `;
                            
                            trainsContainer.appendChild(trainCard);
                        });
                    } catch (e) {
                        console.error('Erreur lors du traitement des données:', e);
                        const trainsContainer = document.getElementById('trainsList');
                        trainsContainer.innerHTML = 
                            '<p style="text-align: center; padding: 20px;">Erreur lors du chargement des trains: ' + e.message + '</p>';
                    }
                } else {
                    const trainsContainer = document.getElementById('trainsList');
                    trainsContainer.innerHTML = 
                        '<p style="text-align: center; padding: 20px;">Erreur lors du chargement des trains. Code: ' + this.status + '</p>';
                }
            };
            
            xhr.onerror = function() {
                const trainsContainer = document.getElementById('trainsList');
                trainsContainer.innerHTML = 
                    '<p style="text-align: center; padding: 20px;">Erreur de connexion au serveur</p>';
            };
            
            xhr.send();
        }

        // Train Management Functions
        function loadTrains() {
            console.log('Chargement des trains');
            
            // Create an AJAX request to fetch trains from the database
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_trains.php', true);
            
            xhr.onload = function() {
                console.log('Réponse reçue pour loadTrains', this.responseText.substring(0, 100) + '...');
                
                if (this.status === 200) {
                    try {
                        const trains = JSON.parse(this.responseText);
                        console.log('Nombre de trains chargés', trains.length);
                        
                        const trainsContainer = document.getElementById('trainsList');
                        if (!trainsContainer) {
                            console.error('Élément #trainsList non trouvé dans le DOM');
                            return;
                        }
                        
                        trainsContainer.innerHTML = '';
                        
                        if (trains.length === 0) {
                            trainsContainer.innerHTML = '<p style="text-align: center; padding: 20px;">Aucun train trouvé</p>';
                            return;
                        }
                        
                        trains.forEach(train => {
                            console.log('Traitement du train', train.id);
                            
                            const trainCard = document.createElement('div');
                            trainCard.className = 'train-card';
                            
                            let statusClass = '';
                            let statusText = '';
                            let serviceButtonText = '';
                            let serviceButtonIcon = '';

                            
                            switch(train.statut) {
                                case 'active':
                                    statusClass = 'status-active';
                                    statusText = 'Actif';
                                    serviceButtonText = 'Mettre hors service';
                                    serviceButtonIcon = 'fa-tools';
                                    break;
                                case 'retired':
                                    statusClass = 'status-inactive';
                                    statusText = 'Retiré';
                                    serviceButtonText = 'Remettre en service';
                                    serviceButtonIcon = 'fa-play-circle';
                                    break;
                            }
                            
                            trainCard.innerHTML = `
                                <h3>${train.nom}</h3>
                                <div class="train-info">
                                    <p><span>Numéro:</span> <span>${train.numero}</span></p>
                                    <p><span>Capacité totale:</span> <span>${parseInt(train.capacite_economique) + parseInt(train.capacite_premiere)} passagers</span></p>
                                    <p><span>1ère classe:</span> <span>${train.capacite_premiere} sièges</span></p>
                                    <p><span>Classe économique:</span> <span>${train.capacite_economique} sièges</span></p>
                                    <p><span>Statut:</span> <span class="${statusClass}">${statusText}</span></p>
                                </div>
                                <div class="actions">
                                    <button onclick="toggleTrainService(${train.id}, '${train.statut}')"><i class="fas ${serviceButtonIcon}"></i> ${serviceButtonText}</button>
                                   
                                </div>
                            `;
                            
                            trainsContainer.appendChild(trainCard);
                        });
                    } catch (e) {
                        console.error('Erreur lors du traitement des données:', e);
                        const trainsContainer = document.getElementById('trainsList');
                        if (trainsContainer) {
                            trainsContainer.innerHTML = 
                                '<p style="text-align: center; padding: 20px;">Erreur lors du chargement des trains: ' + e.message + '</p>';
                        }
                    }
                } else {
                    const trainsContainer = document.getElementById('trainsList');
                    if (trainsContainer) {
                        trainsContainer.innerHTML = 
                            '<p style="text-align: center; padding: 20px;">Erreur lors du chargement des trains. Code: ' + this.status + '</p>';
                    }
                }
            };
            
            xhr.onerror = function(e) {
                const trainsContainer = document.getElementById('trainsList');
                if (trainsContainer) {
                    trainsContainer.innerHTML = 
                        '<p style="text-align: center; padding: 20px;">Erreur de connexion au serveur</p>';
                }
            };
            
            xhr.send();
        }

        // Fonction pour basculer l'état de service d'un train
        function toggleTrainService(trainId, currentStatus) {
            let newStatus;
            let confirmMessage;
            
            if (currentStatus === 'retired') {
                newStatus = 'active';
                confirmMessage = 'Êtes-vous sûr de vouloir remettre ce train en service ?';
            } else {
                newStatus = 'retired';
                confirmMessage = 'Êtes-vous sûr de vouloir mettre ce train hors service ?';
            }
            
            if (confirm(confirmMessage)) {
                // Create FormData object
                const formData = new FormData();
                formData.append('train_id', trainId);
                formData.append('action', 'changeStatus');
                formData.append('new_status', newStatus);
                
                // Create an AJAX request
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_train.php', true);
                
                xhr.onload = function() {
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            
                            if (response.success) {
                                if (newStatus === 'active') {
                                    alert('Train remis en service avec succès');
                                } else {
                                    alert('Train mis hors service avec succès');
                                }
                                loadTrains(); 
                            } else {
                                alert('Erreur: ' + (response.error || 'Une erreur est survenue'));
                            }
                        } catch (e) {
                            console.error('Erreur lors du traitement des données:', e);
                            alert('Erreur lors de la mise à jour du statut du train');
                        }
                    } else {
                        alert('Erreur lors de la mise à jour du statut du train');
                    }
                };
                
                xhr.onerror = function() {
                    alert('Erreur de connexion au serveur');
                };
                
                xhr.send(formData);
            }
        }

// Fonction pour charger les demandes d'annulation
function loadCancelRequests() {
    const container = document.getElementById('cancelRequestsContainer');
    if (!container) return;
    
    container.innerHTML = '<div style="text-align: center; padding: 20px;">Chargement des demandes d\'annulation...</div>';
    
    // Ne pas utiliser de filtres
    const dateFilter = '';
    const userFilter = '';

    
    let url = 'get_cancel_requests.php';

    
    fetch(url)
        .then(response => response.json())
        .then(requests => {
            if (requests.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 20px;">Aucune demande d\'annulation en attente</div>';
                return;
            }
            
            let html = '';
            requests.forEach(request => {
                const dateFormatee = new Date(request.date_demande).toLocaleString('fr-FR');
                
                html += `
                <div class="cancel-request-card">
                    <div class="cancel-request-header">
                        <h3>Demande #${request.id}</h3>
                        <span>${dateFormatee}</span>
                    </div>
                    <div class="cancel-request-info">
                        <p><strong>Client:</strong> ${request.client_prenom} ${request.client_nom} (${request.client_email})</p>
                        <p><strong>Réservation:</strong> #${request.reservation_id}</p>
                        <p><strong>Trajet:</strong> ${request.trajet_info}</p>
                        <p><strong>Train:</strong> ${request.train_info}</p>
                        <p><strong>Classe:</strong> ${request.classe}</p>
                        <p><strong>Prix total:</strong> ${request.prix_total} DA</p>
                    </div>
                    <div class="cancel-request-actions">
                        <button class="btn-reject" onclick="rejectCancelRequest(${request.id}, ${request.utilisateur_id})">
                            <i class="fas fa-times"></i> Refuser
                        </button>
                        <button class="btn-approve" onclick="approveCancelRequest(${request.id}, ${request.utilisateur_id}, ${request.prix_total})">
                            <i class="fas fa-check"></i> Approuver
                        </button>
                    </div>
                </div>
                `;
            });
            
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des demandes d\'annulation:', error);
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: #e74c3c;">Erreur lors du chargement des demandes d\'annulation</div>';
        });
}



// Fonction pour approuver une demande d'annulation
function approveCancelRequest(requestId, clientId, montantTotal) {
    const modal = document.getElementById('notificationModal');
    const clientIdInput = document.getElementById('notification_utilisateur_id');
    const requestIdInput = document.getElementById('notification_request_id');
    const actionInput = document.getElementById('notification_action');
    const messageInput = document.getElementById('notification_message');
    const montantInput = document.getElementById('montant_rembourse');
    const montantGroup = document.getElementById('montant_group');
    
    if (!modal || !clientIdInput || !requestIdInput || !actionInput || !messageInput || !montantInput || !montantGroup) return;
    
    // Préremplir le formulaire
    clientIdInput.value = clientId;
    requestIdInput.value = requestId;
    actionInput.value = 'approve';
    messageInput.value = 'Votre demande d\'annulation a été approuvée. Le remboursement sera effectué dans les prochains jours.';
    montantInput.value = montantTotal;
    montantGroup.style.display = 'block';
    
    // Afficher le modal
    modal.style.display = 'block';
}

// Fonction pour refuser une demande d'annulation
function rejectCancelRequest(requestId, clientId) {
    const modal = document.getElementById('notificationModal');
    const clientIdInput = document.getElementById('notification_utilisateur_id');
    const requestIdInput = document.getElementById('notification_request_id');
    const actionInput = document.getElementById('notification_action');
    const messageInput = document.getElementById('notification_message');
    const montantGroup = document.getElementById('montant_group');
    
    if (!modal || !clientIdInput || !requestIdInput || !actionInput || !messageInput || !montantGroup) return;
    
    // Préremplir le formulaire
    clientIdInput.value = clientId;
    requestIdInput.value = requestId;
    actionInput.value = 'reject';
    messageInput.value = 'Votre demande d\'annulation a été refusée.';
    montantGroup.style.display = 'none';
    
    // Afficher le modal
    modal.style.display = 'block';
}

// Fonction pour fermer le modal de notification
function closeNotificationModal() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Fonction pour charger les trains dans le formulaire d'ajout de trajet
function chargerTrains() {
    console.log('Chargement des trains pour le formulaire');
    const trainSelect = document.getElementById('train-select');
    if (!trainSelect) {
        console.error('Élément train-select non trouvé');
        return;
    }
    
    trainSelect.innerHTML = '<option value="">Chargement des trains...</option>';
    
    // Utiliser XMLHttpRequest au lieu de fetch pour une meilleure compatibilité
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_trains_list.php', true);
    
    xhr.onload = function() {
        console.log('Statut de la réponse:', this.status);
        console.log('Réponse brute:', this.responseText);
        
        if (this.status === 200) {
            try {
                const trains = JSON.parse(this.responseText);
                console.log('Trains parsés:', trains);
                
                // Vérifier si c'est une erreur
                if (trains.error) {
                    console.error('Erreur du serveur:', trains.error);
                    trainSelect.innerHTML = '<option value="">Erreur: ' + trains.error + '</option>';
                    return;
                }
                
                // Vérifier si c'est un tableau
                if (!Array.isArray(trains)) {
                    console.error('Réponse inattendue:', trains);
                    trainSelect.innerHTML = '<option value="">Erreur: format de données incorrect</option>';
                    return;
                }
                
                if (trains.length === 0) {
                    trainSelect.innerHTML = '<option value="">Aucun train actif disponible</option>';
                    return;
                }
                
                let options = '<option value="">Sélectionner un train</option>';
                trains.forEach(train => {
                    console.log('Ajout du train:', train);
                    options += `<option value="${train.id}">${train.nom} (N°${train.numero})</option>`;
                });
                
                trainSelect.innerHTML = options;
                console.log('Options ajoutées avec succès');
                
            } catch (e) {
                console.error('Erreur lors du parsing JSON:', e);
                console.log('Réponse qui a causé l\'erreur:', this.responseText);
                trainSelect.innerHTML = '<option value="">Erreur lors du traitement des données</option>';
            }
        } else {
            console.error('Erreur HTTP:', this.status);
            trainSelect.innerHTML = '<option value="">Erreur de connexion (Code: ' + this.status + ')</option>';
        }
    };
    
    xhr.onerror = function() {
        console.error('Erreur de réseau');
        trainSelect.innerHTML = '<option value="">Erreur de connexion au serveur</option>';
    };
    
    xhr.send();
}

// Fonction pour mettre à jour la capacité totale du train
function updateTotalCapacity() {
    const capacitePremiereInput = document.getElementById('capacite_premiere');
    const capaciteEconomiqueInput = document.getElementById('capacite_economique');
    const capaciteTotaleInput = document.getElementById('train_capacity');
    
    if (capacitePremiereInput && capaciteEconomiqueInput && capaciteTotaleInput) {
        const premiere = parseInt(capacitePremiereInput.value) || 0;
        const economique = parseInt(capaciteEconomiqueInput.value) || 0;
        capaciteTotaleInput.value = premiere + economique;
    }
}

// Dans la fonction DOMContentLoaded, ajouter ceci après les autres initialisations
document.addEventListener('DOMContentLoaded', function() {
    
    // Charger les trains immédiatement au chargement de la page
    setTimeout(function() {
        chargerTrains();
    }, 500);
    
    // Recharger les trains quand on clique sur l'onglet "Ajouter un trajet"
    const addTripTab = document.querySelector('[data-tab="addTrip"]');
    if (addTripTab) {
        addTripTab.addEventListener('click', function() {
            setTimeout(chargerTrains, 200);
        });
    }
    
    // Recharger les trains quand on affiche la section trajets
    const tripsLink = document.getElementById('tripsLink');
    if (tripsLink) {
        tripsLink.addEventListener('click', function() {
            setTimeout(chargerTrains, 300);
        });
    }
});
</script>
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
