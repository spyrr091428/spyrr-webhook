<?php
// ========== CHARGEMENT DES VARIABLES ==========
$env = parse_ini_file('.env');
foreach ($env as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
}

// ========== CONFIGURATION PAYHIP UNIQUEMENT ==========
$config = [
    'payhip' => [
        'api_key' => $_ENV['PAYHIP_API_KEY'] ?? die("❌ Clé Payhip manquante"),
        'valid_product_ids' => ['RDubp'], // <--- À vérifier
        'log_file' => $_ENV['LOG_DIR'] . '/payhip/webhook_' . date('Y-m-d') . '.log'
    ],
    'emailjs' => [
        'service_id' => $_ENV['EMAILJS_SERVICE_ID_PAYHIP'] ?? die("❌ Service ID Payhip manquant"),
        'template_id' => $_ENV['EMAILJS_TEMPLATE_ID_PAYHIP'] ?? die("❌ Template ID Payhip manquant"),
        'user_id' => $_ENV['EMAILJS_USER_ID_PAYHIP'] ?? die("❌ User ID Payhip manquant")
    ]
];

// ========== FONCTIONS ISOLÉES ==========
function logPayhip($type, $message, $data = []) {
    global $config;
    $log = [
        "timestamp" => date('c'),
        "type" => $type,
        "message" => $message,
        "data" => $data,
        "source" => "PAYHIP"
    ];
    file_put_contents($config['payhip']['log_file'], json_encode($log, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
}

// ========== TRAITEMENT DU WEBHOOK ==========
// (Le reste du code reste identique à la version précédente,
//  mais utilise $config['payhip'] et $config['emailjs'])
?>
