 <?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$reservationId = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $reservationId = intval($_GET['id']);
} elseif (isset($_SESSION['reservation_id'])) {
    $reservationId = $_SESSION['reservation_id'];
} else {
    header("Location: utilisateur.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// INFOS RÉSERVATION PRINCIPALE 
$sql = "SELECT r.id, r.date_reservation, r.prix_total, r.statut, r.classe,
               r.nb_passagers, 
               t.id as trajet_id, t.date_heure_depart, t.date_heure_arrivee,
               g1.nom as gare_depart, g2.nom as gare_arrivee,
               u.nom as nom_utilisateur, u.prenom as prenom_utilisateur,
               tr.nom as train_nom, tr.numero as train_numero
        FROM reservations r
        JOIN trajets t ON r.trajet_id = t.id
        JOIN utilisateurs u ON r.utilisateur_id = u.id
        JOIN trains tr ON t.train_id = tr.id
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        WHERE r.id = ? AND r.utilisateur_id = ?";
$stmt = $conn->prepare($sql);
$userId = $_SESSION['user_id'];
$stmt->bind_param("ii", $reservationId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: utilisateur.php");
    exit();
}

$reservation = $result->fetch_assoc();
$stmt->close();

// BILLETS ASSOCIÉS 
$billets = [];
$sql = "SELECT b.id, b.code_billet, b.prix, b.classe, b.trajet_id,
               t.date_heure_depart, t.date_heure_arrivee,
               g1.nom as gare_depart, g2.nom as gare_arrivee,
               tr.nom as train_nom, tr.numero as train_numero
        FROM billets b
        JOIN trajets t ON b.trajet_id = t.id
        JOIN trains tr ON t.train_id = tr.id
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        WHERE b.reservation_id = ?
        ORDER BY b.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $billets[] = $row;
}
$stmt->close();

if (empty($billets)) {
    echo "Aucun billet trouvé pour cette réservation. Veuillez contacter le support.";
    exit();
}

//TRAJET RETOUR 
$returnReservation = null;
$sql = "SELECT t.id as trajet_id, t.date_heure_depart, t.date_heure_arrivee,
               g1.nom as gare_depart, g2.nom as gare_arrivee,
               tr.nom as train_nom, tr.numero as train_numero
        FROM reservations_trajets rt
        JOIN trajets t ON rt.trajet_id = t.id
        JOIN trains tr ON t.train_id = tr.id
        JOIN gares g1 ON t.id_gare_depart = g1.id_gare
        JOIN gares g2 ON t.id_gare_arrivee = g2.id_gare
        WHERE rt.reservation_id = ? AND rt.type = 'retour'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$returnResult = $stmt->get_result();

if ($returnResult->num_rows > 0) {
    $returnReservation = $returnResult->fetch_assoc();
}
$stmt->close();
$conn->close();

// OUTILS 
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i');
}

function getClasse($classe) {
    if ($classe === 'premiere') return 'Première classe';
    if ($classe === 'economique') return 'Classe économique';
    return 'Classe inconnue';
}


