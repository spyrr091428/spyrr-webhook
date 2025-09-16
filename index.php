<?php
/**
 * INDEX.PHP - MIROIR DE SPYRR (Version Webhook Payhip)
 * Gère les paiements Payhip et génère des codes premium.
 * URL : https://spyrr-webhook.onrender.com/webhook-payhip-spyrr.php?token=ubaivahk2P*
 * Ludovic Spyrr - 2024
 */

// =============================================
// 1. CONFIGURATION INITIALE
// =============================================
header('Content-Type: text/plain; charset=utf-8');

// Vérifier le token dans l'URL (sécurité basique)
$validToken = 'ubaivahk2P*'; // À remplacer par une variable d'environnement plus tard
$receivedToken = $_GET['token'] ?? '';

if ($receivedToken !== $validToken) {
    http_response_code(403);
    die('❌ Accès refusé : Token invalide.');
}

// =============================================
// 2. GESTION DU WEBHOOK PAYHIP
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enregistrer les données brutes pour débogage
    file_put_contents(__DIR__ . '/webhook_debug.log',
        "=== NOUVELLE REQUÊTE (" . date('Y-m-d H:i:s') . ") ===\n" .
        "Headers: " . print_r(getallheaders(), true) . "\n" .
        "POST: " . print_r($_POST, true) . "\n" .
        "RAW INPUT: " . file_get_contents('php://input') . "\n\n",
        FILE_APPEND);

    // Extraire les données Payhip (format spécifique)
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Si JSON invalide, essayer avec $_POST
    if (!$data && isset($_POST['data'])) {
        $data = json_decode($_POST['data'], true);
    }

    // Vérifier les données minimales
    if (!$data || !isset($data['event'])) {
        http_response_code(400);
        die('❌ Données Payhip invalides.');
    }

    // =============================================
    // 3. TRAITEMENT DE LA COMMANDE
    // =============================================
    $event = $data['event'];
    $emailClient = $data['order']['email'] ?? '';
    $productName = $data['order']['products'][0]['name'] ?? '';

    // Générer un code premium unique
    $codePremium = 'SPYRR-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    // Enregistrer le code dans un fichier
    file_put_contents(__DIR__ . '/premium_codes.txt',
        date('Y-m-d H:i:s') . " | $emailClient | $productName | $codePremium\n",
        FILE_APPEND);

    // =============================================
    // 4. ENVOI DE L'EMAIL (Simulation - à remplacer par EmailJS)
    // =============================================
    $emailContent = "
    ===== CODE D'ACCÈS À L'ORACLE MIROIR DE SPYRR =====
    Bonjour,
    Merci pour votre achat ! Voici votre code premium :
    ► $codePremium
    Utilisez-le sur : https://app.spyrrgames.net
    Lumière à vous,
    Ludovic Spyrr
    ";

    // Enregistrement dans les logs (remplacera EmailJS pour les tests)
    file_put_contents(__DIR__ . '/email_logs.log',
        date('Y-m-d H:i:s') . " - Email envoyé à $emailClient\n" .
        "Contenu:\n$emailContent\n\n",
        FILE_APPEND);

    // Réponse à Payhip (obligatoire)
    http_response_code(200);
    die('✅ Webhook traité avec succès. Code généré : ' . $codePremium);
}

// =============================================
// 5. PAGE PAR DÉFAUT (si accès direct)
// =============================================
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Spyrr - Miroir de l'Âme</title>
    <style>
        body {
            background-color: #6a0dad;
            color: white;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 0 0 10px #fff;
        }
    </style>
</head>
<body>
    <div class="logo">🌌 Spyrr Webhook 🌌</div>
    <p>Ce point d'accès est réservé aux notifications Payhip.</p>
    <p>Pour tester : <a href="https://spyrr.net" style="color: #ffd700;">retourner sur Spyrr.net</a></p>
</body>
</html>
