<?php
/**
 * TEST COMMANDE MANUELLE - SIMULATION SHOPIFY
 * Fichier : test-commande-manuelle.php
 * À uploader sur InfinityFree pour tester l'envoi d'email
 */

echo "<h1>🧪 Test Commande Manuelle - Simulation Shopify</h1>";

// Simulation d'une commande Shopify
$fake_order = [
    'id' => 'TEST_' . time(),
    'order_number' => 'TEST_001',
    'email' => 'ludovicspyrr@gmail.com',
    'billing_address' => [
        'first_name' => 'Ludovic',
        'last_name' => 'Spyrr'
    ],
    'line_items' => [
        [
            'title' => 'Oracle Le Miroir de Spyrr - Accès Premium',
            'product_id' => 'test_product'
        ]
    ]
];

echo "<h2>📦 Simulation commande :</h2>";
echo "<pre>" . json_encode($fake_order, JSON_PRETTY_PRINT) . "</pre>";

// Configuration EmailJS
$emailjs_service_id = 'service_7bfwpfm';
$emailjs_template_premium = 'template_4lesgvh';
$emailjs_public_key = 'RRvc1ifIrhay8-fVV';

// Génération code premium
function generate_premium_code() {
    $year = date('Y');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    return "SPYRR{$year}_{$random}";
}

// Traitement de la commande simulée
echo "<h2>🔄 Traitement en cours...</h2>";

$email_client = $fake_order['email'];
$nom_client = $fake_order['billing_address']['first_name'] . ' ' . $fake_order['billing_address']['last_name'];
$order_id = $fake_order['id'];
$order_number = $fake_order['order_number'];

// Vérification produit Premium
$has_premium = false;
foreach ($fake_order['line_items'] as $item) {
    if (strpos($item['title'], 'Premium') !== false || 
        strpos($item['title'], 'oracle-le-miroir-de-spyrr-acces-premium') !== false) {
        $has_premium = true;
        echo "<p>✅ Produit Premium détecté : " . $item['title'] . "</p>";
        break;
    }
}

if ($has_premium) {
    // Génération du code
    $code_premium = generate_premium_code();
    echo "<p>🔑 Code généré : <strong>{$code_premium}</strong></p>";
    
    // Sauvegarde (optionnel)
    $data = [
        'code' => $code_premium,
        'email' => $email_client,
        'order_id' => $order_id,
        'date' => date('Y-m-d H:i:s'),
        'type' => 'TEST_MANUAL'
    ];
    file_put_contents('premium_codes.log', json_encode($data) . "\n", FILE_APPEND);
    echo "<p>💾 Code sauvegardé dans premium_codes.log</p>";
    
    // Préparation email
    $emailjs_data = [
        'service_id' => $emailjs_service_id,
        'template_id' => $emailjs_template_premium,
        'user_id' => $emailjs_public_key,
        'template_params' => [
            'to_email' => $email_client,
            'to_name' => $nom_client,
            'premium_code' => $code_premium,
            'order_number' => $order_number,
            'site_url' => 'https://www.spyrr.net/TIRAGE_GRATUIT_3_CARTES.i.htm'
        ]
    ];
    
    echo "<h2>📧 Envoi email en cours...</h2>";
    echo "<p>📨 Destinataire : {$email_client}</p>";
    echo "<p>👤 Nom : {$nom_client}</p>";
    echo "<p>🔑 Code : {$code_premium}</p>";
    
    // Envoi via EmailJS
    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailjs_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: application/json, text/plain, */*',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
        'Origin: https://www.spyrr.net',
        'Referer: https://www.spyrr.net/',
        'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: cross-site'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "<p>📊 Code HTTP : {$http_code}</p>";
    echo "<p>📝 Réponse API : {$result}</p>";
    
    if ($curl_error) {
        echo "<p>❌ Erreur CURL : {$curl_error}</p>";
    }
    
    if ($http_code == 200) {
        echo "<h2>✅ EMAIL ENVOYÉ AVEC SUCCÈS !</h2>";
        echo "<p>🎉 Vérifiez votre boîte Gmail : {$email_client}</p>";
        echo "<p>🔑 Code à tester : <strong>{$code_premium}</strong></p>";
    } else {
        echo "<h2>❌ ERREUR LORS DE L'ENVOI</h2>";
        echo "<p>Code HTTP : {$http_code}</p>";
        echo "<p>Réponse : {$result}</p>";
    }
    
} else {
    echo "<p>❌ Aucun produit Premium détecté dans la commande simulée</p>";
}

echo "<hr>";
echo "<h3>🔗 Actions disponibles :</h3>";
echo "<p><a href='?'>🔄 Relancer le test</a></p>";
echo "<p><a href='webhook-shopify-spyrr.php?test=1'>🧪 Test webhook principal</a></p>";
echo "<p><a href='premium_codes.log'>📋 Voir les codes générés</a></p>";
?>
