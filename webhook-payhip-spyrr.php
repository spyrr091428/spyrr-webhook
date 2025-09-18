emailjs_service_id=service_7bfwpfm
emailjs_template_premium= template_4lesgvh
emailjs_template_consultation=template_consultation
emailjs_public_key=RRvc1ifIrhay8-fVV
PAYHIP_API_KEY=whsec_2c862227efd5165894ab03453940e5a672f15253 
PAYHIP_VALID_PRODUCT_IDS=RDubp  

<?php
// =============================================
// WEBHOOK PAYHIP POUR SPYRR - Version 2024
// Gère les achats Payhip et envoie les accès Premium
// =============================================

// ========== CONSTANTES GLOBALES ==========
define('DEBUG_MODE', true); // Passe à false en production

// ========== CHARGEMENT DES VARIABLES D'ENVIRONNEMENT ==========
$env = parse_ini_file('.env');
if (!$env) {
    die("❌ Fichier .env manquant ou illisible");
}
foreach ($env as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
}

// ========== CONFIGURATION ==========
$config = [
    'payhip' => [
        'api_key'          => $_ENV['whsec_2c862227efd5165894ab03453940e5a672f15253'] ?? die("❌ Clé Payhip manquante (PAYHIP_API_KEY)"),
        'valid_product_ids'=> ['RDubp'], // IDs des produits éligibles (à vérifier)
        'log_dir'          => $_ENV['LOG_DIR'] ?? '/var/log/spyrr',
        'webhook_secret'   => $_ENV['PAYHIP_WEBHOOK_SECRET'] ?? '' // Optionnel pour plus de sécurité
    ],
    'emailjs' => [
        'service_id'       => $_ENV['service_7bfwpfm'] ?? die("❌ Service ID EmailJS manquant"),
        'template_id'      => $_ENV['template_4lesgvh'] ?? die("❌ Template ID EmailJS manquant"),
        'user_id'          => $_ENV['EMAILJS_USER_ID_PAYHIP'] ?? die("❌ User ID EmailJS manquant"),
        'public_key'       => $_ENV['RRvc1ifIrhay8-fVV'] ?? die("❌ Clé publique EmailJS manquante")
    ],
    'premium' => [
        'code_prefix'      => 'SPYRR-',
        'code_length'      => 12, // Longueur du code aléatoire
        'access_duration'  => '12 months'
    ]
];

// ========== FONCTIONS UTILITAIRES ==========
/**
 * Génère un code Premium unique
 */
function generatePremiumCode($email, $config) {
    $uniquePart = substr(md5($email . time() . rand(1000, 9999)), 0, $config['premium']['code_length']);
    return $config['premium']['code_prefix'] . strtoupper($uniquePart);
}

/**
 * Enregistre un log structuré
 */
function logEvent($type, $message, $data = [], $config) {
    $logDir = $config['payhip']['log_dir'] . '/payhip';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $log = [
        "timestamp"   => date('c'),
        "type"       => strtoupper($type),
        "message"    => $message,
        "data"       => $data,
        "source"     => "PAYHIP_WEBHOOK"
    ];

    $logFile = $logDir . '/webhook_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    if ($type === 'error') {
        error_log("[PAYHIP ERROR] $message");
    }
}

/**
 * Vérifie la signature Payhip (sécurité)
 */
function verifySignature($payload, $signature, $config) {
    if (empty($config['payhip']['webhook_secret'])) {
        return true; // Mode non sécurisé (à éviter en production)
    }

    $expectedSignature = hash_hmac('sha256', $payload, $config['payhip']['webhook_secret']);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Envoie un email via EmailJS
 */
function sendEmailJS($email, $templateParams, $config) {
    $url = 'https://api.emailjs.com/api/v1.0/email/send';
    $headers = [
        'Content-Type: application/json',
        'Origin: http://localhost' // Requis par EmailJS
    ];

    $data = [
        'service_id'     => $config['emailjs']['service_id'],
        'template_id'    => $config['emailjs']['template_id'],
        'user_id'        => $config['emailjs']['user_id'],
        'template_params' => $templateParams
    ];

    $options = [
        'http' => [
            'header'  => $headers,
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        logEvent('error', 'Échec de l\'envoi EmailJS', ['data' => $data], $config);
        return false;
    }

    logEvent('info', 'Email envoyé avec succès', ['email' => $email, 'response' => $response], $config);
    return true;
}

// ========== TRAITEMENT DU WEBHOOK ==========
try {
    // 1. Récupérer les données brutes
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);

    // 2. Vérifier la signature (sécurité)
    $signature = $_SERVER['HTTP_X_PAYHIP_SIGNATURE'] ?? '';
    if (!verifySignature($payload, $signature, $config)) {
        logEvent('error', 'Signature Payhip invalide - Requête rejetée', ['signature' => $signature], $config);
        http_response_code(403);
        die("❌ Signature invalide");
    }

    // 3. Valider les données requises
    if (empty($data['order']) || empty($data['product_id']) || empty($data['email'])) {
        logEvent('error', 'Données manquantes dans le payload', $data, $config);
        http_response_code(400);
        die("❌ Données manquantes");
    }

    // 4. Vérifier que le produit est éligible
    if (!in_array($data['product_id'], $config['payhip']['valid_product_ids'])) {
        logEvent('warning', 'Produit non éligible', $data, $config);
        http_response_code(200); // On ignore silencieusement
        die("✅ Produit non concerné (ignoré)");
    }

    // 5. Générer un code Premium
    $premiumCode = generatePremiumCode($data['email'], $config);

    // 6. Préparer l'email
    $emailParams = [
        'customer_name'    => $data['buyer']['name'] ?? 'Cher client',
        'premium_code'     => $premiumCode,
        'access_duration'  => $config['premium']['access_duration'],
        'order_id'         => $data['order']['transaction_id'],
        'product_name'     => $data['product']['name'] ?? 'Accès Premium Spyrr'
    ];

    // 7. Envoyer l'email
    $emailSent = sendEmailJS($data['email'], $emailParams, $config);

    // 8. Log final
    if ($emailSent) {
        logEvent('success', 'Accès Premium généré et envoyé', [
            'email'      => $data['email'],
            'product_id' => $data['product_id'],
            'code'       => $premiumCode
        ], $config);
    } else {
        logEvent('error', 'Échec de l\'envoi de l\'email', $emailParams, $config);
    }

    // 9. Répondre à Payhip
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook traité avec succès']);

} catch (Exception $e) {
    logEvent('error', 'Erreur critique: ' . $e->getMessage(), [], $config);
    http_response_code(500);
    die("❌ Erreur serveur");
}
?>
