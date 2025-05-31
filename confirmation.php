<?php
// Démarrer la session
session_start();

// Vérifier si l'ID de réservation est présent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: utilisateur.php");
    exit();
}

$reservation_id = intval($_GET['id']);

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

// Récupérer les détails de la réservation
$stmt = $pdo->prepare("
    SELECT r.*, 
           u.nom, u.prenom, u.email, u.telephone,
           t.id_gare_depart, t.id_gare_arrivee, t.date_heure_depart, t.date_heure_arrivee, t.prix,
           tr.nom AS train_nom,
           g1.nom AS gare_depart, g2.nom AS gare_arrivee,
           p.methode
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    JOIN trajets t ON r.trajet_id = t.id
    JOIN trains tr ON t.train_id = tr.id
    JOIN gares g1 ON t.id_gare_depart = g1.id_gare
    JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
    LEFT JOIN paiements p ON r.id = p.reservation_id
    WHERE r.id = ?
");
$stmt->execute([$reservation_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    header("Location: utilisateur.php");
    exit();
}

// Récupérer le nombre de passagers
$stmt = $pdo->prepare("
    SELECT type, COUNT(*) as count
    FROM passagers
    WHERE reservation_id = ?
    GROUP BY type
");
$stmt->execute([$reservation_id]);
$passagers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adultes = 0;
$enfants = 0;

foreach ($passagers as $passager) {
    if ($passager['type'] == 'adulte') {
        $adultes = $passager['count'];
    } elseif ($passager['type'] == 'enfant') {
        $enfants = $passager['count'];
    }
}

// Générer un code QR pour la réservation
$qrCodeContent = "RESERVATION:" . $reservation_id . "\n";
$qrCodeContent .= "NOM:" . $reservation['nom'] . " " . $reservation['prenom'] . "\n";
$qrCodeContent .= "TRAJET:" . $reservation['gare_depart'] . " - " . $reservation['gare_arrivee'] . "\n";
$qrCodeContent .= "DATE:" . date('d/m/Y H:i', strtotime($reservation['date_heure_depart'])) . "\n";
$qrCodeContent .= "PASSAGERS:" . ($adultes + $enfants) . "\n";
$qrCodeContent .= "PRIX:" . $reservation['prix_total'] . " DA";

// Générer un numéro de référence unique
$reference = "AT-" . strtoupper(substr($reservation['nom'], 0, 2)) . "-" . $reservation_id . "-" . date('Ymd', strtotime($reservation['date_reservation']));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Réservation - Annaba Train</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .confirmation-header {
            background: linear-gradient(to right, var(--color-deep-blue), var(--color-navy));
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .confirmation-header h1 {
            margin: 0;
            font-family: 'Raleway', sans-serif;
            font-size: 24px;
        }
        
        .confirmation-header .status {
            display: inline-block;
            padding: 5px 15px;
            background-color: #2ecc71;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .confirmation-body {
            padding: 30px;
        }
        
        .confirmation-section {
            margin-bottom: 30px;
        }
        
        .confirmation-section h2 {
            color: var(--color-deep-blue);
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .confirmation-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
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
        
        .qr-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .qr-code {
            width: 150px;
            height: 150px;
            background-color: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .reference-number {
            font-family: monospace;
            font-size: 18px;
            font-weight: 600;
            color: var(--color-deep-blue);
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }
        
        .action-buttons button {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .action-buttons .print-btn {
            background-color: var(--color-deep-blue);
            color: white;
            border: none;
        }
        
        .action-buttons .print-btn:hover {
            background-color: var(--color-blue);
        }
        
        .action-buttons .download-btn {
            background-color: white;
            color: var(--color-deep-blue);
            border: 1px solid var(--color-deep-blue);
        }
        
        .action-buttons .download-btn:hover {
            background-color: #f0f0f0;
        }
        
        .action-buttons .cancel-btn {
            background-color: #f8f9fa;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .action-buttons .cancel-btn:hover {
            background-color: #fff5f5;
        }
        
        .ticket-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .ticket-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        
        .ticket-info .important {
            color: var(--color-deep-blue);
            font-weight: 600;
        }
        
        @media print {
            .action-buttons, .navbar, footer {
                display: none;
            }
            
            body {
                background-color: white;
            }
            
            .confirmation-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .confirmation-details {
                grid-template-columns: 1fr;
            }
            
            .qr-section {
                flex-direction: column;
                gap: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="confirmation-container">
        <div class="confirmation-header">
            <h1>Confirmation de Réservation</h1>
            <div class="status">Confirmée</div>
        </div>
        
        <div class="confirmation-body">
            <div class="confirmation-section">
                <h2>Détails de la Réservation</h2>
                <div class="reference-number"><?php echo $reference; ?></div>
                <div class="confirmation-details">
                    <div class="detail-item">
                        <div class="detail-label">Date de réservation</div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Statut</div>
                        <div class="detail-value"><?php echo ucfirst($reservation['statut']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Méthode de paiement</div>
                        <div class="detail-value"><?php echo ucfirst($reservation['methode'] ?? 'Non spécifiée'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Prix total</div>
                        <div class="detail-value"><?php echo number_format($reservation['prix_total'], 2); ?> DA</div>
                    </div>
                </div>
            </div>
            
            <div class="confirmation-section">
                <h2>Informations du Trajet</h2>
                <div class="confirmation-details">
                    <div class="detail-item">
                        <div class="detail-label">Train</div>
                        <div class="detail-value"><?php echo $reservation['train_nom']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Départ</div>
                        <div class="detail-value"><?php echo $reservation['gare_depart']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Arrivée</div>
                        <div class="detail-value"><?php echo $reservation['gare_arrivee']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Date et heure de départ</div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($reservation['date_heure_depart'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Date et heure d'arrivée</div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($reservation['date_heure_arrivee'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Classe</div>
                        <div class="detail-value"><?php echo ucfirst($reservation['classe']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="confirmation-section">
                <h2>Informations du Voyageur</h2>
                <div class="confirmation-details">
                    <div class="detail-item">
                        <div class="detail-label">Nom</div>
                        <div class="detail-value"><?php echo $reservation['nom']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Prénom</div>
                        <div class="detail-value"><?php echo $reservation['prenom']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo $reservation['email']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Téléphone</div>
                        <div class="detail-value"><?php echo $reservation['telephone']; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="confirmation-section">
                <h2>Passagers</h2>
                <div class="confirmation-details">
                    <div class="detail-item">
                        <div class="detail-label">Adultes</div>
                        <div class="detail-value"><?php echo $adultes; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Enfants</div>
                        <div class="detail-value"><?php echo $enfants; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total</div>
                        <div class="detail-value"><?php echo $adultes + $enfants; ?> passagers</div>
                    </div>
                </div>
            </div>
            
            <div class="qr-section">
                <div>
                    <h3>Scannez ce code pour accéder rapidement à votre réservation</h3>
                    <p>Présentez ce code à la gare pour faciliter votre embarquement</p>
                </div>
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($qrCodeContent); ?>" alt="QR Code de la réservation">
                </div>
            </div>
            
            <div class="ticket-info">
                <p class="important">Informations importantes:</p>
                <p>• Veuillez vous présenter à la gare au moins 30 minutes avant le départ du train.</p>
                <p>• Une pièce d'identité valide est requise pour tous les passagers adultes.</p>
                <p>• Les billets ne sont pas remboursables après le départ du train.</p>
                <p>• Pour toute demande d'annulation, veuillez utiliser le bouton "Demander un remboursement" ci-dessous.</p>
            </div>
            
            <div class="action-buttons">
                <button class="print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimer le billet
                </button>
                <button class="download-btn" onclick="generatePDF()">
                    <i class="fas fa-download"></i> Télécharger en PDF
                </button>
                <?php if ($reservation['statut'] != 'annulee' && strtotime($reservation['date_heure_depart']) > time()): ?>
                <button class="cancel-btn" onclick="window.location.href='demande_remboursement.php?id=<?php echo $reservation_id; ?>'">
                    <i class="fas fa-undo"></i> Demander un remboursement
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function generatePDF() {
            // Masquer les boutons pour le PDF
            const actionButtons = document.querySelector('.action-buttons');
            actionButtons.style.display = 'none';
            
            const element = document.querySelector('.confirmation-container');
            const opt = {
                margin: 10,
                filename: 'reservation-<?php echo $reservation_id; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save().then(() => {
                // Réafficher les boutons après la génération du PDF
                actionButtons.style.display = 'flex';
            });
        }
    </script>
</body>
</html>
