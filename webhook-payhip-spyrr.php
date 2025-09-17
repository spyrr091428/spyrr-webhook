<?php
/**
 * WEBHOOK PAYHIP POUR L'ORACLE "LE MIROIR DE SPYRR"
 * Gère les achats du produit "Accès Premium" et envoie un code par email.
 * Ludovic Spyrr - 2024
 */

// =============================================
// 1. CONFIGURATION (À PERSONNALISER)
// =============================================
$valid_product_id = "RDubp"; // ID de ton produit Payhip (à confirmer)
$expected_price = 12.00;    // Prix en euros (pour éviter les codes via des promos non autorisées)
$emailjs_service_id = 'service_7bfwpfm'; // Ton Service ID EmailJS
$emailjs_template_id = 'template_4lesgvh'; // Ton Template ID pour les codes premium
$emailjs_user_id = 'RRvc1ifIrhay8-fVV';    // Ta clé publique EmailJS

// =============================================
// 2. RÉCUPÉRATION ET VÉRIFICATION DES DONNÉES
// =============================================
// Récupère le payload du webhook
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents('webhook_debug.log', print_r($data, true) . PHP_EOL, FILE_APPEND); // Log brut pour debug

// Vérifie que c'est une requête POST valide
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Méthode non autorisée.");
}

// Vérifie la présence des données minimales
if (!isset($data['product_id']) || !isset($data['buyer_email']) || !isset($data['total'])) {
    http_response_code(400);
    die("Données manquantes.");
}

// =============================================
// 3. FILTRES DE SÉCURITÉ
// =============================================
// Vérifie que c'est le bon produit
if ($data['product_id'] !== $valid_product_id) {
    error_log("⚠️ Produit non éligible. ID reçu: " . $data['product_id']);
    http_response_code(403);
    die("Produit non reconnu.");
}

// Vérifie que le paiement est complet
if ($data['status'] !== 'completed') {
    error_log("⚠️ Paiement non finalisé. Statut: " . $data['status']);
    http_response_code(400);
    die("Paiement non validé.");
}

// Vérifie le montant (évite les codes générés via des promos à 1€)
if ((float)$data['total'] < $expected_price) {
    error_log("⚠️ Montant insuffisant. Reçu: " . $data['total'] . "€");
    http_response_code(400);
    die("Montant insuffisant pour un accès premium.");
}

// =============================================
// 4. GÉNÉRATION DU CODE PREMIUM
// =============================================
function generate_premium_code($email) {
    $year = date('Y');
    $random = strtoupper(substr(md5(uniqid($email, true)), 0, 8));
    return "SPYRR{$year}_{$random}";
}

$email = $data['buyer_email'];
$code = generate_premium_code($email);
$expiry_date = date('Y-m-d', strtotime('+12 months'));

// =============================================
// 5. ENREGISTREMENT DANS LES LOGS
// =============================================
$log_line = sprintf(
    "%s | %s | Code: %s | Expire: %s | Produit: %s | Montant: %s€",
    date('Y-m-d H:i:s'),
    $email,
    $code,
    $expiry_date,
    $data['product_id'],
    $data['total']
);
file_put_contents('premium_codes.log', $log_line . PHP_EOL, FILE_APPEND);

// =============================================
// 6. ENVOI DE L'EMAIL VIA EMAILJS
// =============================================
function send_premium_code_email($email, $code, $expiry_date) {
    global $emailjs_service_id, $emailjs_template_id, $emailjs_user_id;

    $template_params = [
        'user_email' => $email,
        'premium_code' => $code,
        'expiry_date' => $expiry_date,
        'oracle_name' => "Le Miroir de Spyrr"
    ];

    $url = "https://api.emailjs.com/api/v1.0/email/send";
    $headers = [
        'Content-Type: application/json',
        'Origin: http://spyrrgames.net'
    ];
    $payload = json_encode([
        'service_id' => $emailjs_service_id,
        'template_id' => $emailjs_template_id,
        'user_id' => $emailjs_user_id,
        'template_params' => $template_params
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    curl_close($ch);

    file_put_contents('emailjs_debug.log', "Email envoyé à $email. Réponse: $response" . PHP_EOL, FILE_APPEND);
    return $response;
}

// Envoie l'email
$email_response = send_premium_code_email($email, $code, $expiry_date);

// =============================================
// 7. RÉPONSE FINALE AU WEBHOOK
// =============================================
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Code premium généré et envoyé.',
    'email' => $email,
    'code' => $code,
    'emailjs_response' => $email_response
]);
?>
