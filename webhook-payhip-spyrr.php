<?php
/**
 * WEBHOOK PAYHIP POUR L'ORACLE "LE MIROIR DE SPYRR" - VERSION STABLE
 * Compatible avec les envois POST de Payhip + logs détaillés.
 */

// =============================================
// 1. CONFIGURATION
// =============================================
$valid_product_id = "RDubp";       // ID du produit Payhip
$expected_price = 12.00;           // Prix en euros (évite les codes via promos non autorisées)
$emailjs_service_id = 'service_7bfwpfm';
$emailjs_template_id = 'template_4lesgvh';
$emailjs_user_id = 'RRvc1ifIrhay8-fVV';

// =============================================
// 2. RÉCUPÉRATION DES DONNÉES (Version robuste)
// =============================================
// Log brut pour debug (même si la méthode n'est pas POST)
file_put_contents('webhook_debug.log', "[NEW REQUEST] " . date('Y-m-d H:i:s') . PHP_EOL .
    "Method: " . $_SERVER['REQUEST_METHOD'] . PHP_EOL .
    "Input: " . file_get_contents('php://input') . PHP_EOL . PHP_EOL, FILE_APPEND);

// Vérifie que c'est un POST (mais logge même les GET pour debug)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Méthode non autorisée (seul POST est accepté).");
}

// Récupère et décode les données
$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die("Données JSON invalides.");
}

// =============================================
// 3. VALIDATIONS DE SÉCURITÉ
// =============================================
// Vérifie le produit
if (!isset($data['product_id']) || $data['product_id'] !== $valid_product_id) {
    http_response_code(403);
    die("Produit non autorisé.");
}

// Vérifie le prix
if (!isset($data['total']) || (float)$data['total'] < $expected_price) {
    http_response_code(400);
    die("Montant insuffisant.");
}

// Vérifie le statut de la commande
if (!isset($data['status']) || $data['status'] !== 'completed') {
    http_response_code(400);
    die("Commande non finalisée.");
}

// =============================================
// 4. GÉNÉRATION DU CODE PREMIUM
// =============================================
function generate_premium_code() {
    $year = date('Y');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    return "SPYRR{$year}_{$random}";
}

$premium_code = generate_premium_code();
$expiry_date = date('Y-m-d', strtotime('+12 months'));

// =============================================
// 5. LOG ET EMAIL
// =============================================
// Log le code généré
file_put_contents('premium_codes.log',
    date('Y-m-d H:i:s') . " | " . $data['buyer_email'] . " | " . $premium_code . " | " . $expiry_date . PHP_EOL, FILE_APPEND);

// Prépare les données pour EmailJS
$email_data = [
    'service_id' => $emailjs_service_id,
    'template_id' => $emailjs_template_id,
    'user_id' => $emailjs_user_id,
    'template_params' => [
        'buyer_email' => $data['buyer_email'],
        'premium_code' => $premium_code,
        'expiry_date' => $expiry_date,
        'buyer_name' => $data['buyer_name'] ?? 'Ami(e)',
    ]
];

// Envoie l'email via EmailJS (avec log)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.emailjs.com/api/v1.0/email/send");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// Log la réponse d'EmailJS
file_put_contents('emailjs_debug.log',
    date('Y-m-d H:i:s') . " | Email sent to: " . $data['buyer_email'] .
    " | Response: " . $response . PHP_EOL, FILE_APPEND);

// =============================================
// 6. RÉPONSE FINALE
// =============================================
http_response_code(200);
echo "Code premium généré et envoyé avec succès !";
?>
