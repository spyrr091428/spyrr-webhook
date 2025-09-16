<?php
// webhook-payhip-spyrr.php
// Reçoit les notifications de Payhip et envoie un code premium

// 1. Récupération des données Payhip (format différent de Shopify)
$data = json_decode(file_get_contents('php://input'), true);

// 2. Vérification du produit acheté
if (isset($data['product']) && $data['product']['name'] === "Code Premium – Oracle Le Miroir de Spyrr (12 mois)") {
    $customer_email = $data['buyer']['email'];
    $customer_name = $data['buyer']['first_name'];

    // 3. Génération du code (même fonction que pour Shopify)
    $premium_code = generatePremiumCode();

    // 4. Envoi de l'email (même fonction que pour Shopify)
    sendEmailViaEmailJS($customer_email, $premium_code, $customer_name);

    // 5. Log pour debug
    error_log("Payhip Webhook - Code envoyé à : $customer_email | Code : $premium_code");
} else {
    error_log("Payhip Webhook - Produit non éligible : " . $data['product']['name']);
    http_response_code(400);
    exit('Produit non éligible');
}

// --- Fonctions partagées (à extraire dans un fichier commun si possible) ---
function generatePremiumCode() {
    $chars = 'ABCDEF0123456789';
    $code = 'SPYRR2025_';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function sendEmailViaEmailJS($to_email, $premium_code, $customer_name) {
    // Initialisation d'EmailJS (à adapter selon ta librairie)
    $emailJS = new \EmailJS\EmailJS('RRvc1ifIrhay8-fVV');

    $emailJS->send(
        'service_7bfwpfm',
        'template_4lesgvh',
        [
            'to_email' => $to_email,
            'premium_code' => $premium_code,
            'customer_name' => $customer_name
        ]
    );
}
?>
