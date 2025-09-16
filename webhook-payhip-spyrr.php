<?php
// ===== CONFIGURATION =====
define('PAYHIP_WEBHOOK_TOKEN', 'TON_TOKEN_SECRET'); // Remplace par ton token
define('EMAILJS_SERVICE_ID', 'service_7bfwpfm');
define('EMAILJS_TEMPLATE_ID', 'template_4lesgvh');
define('EMAILJS_PUBLIC_KEY', 'RRvc1ifIrhay8-fVV');
define('LOG_FILE', 'payhip_webhook.log');

// ===== SÉCURITÉ : Vérification du token =====
if ($_GET['token'] !== PAYHIP_WEBHOOK_TOKEN) {
    error_log("Token invalide : " . ($_GET['token'] ?? 'aucun token'));
    http_response_code(401);
    exit('Accès non autorisé');
}

// ===== RÉCUPÉRATION DES DONNÉES PAYHIP =====
$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Erreur JSON : " . json_last_error_msg());
    http_response_code(400);
    exit('Données invalides');
}

// Vérifie que c'est un événement "purchase"
if (($data['event'] ?? '') !== 'purchase') {
    error_log("Événement non géré : " . ($data['event'] ?? 'aucun événement'));
    http_response_code(400);
    exit('Événement non supporté');
}

// ===== EXTRACTION DES INFOS CLIENT =====
$order_data = $data['data']['order'] ?? [];
$product_name = $order_data['product_name'] ?? '';
$buyer_email = $order_data['buyer_email'] ?? '';
$buyer_name = trim(($order_data['buyer_first_name'] ?? '') . ' ' . ($order_data['buyer_last_name'] ?? ''));

// ===== VÉRIFICATION DU PRODUIT =====
$valid_product_name = "Code Premium – Oracle Le Miroir de Spyrr (12 mois)";
if ($product_name !== $valid_product_name) {
    error_log("Produit non valide : " . $product_name);
    http_response_code(400);
    exit('Produit non éligible');
}

// ===== GÉNÉRATION DU CODE PREMIUM =====
function generatePremiumCode() {
    $prefix = 'SPYRR' . date('Y');
    $random_part = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    return $prefix . '_' . $random_part;
}

$premium_code = generatePremiumCode();
logMessage("Code généré pour $buyer_email : $premium_code");

// ===== ENVOI DE L'EMAIL VIA EMAILJS =====
function sendEmailViaEmailJS($to_email, $to_name, $premium_code) {
    $url = 'https://api.emailjs.com/api/v1.0/email/send';
    $data = [
        'service_id' => EMAILJS_SERVICE_ID,
        'template_id' => EMAILJS_TEMPLATE_ID,
        'user_id' => EMAILJS_PUBLIC_KEY,
        'template_params' => [
            'to_email' => $to_email,
            'to_name' => $to_name,
            'premium_code' => $premium_code,
            'product_name' => $GLOBALS['valid_product_name']
        ]
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === false) {
        error_log("Erreur EmailJS : " . error_get_last()['message']);
        return false;
    }

    logMessage("Email envoyé à $to_email avec succès");
    return true;
}

// ===== ENVOI ET LOG =====
$email_sent = sendEmailViaEmailJS($buyer_email, $buyer_name, $premium_code);

if ($email_sent) {
    http_response_code(200);
    echo "Webhook Payhip traité avec succès. Code envoyé à $buyer_email.";
} else {
    http_response_code(500);
    echo "Erreur lors de l'envoi de l'email.";
}

// ===== FONCTION DE LOG =====
function logMessage($message) {
    $log_entry = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND);
}
