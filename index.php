<?php
/**
 * INDEX.PHP - MIROIR DE SPYRR
 * Gère les webhooks Payhip et les requêtes normales.
 * Ludovic Spyrr - 2024
 */

// =============================================
// 1. CHARGER LES VARIABLES D'ENVIRONNEMENT (CLÉS SECRÈTES)
// =============================================
require __DIR__ . '/vendor/autoload.php'; // Charge les outils (composer)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Charge les variables du .env (ou de Render)

// =============================================
// 2. GESTION DU WEBHOOK PAYHIP (POUR LES PAIEMENTS)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_PAYHIP_SIGNATURE'])) {
    // Vérifier le token du webhook (sécurité)
    $webhookToken = $_ENV['PAYHIP_WEBHOOK_TOKEN'] ?? '';
    $receivedToken = $_SERVER['HTTP_X_PAYHIP_SIGNATURE'] ?? '';

    if ($receivedToken !== $webhookToken) {
        http_response_code(403);
        die('❌ Accès refusé : Token invalide.');
    }

    // Lire les données du paiement
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(400);
        die('❌ Données invalides.');
    }

    // ====== TRAITEMENT DES ÉVÉNEMENTS PAYHIP ======
    $event = $data['event'] ?? '';
    $orderId = $data['order']['id'] ?? '';
    $emailClient = $data['order']['email'] ?? '';
    $productName = $data['order']['products'][0]['name'] ?? '';

    // Exemple : Envoi d'un email via EmailJS si paiement réussi
    if ($event === 'subscription:activated' || $event === 'payment:confirmed') {
        // Générer un code premium aléatoire (ex: SPYRR-XXXX-XXXX)
        $codePremium = 'SPYRR-' . strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(2)));

        // Enregistrer le code dans un fichier (ou une base de données plus tard)
        file_put_contents(__DIR__ . '/premium_codes.txt', "$emailClient,$codePremium,$productName\n", FILE_APPEND);

        // ====== ENVOI DE L'EMAIL VIA EMAILJS ======
        $serviceId = $_ENV['EMAILJS_SERVICE_ID'];
        $templateId = $_ENV['EMAILJS_TEMPLATE_ID'];
        $publicKey = $_ENV['EMAILJS_PUBLIC_KEY'];

        $emailData = [
            'service_id' => $serviceId,
            'template_id' => $templateId,
            'user_id' => $publicKey,
            'template_params' => [
                'to_email' => $emailClient,
                'code_premium' => $codePremium,
                'product_name' => $productName,
                'message' => "Merci d'avoir rejoint l'univers Spyrr ! Voici ton code premium : **$codePremium**\n\nVibre libre, ami(e)."
            ]
        ];

        // Envoyer la requête à EmailJS
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.emailjs.com/api/v1.0/email/send");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Origin: https://spyrr.net'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Log pour débogage
        file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - Email envoyé à $emailClient (Code: $codePremium)\n", FILE_APPEND);
    }

    // Répondre à Payhip (obligatoire)
    http_response_code(200);
    die('✅ Webhook reçu avec succès.');
}

// =============================================
// 3. PAGE NORMALE (SI CE N'EST PAS UN WEBHOOK)
// =============================================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miroir de Spyrr - Univers Vibratoire</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0e6ff;
            color: #333;
            text-align: center;
            padding: 50px;
        }
        h1 {
            color: #8a2be2;
            font-size: 2.5em;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌟 Bienvenue dans l'Univers Spyrr 🌟</h1>
        <p>Ton code premium t'a été envoyé par email après ton paiement.</p>
        <p>Si tu n'as pas reçu ton code, contacte-nous à : <a href="mailto:contact@spyrr.net">contact@spyrr.net</a></p>
        <hr>
        <p><small>© <?php echo date('Y'); ?> Ludovic Spyrr - Vibre Libre.</small></p>
    </div>
</body>
</html>