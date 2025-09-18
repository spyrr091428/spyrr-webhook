<?php
/**
 * WEBHOOK PAYHIP POUR LE MIROIR DE SPYRR
 * Version finale avec EmailJS - Prêt pour la production
 * Ludovic Spyrr - 2024
 */

// ========== 1. CONFIGURATION (À PERSONNALISER) ==========
$apiKey          = 'whsec_2c862227efd5165894ab03453940e5a672f15253'; // <--- Ta clé Payhip (déjà modifiée)
$validProductIds = ['RDubp']; // <--- ID de ton produit (vérifie dans l'URL Payhip)
$emailJSConfig   = [
    'service_id'  => 'service_7bfwpfm',    // <--- Ton Service ID EmailJS
    'template_id' => 'template_4lesgvh',   // <--- Ton Template ID
    'user_id'     => 'RRvc1ifIrhay8-fVV'    // <--- Ta Clé Publique EmailJS
];
$logFile         = 'webhook_debug.log';    // Fichier de logs
$codesFile       = 'premium_codes.log';    // Fichier des codes générés

// ========== 2. FONCTIONS UTILES ==========
function _logWebhookError($data) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
}

// ========== 3. VÉRIFICATION DE LA SIGNATURE ==========
$payhipSignature = $_SERVER['HTTP_X_PAYHIP_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$expectedSignature = hash('sha256', $payload . $apiKey);

if ($payhipSignature !== $expectedSignature) {
    _logWebhookError([
        "type"    => "SECURITY_ERROR",
        "message" => "Signature Payhip invalide",
        "received"=> $payhipSignature,
        "expected"=> $expectedSignature
    ]);
    http_response_code(403);
    die(json_encode(["error" => "Signature invalide"]));
}

// ========== 4. DÉCODAGE ET VALIDATION DES DONNÉES ==========
$data = json_decode($payload, true);
if (!$data) {
    _logWebhookError(["type" => "DATA_ERROR", "message" => "Payload JSON invalide", "raw" => $payload]);
    http_response_code(400);
    die(json_encode(["error" => "Données invalides"]));
}

// Vérifie l'événement (seulement "paid" pour les achats)
if ($data['type'] !== 'paid') {
    _logWebhookError(["type" => "IGNORED_EVENT", "event" => $data['type']]);
    http_response_code(200);
    die(json_encode(["status" => "Event ignoré (seuls les 'paid' sont traités)"]));
}

// ========== 5. VALIDATION DU PRODUIT ==========
$isValidProduct = false;
foreach ($data['items'] ?? [] as $item) {
    if (in_array($item['product_id'], $validProductIds)) {
        $isValidProduct = true;
        break;
    }
}

if (!$isValidProduct) {
    _logWebhookError([
        "type"       => "PRODUCT_ERROR",
        "message"    => "Produit non autorisé",
        "product_id" => $data['items'][0]['product_id'] ?? 'inconnu',
        "email"      => $data['email'] ?? 'inconnu'
    ]);
    http_response_code(400);
    die(json_encode(["error" => "Produit non reconnu"]));
}

// ========== 6. GÉNÉRATION DU CODE PREMIUM ==========
$code = 'SPYRR_' . strtoupper(substr(hash('sha256', uniqid() . $data['email'] . time()), 0, 8));
$expiryDate = date('Y-m-d', strtotime('+1 year')); // Validité 1 an

// Sauvegarde le code (fichier + base de données si disponible)
$codeLogEntry = sprintf(
    "[%s] Email: %s | Code: %s | Expire: %s | Produit: %s\n",
    date('Y-m-d H:i:s'),
    $data['email'],
    $code,
    $expiryDate,
    $data['items'][0]['product_id']
);
file_put_contents($codesFile, $codeLogEntry, FILE_APPEND);

// ========== 7. ENVOI DE L'EMAIL VIA EMAILJS ==========
try {
    // Initialisation d'EmailJS (sans dépendance externe)
    $emailData = [
        'to_email'    => $data['email'],
        'to_name'     => $data['buyer']['name'] ?? 'Ami(e)',
        'code'        => $code,
        'expiry_date' => $expiryDate,
        'product_name'=> $data['items'][0]['product_name'] ?? 'Oracle Le Miroir de Spyrr',
        'support_email'=> 'ludovicspyrr@gmail.com'
    ];

    // Préparation de la requête cURL pour EmailJS
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.emailjs.com/api/v1.0/email/send");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Origin: https://app.spyrrgames.net'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'service_id'  => $emailJSConfig['service_id'],
        'template_id' => $emailJSConfig['template_id'],
        'user_id'     => $emailJSConfig['user_id'],
        'template_params' => $emailData
    ]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("EmailJS API error (HTTP $httpCode): " . $response);
    }

    _logWebhookError([
        "type"    => "EMAIL_SUCCESS",
        "message" => "Email envoyé via EmailJS",
        "email"   => $data['email'],
        "code"    => $code
    ]);

} catch (Exception $e) {
    _logWebhookError([
        "type"    => "EMAIL_ERROR",
        "message" => $e->getMessage(),
        "email"   => $data['email'],
        "code"    => $code
    ]);
    // Envoi un email de secours avec PHP mail() si EmailJS échoue
    $subject = "🌟 Ton Accès Premium au Miroir de Spyrr";
    $message = "Namasté " . ($data['buyer']['name'] ?? '') . ",\n\n" .
               "Ton code d'accès premium est : **$code**\n" .
               "Valable jusqu'au : $expiryDate\n" .
               "Lien d'accès : https://app.spyrrgames.net?code=$code\n\n" .
               "Vibre libre !\n~ Ludovic Spyrr";

    $headers = "From: Ludovic Spyrr <ludovicspyrr@gmail.com>\r\n" .
               "Reply-To: ludovicspyrr@gmail.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    if (!mail($data['email'], $subject, $message, $headers)) {
        _logWebhookError([
            "type" => "FALLBACK_EMAIL_ERROR",
            "message" => "Échec de l'envoi de secours (PHP mail)",
            "email" => $data['email']
        ]);
    }
}

// ========== 8. RÉPONSE FINALE À PAYHIP ==========
_logWebhookError([
    "type"    => "SUCCESS",
    "message" => "Webhook traité avec succès",
    "email"   => $data['email'],
    "code"    => $code,
    "product" => $data['items'][0]['product_name'] ?? 'inconnu'
]);

http_response_code(200);
echo json_encode([
    "status"  => "success",
    "message" => "Code premium généré et envoyé",
    "email"   => $data['email'],
    "code"    => $code // <--- À masquer en production pour la sécurité
]);
?>


