<?php
// ===== CONFIGURATION =====
define('PAYHIP_WEBHOOK_TOKEN', 'ubaivahk2P*');
define('EMAILJS_SERVICE_ID', 'service_7bfwpfm');
define('EMAILJS_TEMPLATE_ID', 'template_4lesgvh');
define('EMAILJS_PUBLIC_KEY', 'RRvc1ifIrhay8-fVV');
define('LOG_FILE', 'payhip_webhook.log');

// ===== FONCTIONS =====
function logMessage($message) {
    file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] $message" . PHP_EOL, FILE_APPEND);
}

function generatePremiumCode() {
    return 'SPYRR' . date('Y') . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
}

function sendPremiumCodeEmail($to_email, $premium_code) {
    $url = "https://api.emailjs.com/api/v1.0/email/send";
    $data = [
        'service_id' => EMAILJS_SERVICE_ID,
        'template_id' => EMAILJS_TEMPLATE_ID,
        'user_id' => EMAILJS_PUBLIC_KEY,
        'template_params' => [
            'to_email' => $to_email,
            'premium_code' => $premium_code,
            'buyer_name' => 'Ami(e) des Étoiles'
        ]
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        $error = error_get_last();
        logMessage("ERREUR EmailJS : " . $error['message']);
        return false;
    }

    logMessage("Réponse EmailJS : $response");
    return $response;
}

// ===== VÉRIFICATION DU TOKEN =====
if ($_GET['token'] !== PAYHIP_WEBHOOK_TOKEN) {
    logMessage("ERREUR : Token invalide.");
    http_response_code(401);
    exit('Accès non autorisé');
}

// ===== LECTURE DU JSON =====
$json = file_get_contents('php://input');
logMessage("JSON reçu : " . ($json ?: 'vide'));

$data = json_decode($json, true);
if ($data === null) {
    logMessage("ERREUR JSON : " . json_last_error_msg());
    http_response_code(400);
    exit('Données JSON invalides');
}

// ===== VALIDATION DES DONNÉES =====
if (empty($data['data']['order']['buyer_email'])) {
    logMessage("ERREUR : Email acheteur manquant.");
    http_response_code(400);
    exit('Email acheteur requis');
}

$buyer_email = $data['data']['order']['buyer_email'];
$premium_code = generatePremiumCode();
logMessage("Code généré : $premium_code pour $buyer_email");

// ===== ENVOI DE L'EMAIL =====
$email_sent = sendPremiumCodeEmail($buyer_email, $premium_code);
if ($email_sent === false) {
    logMessage("ÉCHEC : Email non envoyé à $buyer_email.");
    http_response_code(500);
    exit('Erreur lors de l’envoi de l’email');
}

// ===== RÉPONSE =====
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'premium_code' => $premium_code,
    'email' => $buyer_email,
]);
?>