function calculerDuree($dateDepart, $dateArrivee) {
    $depart = new DateTime($dateDepart);
    $arrivee = new DateTime($dateArrivee);
    $interval = $depart->diff($arrivee);
    $hours = $interval->h + ($interval->d * 24);
    return $hours . 'h ' . $interval->i . 'min';
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billets de Train - <?php echo $reservation['id']; ?></title>
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

        /* Header Styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: var(--primary-color);
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
        }

        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.75rem;
            object-fit: cover;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: white;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover, 
        .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

    .page-title {
        text-align: center;
        margin-bottom: 20px;
    }

    .page-title h2 {
        color: var(--primary-color);
        margin-bottom: 5px;
    }

    .tickets-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .ticket {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 15px;
        border-left: 4px solid var(--primary-color);
    }

    .ticket-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .ticket-title {
        font-weight: bold;
        color: var(--primary-color);
    }

    .ticket-ref {
        background: var(--primary-color);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .journey-details {
        display: flex;
        justify-content: space-between;
        margin: 10px 0;
    }

    .station-name {
        font-weight: bold;
        font-size: 14px;
    }

    .station-time {
        font-size: 12px;
        color: #666;
    }

    .journey-arrow {
        align-self: center;
        color: var(--accent-color);
    }

    .journey-duration {
        text-align: center;
        font-size: 12px;
        color: #666;
        margin: 5px 0;
    }

    .ticket-class {
        display: flex;
        justify-content: space-between;
        margin: 10px 0;
        padding: 8px;
        background: #f8f9fa;
        border-radius: 4px;
    }

    .class-price {
        font-weight: bold;
        color: var(--primary-color);
    }

    .ticket-qr {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed var(--border-color);
    }

    .qr-code {
        width: 80px;
        height: 80px;
        border: 1px solid var(--border-color);
        padding: 5px;
    }

    .qr-info {
        font-size: 12px;
        color: #666;
    }

    .page-actions {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }

    .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    position: relative;
    overflow: hidden;
    min-width: 120px;
    text-align: center;
}

.btn::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent);
    transition: 0.5s;
}

.btn:hover::after {
    left: 100%;
}

.btn-back {
    background-color: var(--bg-light);
    color: var(--primary-color);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
}

.btn-back:hover {
    background-color: var(--border-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-all {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    box-shadow: 0 4px 6px rgba(6, 36, 91, 0.15);
}

.btn-all:hover {
    background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(6, 36, 91, 0.2);
}

.btn i {
    font-size: 1rem;
}

@media (max-width: 768px) {
    .page-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn {
        width: 100%;
    }
}

    /* Footer Styles */
        footer {
            background-color: var(--primary-dark);
            color: white;
            padding: 2rem 0 1rem;
            margin-top: 2rem;
        }

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

        /* Print Styles */
        @media print {
            @page {
                size: 100mm 180mm;
                margin: 0;
            }
            
            body {
                margin: 0;
                padding: 0;
                background-color: white;
            }
            
            .navbar, .page-title, .page-actions, footer {
                display: none;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .tickets-container {
                gap: 0;
                margin: 0;
            }
            
            .ticket {
                page-break-after: always;
                box-shadow: none;
                border: 1px solid #ddd;
                margin: 0;
                max-width: 100%;
            }
            
            .ticket-header {
                background: #06245b !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            

            
            .ticket:last-child {
                page-break-after: avoid;
            }
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .journey-details {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
                padding: 0.5rem;
            }
            
            .journey-arrow {
                transform: rotate(90deg);
                margin: 0.25rem 0;
            }
            
            .ticket-qr {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 0.5rem;
            }
            
            .page-actions {
                flex-direction: column;
            }
            
            .btn-back, .btn-all {
                width: 100%;
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
        </nav>
    </header>

    <!-- Main Content -->
    <main>

<div class="container">
    <div class="page-title">
        <h2>Vos billets de train</h2>
        <p>Référence: <?php echo $reservation['id']; ?></p>
    </div>

    <div class="tickets-container">
        <?php foreach ($billets as $index => $passenger): ?>

        <div class="ticket">
            <div class="ticket-header">
                <div class="ticket-title">Billet de train</div>
                <div class="ticket-ref"><?php echo htmlspecialchars($passenger['code_billet']); ?></div>
            </div>
            
            <div class="journey-details">
                <div class="journey-station">
                    <div class="station-name"><?php echo htmlspecialchars($reservation['gare_depart']); ?></div>
                    <div class="station-time"><?php echo (new DateTime($reservation['date_heure_depart']))->format('d/m/Y H:i'); ?></div>
                </div>
                <div class="journey-arrow">→</div>
                <div class="journey-station">
                    <div class="station-name"><?php echo htmlspecialchars($reservation['gare_arrivee']); ?></div>
                    <div class="station-time"><?php echo (new DateTime($reservation['date_heure_arrivee']))->format('d/m/Y H:i'); ?></div>
                </div>
            </div>
            
            <div class="journey-duration">
                Durée: <?php echo calculerDuree($reservation['date_heure_depart'], $reservation['date_heure_arrivee']); ?>
            </div>
            
            <div class="ticket-class">
    <div class="class-info">
        <?php 
            $classeBillet = $passenger['classe'] ?? 'economique';
            echo getClasse($classeBillet); 
        ?>
    </div>
    <div class="class-price">
        <?php 
            $prixBillet = $passenger['prix'] ?? 0;
            echo number_format($prixBillet, 0, ',', ' '); 
        ?> DA
    </div>
</div>

            
            <div class="ticket-qr">
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($passenger['code_billet']); ?>" width="80" height="80">
                </div>
                <div class="qr-info">
                    <strong>Code QR du billet</strong><br>
                    À présenter lors du contrôle
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="page-actions">
        <button class="btn btn-back" onclick="window.history.back()">Retour</button>
        <button class="btn btn-all" onclick="window.print()">Imprimer tous</button>
    </div>
</div>
</main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>À propos</h3>
                    <p>Service de réservation de billets de train pour Annaba et toute l'Algérie. Voyagez en toute sécurité et confort.</p>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>Email: contact@annaba-train.dz</p>
                    <p>Tél: +213 XX XX XX XX</p>
                    <p>Adresse: Gare d'Annaba, Algérie</p>
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
        // Fonction pour imprimer un billet spécifique
        function imprimerBillet(index) {
            const ticketElement = document.getElementById('ticket-' + index);
            
            // Créer une nouvelle fenêtre pour l'impression
            const printWindow = window.open('', '_blank');
            
            // Ajouter le contenu du ticket et les styles nécessaires
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Billet de Train</title>
                    <style>
                        @page {
                            size: A4;
                            margin: 15mm;
                            margin-top: 0;
                        }
                        
                        /* إخفاء رابط الموقع */
                        body::before,
                        body::after {
                            display: none !important;
                        }

                        a[href]:after {
                            content: none !important;
                        }
                        
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: 'Arial', sans-serif;
                            background: white;
                            color: #333;
                            line-height: 1.4;
                        }
                        
                        .ticket {
                            width: 100%;
                            max-width: 600px;
                            margin: 0 auto;
                            border: 3px solid #06245b;
                            border-radius: 15px;
                            overflow: hidden;
                            background: white;
                        }
                        
                        .ticket-header {
                            background: #06245b;
                            color: white;
                            padding: 20px;
                            text-align: center;
                        }
                        
                        .ticket-title h3 {
                            font-size: 24px;
                            margin-bottom: 10px;
                        }
                        
                        .ticket-ref {
                            font-size: 16px;
                            background: rgba(255,255,255,0.2);
                            padding: 8px 15px;
                            border-radius: 20px;
                            display: inline-block;
                        }
                        
                        .ticket-body {
                            padding: 25px;
                        }
                        
                        .journey-title {
                            font-size: 18px;
                            font-weight: bold;
                            color: #06245b;
                            margin-bottom: 15px;
                            padding-bottom: 8px;
                            border-bottom: 2px solid #e9ecef;
                        }
                        
                        .journey-details {
                            background: #f8f9fa;
                            padding: 20px;
                            border-radius: 10px;
                            margin-bottom: 15px;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                        }
                        
                        .journey-station {
                            text-align: center;
                            flex: 1;
                        }
                        
                        .station-name {
                            font-size: 16px;
                            font-weight: bold;
                            color: #06245b;
                            margin-bottom: 5px;
                        }
                        
                        .station-time {
                            font-size: 14px;
                            color: #666;
                        }
                        
                        .journey-arrow {
                            font-size: 24px;
                            color: #ff6b35;
                            margin: 0 20px;
                        }
                        
                        .journey-duration {
                            background: #10b981;
                            color: white;
                            padding: 8px 15px;
                            border-radius: 20px;
                            text-align: center;
                            margin-bottom: 15px;
                            font-weight: bold;
                        }
                        
                        .ticket-class {
                            background: #f8f9fa;
                            padding: 15px;
                            border-radius: 10px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 20px;
                            border-left: 5px solid #06245b;
                        }
                        
                        .class-info {
                            font-size: 16px;
                            font-weight: bold;
                        }
                        
                        .class-price {
                            background: #ff6b35;
                            color: white;
                            padding: 8px 15px;
                            border-radius: 20px;
                            font-weight: bold;
                            font-size: 16px;
                        }
                        
                        .ticket-qr {
                            background: #f8f9fa;
                            padding: 20px;
                            border-radius: 10px;
                            display: flex;
                            align-items: center;
                            gap: 20px;
                            border-top: 3px dashed #ccc;
                        }
                        
                        .qr-code {
                            width: 100px;
                            height: 100px;
                            border: 2px solid #ccc;
                            border-radius: 10px;
                            padding: 5px;
                            background: white;
                        }
                        
                        .qr-code img {
                            width: 100%;
                            height: 100%;
                        }
                        
                        .qr-info {
                            flex: 1;
                            font-size: 14px;
                            color: #666;
                            line-height: 1.5;
                        }
                        

                    </style>
                </head>
                <body>
                    ${ticketElement.outerHTML}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            printWindow.onload = function() {
                printWindow.print();
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            };
        }
        
        // Fonction pour imprimer tous les billets
        function imprimerTousBillets() {
            const tickets = document.querySelectorAll('.ticket');
            
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Tous les billets</title>
                    <style>
                        @page {
                            size: A4;
                            margin: 15mm;
                            margin-top: 0;
                        }
                        
                        /* إخفاء رابط الموقع */
                        body::before,
                        body::after {
                            display: none !important;
                        }

                        a[href]:after {
                            content: none !important;
                        }
                        
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: 'Arial', sans-serif;
                            background: white;
                            color: #333;
                            line-height: 1.4;
                        }
                        
                        .ticket {
                            width: 100%;
                            max-width: 600px;
                            margin: 0 auto 30px auto;
                            border: 3px solid #06245b;
                            border-radius: 15px;
                            overflow: hidden;
                            background: white;
                            page-break-after: always;
                        }
                        
                        .ticket:last-child {
                            page-break-after: avoid;
                        }
                        
                        .ticket-header {
                            background: #06245b;
                            color: white;
                            padding: 20px;
                            text-align: center;
                        }
                        
                        .ticket-title h3 {
                            font-size: 24px;
                            margin-bottom: 10px;
                        }
                        
                        .ticket-ref {
                            font-size: 16px;
                            background: rgba(255,255,255,0.2);
                            padding: 8px 15px;
                            border-radius: 20px;
                            display: inline-block;
                        }
                        
                        .ticket-body {
                            padding: 25px;
                        }
                        
                        .journey-title {
                            font-size: 18px;
                            font-weight: bold;
                            color: #06245b;
                            margin-bottom: 15px;
                            padding-bottom: 8px;
                            border-bottom: 2px solid #e9ecef;
                        }
                        
                        .journey-details {
                            background: #f8f9fa;
                            padding: 20px;
                            border-radius: 10px;
                            margin-bottom: 15px;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                        }
                        
                        .journey-station {
                            text-align: center;
                            flex: 1;
                        }
                        
                        .station-name {
                            font-size: 16px;
                            font-weight: bold;
                            color: #06245b;
                            margin-bottom: 5px;
                        }
                        
                        .station-time {
                            font-size: 14px;
                            color: #666;
                        }
                        
                        .journey-arrow {
                            font-size: 24px;
                            color: #ff6b35;
                            margin: 0 20px;
                        }
                        
                        .journey-duration {
                            background: #10b981;
                            color: white;
                            padding: 8px 15px;
                            border-radius: 20px;
                            text-align: center;
                            margin-bottom: 15px;
                            font-weight: bold;
                        }
                        
                        .ticket-class {
                            background: #f8f9fa;
                            padding: 15px;
                            border-radius: 10px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 20px;
                            border-left: 5px solid #06245b;
                        }
                        
                        .class-info {
                            font-size: 16px;
                            font-weight: bold;
                        }
                        
                        .class-price {
                            background: #ff6b35;
                            color: white;
                            padding: 8px 15px;
                            border-radius: 20px;
                            font-weight: bold;
                            font-size: 16px;
                        }
                        
                        .ticket-qr {
                            background: #f8f9fa;
                            padding: 20px;
                            border-radius: 10px;
                            display: flex;
                            align-items: center;
                            gap: 20px;
                            border-top: 3px dashed #ccc;
                        }
                        
                        .qr-code {
                            width: 100px;
                            height: 100px;
                            border: 2px solid #ccc;
                            border-radius: 10px;
                            padding: 5px;
                            background: white;
                        }
                        
                        .qr-code img {
                            width: 100%;
                            height: 100%;
                        }
                        
                        .qr-info {
                            flex: 1;
                            font-size: 14px;
                            color: #666;
                            line-height: 1.5;
                        }
                        

                    </style>
                </head>
                <body>
            `);
            
            tickets.forEach(ticket => {
                printWindow.document.write(ticket.outerHTML);
            });
            
            printWindow.document.write(`
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            printWindow.onload = function() {
                printWindow.print();
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            };
        }
    </script>
</body>
</html>
