<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Générer un token CSRF s'il n'existe pas déjà
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Connexion à la base de données
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Traitement des actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_refund']) && isset($_POST['demande_id']) && isset($_POST['csrf_token'])) {
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            $demande_id = intval($_POST['demande_id']);
            $montant_rembourse = isset($_POST['montant_rembourse']) ? floatval($_POST['montant_rembourse']) : 0;
            $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
            
            try {
                // Récupérer l'ID de réservation
                $stmt = $pdo->prepare("SELECT reservation_id FROM demandes_remboursement WHERE id = ?");
                $stmt->execute([$demande_id]);
                $reservation_id = $stmt->fetchColumn();
                
                if ($reservation_id) {
                    // Mettre à jour le statut de la demande
                    $stmt = $pdo->prepare("
                        UPDATE demandes_remboursement 
                        SET statut = 'approved', 
                            date_traitement = NOW(), 
                            montant_rembourse = ?, 
                            commentaire_admin = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$montant_rembourse, $commentaire, $demande_id]);
                    
                    // Mettre à jour le statut de la réservation
                    $stmt = $pdo->prepare("UPDATE reservations SET statut = 'cancelled' WHERE id = ?");
                    $stmt->execute([$reservation_id]);
                    
                    $message = "La demande de remboursement a été approuvée avec succès.";
                } else {
                    $error = "Demande de remboursement introuvable.";
                }
            } catch (PDOException $e) {
                $error = "Une erreur est survenue lors du traitement de la demande: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['reject_refund']) && isset($_POST['demande_id']) && isset($_POST['csrf_token'])) {
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Erreur de sécurité. Veuillez réessayer.";
        } else {
            $demande_id = intval($_POST['demande_id']);
            $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
            
            try {
                // Récupérer l'ID de réservation
                $stmt = $pdo->prepare("SELECT reservation_id FROM demandes_remboursement WHERE id = ?");
                $stmt->execute([$demande_id]);
                $reservation_id = $stmt->fetchColumn();
                
                if ($reservation_id) {
                    // Mettre à jour le statut de la demande
                    $stmt = $pdo->prepare("
                        UPDATE demandes_remboursement 
                        SET statut = 'rejected', 
                            date_traitement = NOW(), 
                            commentaire_admin = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$commentaire, $demande_id]);
                    
                    // Remettre le statut de la réservation à confirmed
                    $stmt = $pdo->prepare("UPDATE reservations SET statut = 'confirmed' WHERE id = ?");
                    $stmt->execute([$reservation_id]);
                    
                    $message = "La demande de remboursement a été rejetée.";
                } else {
                    $error = "Demande de remboursement introuvable.";
                }
            } catch (PDOException $e) {
                $error = "Une erreur est survenue lors du traitement de la demande: " . $e->getMessage();
            }
        }
    }
}

// Récupérer les demandes de remboursement
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "
    SELECT dr.*, 
           r.prix_total, r.statut AS reservation_statut, r.date_reservation,
           u.nom, u.prenom, u.email,
           t.gare_depart, t.gare_arrivee, t.date_heure_depart
    FROM demandes_remboursement dr
    JOIN reservations r ON dr.reservation_id = r.id
    JOIN utilisateurs u ON dr.user_id = u.id
    JOIN trajets t ON r.trajet_id = t.id
    WHERE 1=1
";

$params = [];

if (!empty($status_filter)) {
    $sql .= " AND dr.statut = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $sql .= " AND DATE(dr.date_demande) = ?";
    $params[] = $date_filter;
}

