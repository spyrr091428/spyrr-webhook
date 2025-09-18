<?php
// ========== 1. VÉRIFICATION DE LA SIGNATURE (POINT 4) ==========
$payhipSignature = $_SERVER['HTTP_X_PAYHIP_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$apiKey = 'TA_CLÉ_API_PAYHIP'; // Remplace par ta clé (Payhip → Settings → Developer)
$expectedSignature = hash('sha256', $payload . $apiKey);

if ($payhipSignature !== $expectedSignature) {
    http_response_code(403);
    die("❌ Signature invalide. Requête non autorisée.");
}

// ========== 2. TRAITEMENT DE LA REQUÊTE (POINT 5) ==========
$data = json_decode($payload, true);
if (!$data || $data['type'] !== 'paid') {
    http_response_code(400);
    die("❌ Événement non valide ou données manquantes.");
}

// Vérifie que c'est bien ton produit
$validProductIds = ['TON_ID_PRODUIT']; // Ex: "2804256" (à trouver dans Payhip)
$isValidProduct = false;
foreach ($data['items'] as $item) {
    if (in_array($item['product_id'], $validProductIds)) {
        $isValidProduct = true;
        break;
    }
}
if (!$isValidProduct) {
    http_response_code(400);
    die("❌ Produit non reconnu.");
}

// Génère un code premium
$code = 'SPYRR_' . strtoupper(substr(md5(uniqid() . $data['email']), 0, 8));

// Stocke le code (fichier ou base de données)
file_put_contents('premium_codes.log', date('Y-m-d H:i:s') . " | Email: " . $data['email'] . " | Code: " . $code . PHP_EOL, FILE_APPEND);

// Envoie l'email (version basique)
$to = $data['email'];
$subject = "🌟 Ton Accès Premium au Miroir de Spyrr";
$message = "Namasté,\n\nTon code premium : **$code**\nLien : https://app.spyrrgames.net?code=$code\n\nVibre libre !";
$headers = "From: ludovicspyrr@gmail.com\r\n";

if (!mail($to, $subject, $message, $headers)) {
    http_response_code(500);
    die("❌ Erreur lors de l'envoi de l'email.");
}

// Répond à Payhip (obligatoire)
http_response_code(200);
echo "✅ Webhook traité. Code envoyé à " . $data['email'];
?>
