<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Vérifier si les informations de réservation sont présentes
if (!isset($_SESSION['reservation_info'])) {
    header("Location: utilisateur.php");
    exit();
}

$reservationInfo = $_SESSION['reservation_info'];
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? '';

// Générer un ID de transaction unique
$transactionId = 'TXN' . date('YmdHis') . rand(1000, 9999);

// Stocker les informations de transaction
$_SESSION['transaction'] = [
    'id' => $transactionId,
    'outbound_id' => $reservationInfo['outbound_id'],
    'return_id' => $reservationInfo['return_id'],
    'passengers_count' => $reservationInfo['passengers_count'],
    'total_price' => $reservationInfo['total_price'],
    'payment_method' => 'edahabia',
    'classe' => $reservationInfo['classe']
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Edahabia - Annaba Train</title>
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #06245b;
            --primary-light: #1a3a7a;
            --primary-dark: #041a4a;
            --accent-color: #ff6b35;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #f8f9fa;
            --bg-light: #ffffff;
            --border-color: #e5e7eb;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .payment-card {
            background: var(--bg-light);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .payment-header {
            background: linear-gradient(135deg, #2d5a27, #1e3a1a);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .payment-header h2 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .payment-header p {
            margin: 0;
            opacity: 0.9;
        }

        .payment-body {
            padding: 2rem;
        }

        .transaction-info {
            background: var(--bg-color);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .transaction-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .transaction-row:last-child {
            margin-bottom: 0;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
            border-top: 1px solid var(--border-color);
            padding-top: 0.75rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus {
            border-color: #2d5a27;
            outline: none;
            box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.1);
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
            text-decoration: none;
            border: none;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2d5a27, #1e3a1a);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1e3a1a, #0f1d0d);
        }

        .btn-secondary {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
        }

        .edahabia-info {
            background: #f0f9f0;
            border: 1px solid #2d5a27;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .edahabia-info h3 {
            color: #2d5a27;
            margin-top: 0;
            margin-bottom: 1rem;
        }

        .edahabia-info ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .edahabia-info li {
            margin-bottom: 0.5rem;
            color: #1e3a1a;
        }

        .security-info {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #0369a1;
        }

        .security-info i {
            color: #0ea5e9;
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem auto;
            }
            
            .payment-header {
                padding: 1.5rem;
            }
            
            .payment-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h2><i class="fas fa-wallet"></i> Paiement Edahabia</h2>
                <p>Payez avec votre carte Edahabia d'Algérie Poste</p>
            </div>
            
            <div class="payment-body">
                <div class="transaction-info">
                    <h3 style="margin-top: 0; color: var(--primary-color);">Récapitulatif de la transaction</h3>
                    <div class="transaction-row">
                        <span>ID Transaction:</span>
                        <span><?php echo $transactionId; ?></span>
                    </div>
                    <div class="transaction-row">
                        <span>Nombre de passagers:</span>
                        <span><?php echo $reservationInfo['passengers_count']; ?></span>
                    </div>
                    <div class="transaction-row">
                        <span>Méthode de paiement:</span>
                        <span>Carte Edahabia</span>
                    </div>
                    <div class="transaction-row">
                        <span>Montant total:</span>
                        <span><?php echo number_format($reservationInfo['total_price'], 0, ',', ' '); ?> DA</span>
                    </div>
                </div>

                <div class="edahabia-info">
                    <h3><i class="fas fa-info-circle"></i> Informations Edahabia</h3>
                    <ul>
                        <li>Assurez-vous que votre carte Edahabia est activée pour les paiements en ligne</li>
                        <li>Vérifiez que votre solde est suffisant pour effectuer le paiement</li>
                        <li>Gardez votre téléphone à portée de main pour recevoir le code de confirmation</li>
                        <li>Le paiement sera traité de manière sécurisée via le système d'Algérie Poste</li>
                    </ul>
                </div>

                <form id="paymentForm" action="process_payment.php" method="post">
                    <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
                    <input type="hidden" name="payment_method" value="edahabia">
                    
                    <div class="form-group">
                        <label for="card_number">Numéro de carte Edahabia</label>
                        <input type="text" id="card_number" name="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pin">Code PIN</label>
                        <input type="password" id="pin" name="pin" placeholder="Votre code PIN" maxlength="4" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Numéro de téléphone</label>
                        <input type="tel" id="phone" name="phone" placeholder="0XXX XX XX XX" required>
                    </div>
                    
                    <a href="reservation.php?outbound=<?php echo $reservationInfo['outbound_id']; ?>&return=<?php echo $reservationInfo['return_id']; ?>&passengers=<?php echo $reservationInfo['passengers_count']; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Retour
                    </a>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-lock"></i>
                        Payer <?php echo number_format($reservationInfo['total_price'], 0, ',', ' '); ?> DA
                    </button>
                </form>
                
                <div class="security-info">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Paiement sécurisé:</strong> Votre transaction est protégée par le système de sécurité d'Algérie Poste. Aucune information sensible n'est stockée sur nos serveurs.
                </div>
            </div>
        </div>
    </div>

    <script>
        // Formatage automatique du numéro de carte
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Validation PIN
        document.getElementById('pin').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });

        // Formatage du numéro de téléphone
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            if (value.length > 0 && !value.startsWith('0')) {
                value = '0' + value;
            }
            e.target.value = value;
        });

        // Validation du formulaire
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
            const pin = document.getElementById('pin').value;
            const phone = document.getElementById('phone').value;

            if (cardNumber.length < 16) {
                alert('Veuillez saisir un numéro de carte Edahabia valide');
                e.preventDefault();
                return;
            }

            if (pin.length !== 4) {
                alert('Veuillez saisir un code PIN à 4 chiffres');
                e.preventDefault();
                return;
            }

            if (phone.length < 10) {
                alert('Veuillez saisir un numéro de téléphone valide');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
