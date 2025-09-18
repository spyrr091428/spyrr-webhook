<?php
// ===== CONFIGURATION =====
define('PAYHIP_WEBHOOK_TOKEN', 'ubaivahk2P*'); // Ton token (à protéger !)
define('EMAILJS_SERVICE_ID', 'service_7bfwpfm');
define('EMAILJS_TEMPLATE_ID', 'template_4lesgvh');
define('EMAILJS_PUBLIC_KEY', 'RRvc1ifIrhay8-fVV');
define('LOG_FILE', 'payhip_webhook.log');

// ===== FONCTIONS UTILES =====
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $log, FILE_APPEND);
    error_log($log);
}

// ===== SÉCURITÉ : Vérification du token =====
$received_token = $_GET['token'] ?? '';
logMessage("Token reçu : '" . $received_token . "'");
logMessage("Token attendu : '" . PAYHIP_WEBHOOK_TOKEN . "'");

if ($received_token !== PAYHIP_WEBHOOK_TOKEN) {
    logMessage("ERREUR : Token invalide ou manquant.");
    http_response_code(401);
    exit('Accès non autorisé');
}

// ===== TRAITEMENT DU WEBHOOK =====
logMessage("Token valide. Traitement de la requête...");

// Récupérer les données POST (pour Payhip)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data === null) {
    logMessage("Aucune donnée JSON valide reçue.");
    http_response_code(400);
    exit('Données JSON invalides');
}

logMessage("Données reçues : " . print_r($data, true));

// ===== GÉNÉRATION DU CODE PREMIUM =====
function generatePremiumCode() {
    $prefix = 'SPYRR' . date('Y');
    $random = strtoupper(substr(md5(uniqid()), 0, 8));
    return $prefix . '_' . $random;
}

$premium_code = generatePremiumCode();
$buyer_email = $data['data']['order']['buyer_email'] ?? 'inconnu@test.com';

logMessage("Code premium généré : $premium_code pour $buyer_email");

// ===== ENVOI DE L'EMAIL (EXEMPLE AVEC EMAILJS) =====
// (À adapter selon ta configuration EmailJS)
logMessage("Envoi de l'email à $buyer_email avec le code $premium_code...");
// Ici, tu appellerais l'API EmailJS avec $buyer_email et $premium_code

// ===== RÉPONSE AU CLIENT =====
http_response_code(200);
echo "Webhook Payhip traité avec succès. Code premium : $premium_code";
?>

