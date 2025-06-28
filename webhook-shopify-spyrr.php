<?php
/**
 * WEBHOOK SHOPIFY POUR LE MIROIR DE SPYRR - VERSION SÉCURISÉE
 * Fichier : webhook-shopify-spyrr.php
 * RETOUR À LA BASE STABLE + corrections essentielles seulement
 */

// Configuration
$webhook_secret = '9abe05879f4e2cfad967f7eb901f11f66fb1bc4500490e6064f68ce10d2e0547';
$emailjs_service_id = 'service_7bfwpfm';
$emailjs_template_premium = 'template_4lesgvh';
$emailjs_template_consultation = 'template_consultation';
$emailjs_public_key = 'RRvc1ifIrhay8-fVV';

/**
 * Log personnalisé
 */
function write_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents('webhook_debug.log', "[{$timestamp}] {$message}\n", FILE_APPEND);
    error_log("WEBHOOK SPYRR: " . $message);
}

// Template de test pour debug
if (isset($_GET['test'])) {
    write_log("=== TEST WEBHOOK APPELÉ ===");
    
    echo "<h1>🧪 Test Webhook Spyrr - VERSION SÉCURISÉE</h1>";
    echo "<p>✅ Webhook configuré et opérationnel !</p>";
    echo "<p>📡 URL : " . $_SERVER['REQUEST_URI'] . "</p>";
    echo "<p>🕒 Date : " . date('Y-m-d H:i:s') . "</p>";
    echo "<p>🔧 <strong>VERSION SÉCURISÉE SANS ANTI-DOUBLONS COMPLEXE</strong></p>";
    
    // Test génération code
    if (isset($_GET['code'])) {
        $test_code = generate_premium_code();
        echo "<p>🔑 Code test généré : <strong>{$test_code}</strong></p>";
    }
    
    // Test EmailJS
    if (isset($_GET['email'])) {
        echo "<p>📧 Test EmailJS en cours...</p>";
        try {
            $result = test_emailjs();
            if ($result !== false) {
                echo "<p><strong>✅ Test EmailJS : SUCCESS</strong></p>";
            }
        } catch (Exception $e) {
            echo "<p><strong>❌ Test EmailJS : ERREUR</strong> - " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<p>🔗 <a href='voir-logs.php'>📊 Voir logs détaillés</a></p>";
    
    exit;
}

write_log("=== NOUVELLE REQUÊTE WEBHOOK ===");
write_log("Date: " . date('Y-m-d H:i:s'));
write_log("Méthode: " . $_SERVER['REQUEST_METHOD']);
write_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Non défini'));

try {
    // Récupération des données
    $data = file_get_contents('php://input');
    write_log("Taille des données: " . strlen($data) . " bytes");
    
    $webhook_signature = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? '';
    write_log("Signature présente: " . ($webhook_signature ? 'OUI' : 'NON'));
    
    // Vérification sécurité Shopify UNIQUEMENT pour les vraies requêtes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($webhook_secret) && $webhook_secret !== 'VOTRE_SHOPIFY_WEBHOOK_SECRET') {
        if (!empty($webhook_signature)) {
            $calculated_hmac = base64_encode(hash_hmac('sha256', $data, $webhook_secret, true));
            if (!hash_equals($webhook_signature, $calculated_hmac)) {
                http_response_code(401);
                write_log("Webhook non autorisé - Signature invalide");
                exit('Unauthorized - Invalid signature');
            }
        }
    }
    
    // Si pas de données POST, c'est probablement un test
    if (empty($data)) {
        write_log("Pas de données POST - Test direct");
        echo "Webhook Spyrr opérationnel - En attente de données Shopify";
        exit;
    }
    
    // Parsing commande
    $order = json_decode($data, true);
    if (!$order) {
        throw new Exception("Impossible de parser la commande");
    }
    
    // Informations client
    $email_client = $order['email'] ?? '';
    $nom_client = ($order['billing_address']['first_name'] ?? '') . ' ' . ($order['billing_address']['last_name'] ?? '');
    $order_id = $order['id'] ?? '';
    $order_number = $order['order_number'] ?? '';
    
    write_log("=== COMMANDE ANALYSÉE ===");
    write_log("Email: {$email_client}");
    write_log("Nom: {$nom_client}");
    write_log("Order ID: {$order_id}");
    write_log("Order Number: {$order_number}");
    
    // ANTI-DOUBLONS SIMPLE (pas de fichier complexe)
    $unique_key = md5($order_id . $email_client . date('Y-m-d H:i'));
    $processed_key_file = 'last_processed.txt';
    
    if (file_exists($processed_key_file)) {
        $last_key = trim(file_get_contents($processed_key_file));
        if ($last_key === $unique_key) {
            write_log("DOUBLON DÉTECTÉ - Commande déjà traitée");
            echo "OK - Already processed";
            exit;
        }
    }
    file_put_contents($processed_key_file, $unique_key);
    
    // Vérification des produits achetés
    $has_premium = false;
    $has_consultation = false;
    
    write_log("=== ANALYSE PRODUITS ===");
    if (isset($order['line_items']) && is_array($order['line_items'])) {
        write_log("Nombre de produits: " . count($order['line_items']));
        
        foreach ($order['line_items'] as $index => $item) {
            $product_title = $item['title'] ?? '';
            $product_id = $item['product_id'] ?? '';
            
            write_log("Produit {$index}: {$product_title} (ID: {$product_id})");
            
            // Détection produit Premium - MÉTHODES MULTIPLES SÉCURISÉES
            if ($product_id == '15073025196380') {
                // Détection par ID exact - PRIORITÉ 1
                $has_premium = true;
                write_log("✅ PREMIUM DÉTECTÉ par ID exact: " . $product_title);
            } elseif (stripos($product_title, 'oracle') !== false && 
                      stripos($product_title, 'spyrr') !== false && 
                      stripos($product_title, 'premium') !== false) {
                // Détection par titre complet - PRIORITÉ 2
                $has_premium = true;
                write_log("✅ PREMIUM DÉTECTÉ par titre: " . $product_title);
            } elseif (stripos($product_title, 'premium') !== false || 
                      stripos($product_title, 'accès') !== false || 
                      stripos($product_title, 'acces') !== false) {
                // Détection large - PRIORITÉ 3
                $has_premium = true;
                write_log("✅ PREMIUM DÉTECTÉ par mots-clés: " . $product_title);
            }
            
            // Détection consultation privée
            if (stripos($product_title, 'consultation') !== false) {
                $has_consultation = true;
                write_log("✅ CONSULTATION DÉTECTÉE : " . $product_title);
            }
        }
    }
    
    write_log("Premium détecté: " . ($has_premium ? 'OUI' : 'NON'));
    write_log("Consultation détectée: " . ($has_consultation ? 'OUI' : 'NON'));
    
    // Traitement Accès Premium
    if ($has_premium) {
        write_log("=== TRAITEMENT PREMIUM ===");
        $code_premium = generate_premium_code();
        write_log("Code généré: {$code_premium}");
        
        // Sauvegarde du code
        save_premium_code($code_premium, $email_client, $order_id);
        
        // Envoi email code premium
        send_premium_email($email_client, $nom_client, $code_premium, $order_number);
        
        write_log("✅ EMAIL PREMIUM ENVOYÉ : {$code_premium} à {$email_client}");
    }
    
    // Traitement Consultation
    if ($has_consultation) {
        write_log("=== TRAITEMENT CONSULTATION ===");
        send_consultation_email($email_client, $nom_client, $order_number);
        write_log("✅ EMAIL CONSULTATION ENVOYÉ à {$email_client}");
    }
    
    // Réponse succès
    http_response_code(200);
    echo "OK - Commande traitée";
    
} catch (Exception $e) {
    write_log("Erreur webhook : " . $e->getMessage());
    http_response_code(500);
    echo "Erreur : " . $e->getMessage();
}

/**
 * Génération code premium unique
 */
function generate_premium_code() {
    $year = date('Y');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    return "SPYRR{$year}_{$random}";
}

/**
 * Test EmailJS
 */
function test_emailjs() {
    $test_data = [
        'service_id' => 'service_7bfwpfm',
        'template_id' => 'template_4lesgvh',
        'user_id' => 'RRvc1ifIrhay8-fVV',
        'template_params' => [
            'to_email' => 'ludovicspyrr@gmail.com',
            'to_name' => 'Test User',
            'premium_code' => 'SPYRR2025_TEST123',
            'order_number' => 'TEST_ORDER',
            'site_url' => 'https://www.spyrr.net/TIRAGE_GRATUIT_3_CARTES.i.htm'
        ]
    ];
    
    return send_emailjs($test_data, true);
}

/**
 * Sauvegarde code en base
 */
function save_premium_code($code, $email, $order_id) {
    $data = [
        'code' => $code,
        'email' => $email,
        'order_id' => $order_id,
        'date' => date('Y-m-d H:i:s')
    ];
    file_put_contents('premium_codes.log', json_encode($data) . "\n", FILE_APPEND);
}

/**
 * Envoi email code premium via EmailJS
 */
function send_premium_email($email, $nom, $code, $order_number) {
    global $emailjs_service_id, $emailjs_template_premium, $emailjs_public_key;
    
    $emailjs_data = [
        'service_id' => $emailjs_service_id,
        'template_id' => $emailjs_template_premium,
        'user_id' => $emailjs_public_key,
        'template_params' => [
            'to_email' => $email,
            'to_name' => $nom,
            'premium_code' => $code,
            'order_number' => $order_number,
            'site_url' => 'https://www.spyrr.net/TIRAGE_GRATUIT_3_CARTES.i.htm',
            'instructions' => "1. Cliquez sur 'Inscription'\n2. Saisissez votre code : {$code}\n3. Utilisez votre email : {$email}\n4. Choisissez un mot de passe\n5. Connectez-vous et profitez des 5 tirages premium !"
        ]
    ];
    
    send_emailjs($emailjs_data);
}

/**
 * Envoi email consultation via EmailJS
 */
function send_consultation_email($email, $nom, $order_number) {
    global $emailjs_service_id, $emailjs_public_key;
    
    $emailjs_data = [
        'service_id' => $emailjs_service_id,
        'template_id' => 'template_consultation',
        'user_id' => $emailjs_public_key,
        'template_params' => [
            'to_email' => $email,
            'to_name' => $nom,
            'order_number' => $order_number,
            'contact_email' => 'spyrr@proton.me',
            'contact_url' => 'https://www.spyrr.net/CONTACT.K.htm'
        ]
    ];
    
    send_emailjs($emailjs_data);
}

/**
 * Fonction commune envoi EmailJS
 */
function send_emailjs($data, $test_mode = false) {
    $headers = [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: application/json, text/plain, */*',
        'Origin: https://www.spyrr.net',
        'Referer: https://www.spyrr.net/TIRAGE_GRATUIT_3_CARTES.i.htm'
    ];
    
    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    write_log("EmailJS Response - HTTP: {$http_code}");
    if ($curl_error) {
        write_log("EmailJS CURL Error: {$curl_error}");
    }
    
    if ($http_code !== 200) {
        $error_msg = "Erreur envoi email : HTTP {$http_code} - {$result}";
        write_log("❌ ÉCHEC EMAILJS: {$error_msg}");
        if (!$test_mode) {
            throw new Exception($error_msg);
        } else {
            return false;
        }
    }
    
    write_log("✅ EmailJS SUCCESS");
    return $result;
}
?>
