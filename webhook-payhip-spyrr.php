<?php
// ========== CONFIGURATION SÉCURISÉE ==========
$apiKey = $_ENV['PAYHIP_API_KEY'] ?? die("❌ Clé Payhip manquante");
$emailJSConfig = [
    'service_id'  => $_ENV['EMAILJS_SERVICE_ID'] ?? die("❌ Service ID manquant"),
    'template_id' => $_ENV['EMAILJS_TEMPLATE_ID'] ?? die("❌ Template ID manquant"),
    'user_id'     => $_ENV['EMAILJS_USER_ID'] ?? die("❌ User ID manquant")
];
$validProductIds = ['RDubp']; // <--- Vérifie cet ID
$logFile = 'webhook_'.date('Y-m').'.log'; // Logs mensuels

// ========== FONCTIONS ==========
function _log($type, $message, $data = []) {
    global $logFile;
    $log = [
        "timestamp" => date('c'),
        "type"      => $type,
        "message"   => $message,
        "data"      => $data,
        "ip"        => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT)."\n", FILE_APPEND);
}

// ========== VÉRIFICATION DE SÉCURITÉ ==========
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    _log('SECURITY', 'Méthode non autorisée', ['method' => $_SERVER['REQUEST_METHOD']]);
    http_response_code(405);
    die(json_encode(["error" => "Méthode non autorisée"]));
}

$payhipSignature = $_SERVER['HTTP_X_PAYHIP_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');

_log('DEBUG', 'Requête reçue', [
    'signature' => $payhipSignature,
    'payload_size' => strlen($payload)
]);

$expectedSignature = hash('sha256', $payload . $apiKey);

if (!hash_equals($expectedSignature, $payhipSignature)) {
    _log('SECURITY', 'Signature invalide', [
        'expected' => $expectedSignature,
        'received' => $payhipSignature
    ]);
    http_response_code(403);
    die(json_encode(["error" => "Signature invalide"]));
}

// ========== TRAITEMENT ==========
$data = json_decode($payload, true);
if (!$data || $data['type'] !== 'paid') {
    _log('VALIDATION', 'Données invalides', ['data' => $data]);
    http_response_code(400);
    die(json_encode(["error" => "Données invalides"]));
}

// Vérification du produit
$isValidProduct = false;
foreach ($data['items'] ?? [] as $item) {
    if (in_array($item['product_id'], $validProductIds)) {
        $isValidProduct = true;
        break;
    }
}

if (!$isValidProduct) {
    _log('PRODUCT', 'Produit non autorisé', [
        'product_id' => $data['items'][0]['product_id'] ?? 'null',
        'email' => $data['email'] ?? 'null'
    ]);
    http_response_code(400);
    die(json_encode(["error" => "Produit non reconnu"]));
}

// ========== GÉNÉRATION DU CODE ==========
$code = 'SPYRR_' . strtoupper(substr(hash('sha256', uniqid().$data['email'].time()), 0, 8));
$expiryDate = date('Y-m-d', strtotime('+1 year'));

// ========== ENVOI EMAIL VIA EMAILJS ==========
try {
    $emailData = [
        'to_email'    => $data['email'],
        'to_name'     => $data['buyer']['name'] ?? 'Ami(e)',
        'code'        => $code,
        'expiry_date' => $expiryDate,
        'product_name'=> $data['items'][0]['product_name'] ?? 'Miroir de Spyrr'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.emailjs.com/api/v1.0/email/send",
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'service_id' => $emailJSConfig['service_id'],
            'template_id' => $emailJSConfig['template_id'],
            'user_id' => $emailJSConfig['user_id'],
            'template_params' => $emailData
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("EmailJS Error: HTTP $httpCode - $response");
    }

    _log('SUCCESS', 'Code généré et email envoyé', [
        'email' => $data['email'],
        'code' => $code,
        'product' => $data['items'][0]['product_name'] ?? 'inconnu'
    ]);

    http_response_code(200);
    echo json_encode(["status" => "success", "email" => $data['email']]);

} catch (Exception $e) {
    _log('EMAIL_ERROR', $e->getMessage(), [
        'email' => $data['email'],
        'code' => $code
    ]);
    http_response_code(500);
    die(json_encode(["error" => "Erreur d'envoi d'email"]));
}
?>