if (!empty($search_filter)) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search_filter%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY dr.date_demande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Remboursements - Administration</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .refund-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .refund-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .refund-header {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .refund-id {
            font-weight: 600;
            color: var(--color-deep-blue);
            font-size: 16px;
        }
        
        .refund-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff8e8;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .refund-body {
            padding: 20px;
        }
        
        .refund-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-weight: 500;
            color: #333;
        }
        
        .refund-reason {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .reason-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        .reason-text {
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .refund-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .refund-actions button {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .approve-btn {
            background-color: #28a745;
            color: white;
            border: none;
        }
        
        .approve-btn:hover {
            background-color: #218838;
        }
        
        .reject-btn {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        
        .reject-btn:hover {
            background-color: #c82333;
        }
        
        .view-btn {
            background-color: var(--color-deep-blue);
            color: white;
            border: none;
        }
        
        .view-btn:hover {
            background-color: var(--color-blue);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 80%;
            max-width: 600px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-weight: 600;
            color: var(--color-deep-blue);
            font-size: 18px;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--color-deep-blue);
            box-shadow: 0 0 0 3px rgba(26, 71, 125, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
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
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .filters select,
        .filters input {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            min-width: 150px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
        }
        
        .filters button {
            padding: 10px 20px;
            background-color: var(--color-deep-blue);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filters button:hover {
            background-color: var(--color-blue);
        }
        
        .no-results {
            text-align: center;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 10px;
            color: #555;
        }
        
        @media (max-width: 768px) {
            .refund-info {
                grid-template-columns: 1fr;
            }
            
            .refund-actions {
                flex-direction: column;
            }
            
            .filters {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin_sidebar.php'; ?>
        
        <div class="main-content">
            <h2>Gestion des Demandes de Remboursement</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="filters">
                <input type="text" id="searchInput" placeholder="Rechercher un client..." value="<?php echo htmlspecialchars($search_filter); ?>">
                <select id="statusFilter">
                    <option value="">Tous les statuts</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approuvé</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                </select>
                <input type="date" id="dateFilter" value="<?php echo htmlspecialchars($date_filter); ?>">
                <button onclick="resetFilters()"><i class="fas fa-sync-alt"></i> Réinitialiser</button>
            </div>
            
            <?php if (empty($demandes)): ?>
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <h3>Aucune demande de remboursement trouvée</h3>
                    <p>Il n'y a pas de demandes correspondant à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <?php foreach ($demandes as $demande): ?>
                    <div class="refund-card">
                        <div class="refund-header">
                            <div class="refund-id">Demande #<?php echo $demande['id']; ?></div>
                            <div class="refund-status status-<?php echo $demande['statut']; ?>">
                                <?php 
                                    switch ($demande['statut']) {
                                        case 'pending':
                                            echo 'En attente';
                                            break;
                                        case 'approved':
                                            echo 'Approuvé';
                                            break;
                                        case 'rejected':
                                            echo 'Rejeté';
                                            break;
                                        default:
                                            echo ucfirst($demande['statut']);
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="refund-body">
                            <div class="refund-info">
                                <div class="info-item">
                                    <div class="info-label">Client</div>
                                    <div class="info-value"><?php echo $demande['nom'] . ' ' . $demande['prenom']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo $demande['email']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Réservation</div>
                                    <div class="info-value">#<?php echo $demande['reservation_id']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Trajet</div>
                                    <div class="info-value"><?php echo $demande['gare_depart'] . ' - ' . $demande['gare_arrivee']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Date de départ</div>
                                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($demande['date_heure_depart'])); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Montant total</div>
                                    <div class="info-value"><?php echo number_format($demande['prix_total'], 2); ?> DA</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Date de demande</div>
                                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($demande['date_demande'])); ?></div>
                                </div>
                                <?php if ($demande['statut'] !== 'pending'): ?>
                                <div class="info-item">
                                    <div class="info-label">Date de traitement</div>
                                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($demande['date_traitement'])); ?></div>
                                </div>
                                <?php if ($demande['statut'] === 'approved'): ?>
                                <div class="info-item">
                                    <div class="info-label">Montant remboursé</div>
                                    <div class="info-value"><?php echo number_format($demande['montant_rembourse'], 2); ?> DA</div>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="refund-reason">
                                <div class="reason-label">Motif d'annulation:</div>
                                <div class="reason-text">
                                    <?php 
                                        switch ($demande['motif']) {
                                            case 'changement_plans':
                                                echo 'Changement de plans';
                                                break;
                                            case 'maladie':
                                                echo 'Maladie ou urgence médicale';
                                                break;
                                            case 'double_reservation':
                                                echo 'Double réservation';
                                                break;
                                            case 'erreur_reservation':
                                                echo 'Erreur dans la réservation';
                                                break;
                                            case 'autre':
                                                echo 'Autre raison';
                                                break;
                                            default:
                                                echo ucfirst($demande['motif']);
                                        }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="refund-reason">
                                <div class="reason-label">Détails:</div>
                                <div class="reason-text"><?php echo nl2br(htmlspecialchars($demande['details'])); ?></div>
                            </div>
                            
                            <?php if (!empty($demande['commentaire_admin'])): ?>
                            <div class="refund-reason">
                                <div class="reason-label">Commentaire administrateur:</div>
                                <div class="reason-text"><?php echo nl2br(htmlspecialchars($demande['commentaire_admin'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="refund-actions">
                                <button class="view-btn" onclick="window.location.href='confirmation.php?id=<?php echo $demande['reservation_id']; ?>'">
                                    <i class="fas fa-eye"></i> Voir la réservation
                                </button>
                                
                                <?php if ($demande['statut'] === 'pending'): ?>
                                <button class="approve-btn" onclick="openApproveModal(<?php echo $demande['id']; ?>, <?php echo $demande['prix_total']; ?>)">
                                    <i class="fas fa-check"></i> Approuver
                                </button>
                                <button class="reject-btn" onclick="openRejectModal(<?php echo $demande['id']; ?>)">
                                    <i class="fas fa-times"></i> Rejeter
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal pour approuver un remboursement -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Approuver le remboursement</h2>
                <span class="close" onclick="closeModal('approveModal')">&times;</span>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="demande_id" id="approve_demande_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="montant_rembourse">Montant à rembourser (DA)</label>
                        <input type="number" step="0.01" id="montant_rembourse" name="montant_rembourse" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="commentaire">Commentaire (optionnel)</label>
                        <textarea id="commentaire" name="commentaire" placeholder="Ajouter un commentaire..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeModal('approveModal')">Annuler</button>
                    <button type="submit" name="approve_refund" class="approve-btn">Confirmer le remboursement</button>
                </div>
            </form  class="approve-btn">Confirmer le remboursement</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal pour rejeter un remboursement -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Rejeter le remboursement</h2>
                <span class="close" onclick="closeModal('rejectModal')">&times;</span>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="demande_id" id="reject_demande_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="commentaire_rejet">Motif du rejet</label>
                        <textarea id="commentaire_rejet" name="commentaire" placeholder="Veuillez expliquer pourquoi la demande est rejetée..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeModal('rejectModal')">Annuler</button>
                    <button type="submit" name="reject_refund" class="reject-btn">Confirmer le rejet</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Fonctions pour les modals
        function openApproveModal(demandeId, montantTotal) {
            document.getElementById('approve_demande_id').value = demandeId;
            document.getElementById('montant_rembourse').value = montantTotal;
            document.getElementById('approveModal').style.display = 'block';
        }
        
        function openRejectModal(demandeId) {
            document.getElementById('reject_demande_id').value = demandeId;
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Fermer les modals si on clique en dehors
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        // Fonctions pour les filtres
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const date = document.getElementById('dateFilter').value;
            const search = document.getElementById('searchInput').value;
            
            let url = 'admin_remboursements.php?';
            
            if (status) {
                url += 'status=' + encodeURIComponent(status) + '&';
            }
            
            if (date) {
                url += 'date=' + encodeURIComponent(date) + '&';
            }
            
            if (search) {
                url += 'search=' + encodeURIComponent(search);
            }
            
            window.location.href = url;
        }
        
        function resetFilters() {
            window.location.href = 'admin_remboursements.php';
        }
    </script>
</body>
</html>
