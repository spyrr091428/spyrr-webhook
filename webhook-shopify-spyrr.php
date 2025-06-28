<?php
/**
 * WEBHOOK SHOPIFY POUR LE MIROIR DE SPYRR - VERSION DEBUG AMÉLIORÉE
 * Fichier à uploader : webhook-shopify-spyrr.php sur Render.com
 */

// Configuration
$webhook_secret = '9abe05879f4e2cfad967f7eb901f11f66fb1bc4500490e6064f68ce10d2e0547';
$emailjs_service_id = 'service_7bfwpfm';
$emailjs_template_premium = 'template_4lesgvh';
$emailjs_template_consultation = 'template_consultation';
$emailjs_public_key = 'RRvc1ifIrhay8-fVV';

/**
 * Log personnalisé AMÉLIORÉ
 */
function write_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    
    // Log dans plusieurs fichiers
    file_put_contents('webhook_debug.log', $log_entry, FILE_APPEND);
    file_put_contents('all_requests.log', $log_entry, FILE_APPEND);
    
    // Log PHP standard aussi
    error_log("WEBHOOK SPYRR: " . $message);
}

// CAPTURE ABSOLUMENT TOUT Dès le début
write_log("=== NOUVELLE REQUÊTE WEBHOOK ===");
write_log("Date: " . date('Y-m-d H:i:s'));
write_log("Méthode: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
write_log("URL: " . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN'));
write_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'));
write_log("Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'));

// Log TOUS les headers reçus
write_log("=== HEADERS REÇUS ===");
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        write_log("Header {$key}: {$value}");
    }
}

// Template de test pour debug
if (isset($_GET['test'])) {
    write_log("=== TEST WEBHOOK APPELÉ ===");
    write_log("Paramètres: " . ($_SERVER['QUERY_STRING'] ?? 'AUCUN'));
    
    echo "<h1>🧪 Test Webhook Spyrr - VERSION DEBUG</h1>";
    echo "<p>✅ Webhook configuré et opérationnel !</p>";
    echo "<p>📡 URL : " . ($_SERVER['REQUEST_URI'] ?? '') . "</p>";
    echo "<p>🕒 Date : " . date('Y-m-d H:i:s') . "</p>";
    echo "<p>🌐 Server : " . ($_SERVER['HTTP_HOST'] ?? '') . "</p>";
    echo "<p>🔧 <strong>VERSION AVEC LOGS HYPER DÉTAILLÉS</strong></p>";
    
    // Test génération code
    if (isset($_GET['code'])) {
        $test_code = generate_premium_code();
        write_log("Code test généré: {$test_code}");
        echo "<p>🔑 Code test généré : <strong>{$test_code}</strong></p>";
    }
    
    // Test EmailJS
    if (isset($_GET['email'])) {
        echo "<p>📧 Test EmailJS en cours...</p>";
        write_log("=== DÉBUT TEST EMAILJS ===");
        try {
            $result = test_emailjs();
            if ($result !== false) {
                echo "<p><strong>✅ Test EmailJS : SUCCESS</strong></p>";
                write_log("✅ Test EmailJS réussi");
            }
        } catch (Exception $e) {
            echo "<p><strong>❌ Test EmailJS : ERREUR</strong> - " . $e->getMessage() . "</p>";
            write_log("❌ Test EmailJS échoué: " . $e->getMessage());
        }
    }
    
    echo "<hr>";
    echo "<p>🔗 Tests disponibles :</p>";
    echo "<ul>";
    echo "<li><a href='?test=1&code=1'>Test génération code</a></li>";
    echo "<li><a href='?test=1&email=1'>Test EmailJS</a></li>";
    echo "<li><a href='voir-logs.php'>📊 Voir logs détaillés</a></li>";
    echo "</ul>";
    
    write_log("=== FIN TEST WEBHOOK ===");
    exit;
}

try {
    // Log détaillé du type de requête
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        write_log("✅ REQUÊTE POST DÉTECTÉE - C'est probablement Shopify !");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        write_log("ℹ️ REQUÊTE GET - Probablement un test navigateur");
    } else {
        write_log("⚠️ MÉTHODE INCONNUE: " . $_SERVER['REQUEST_METHOD']);
    }

    // Récupération des données avec capture d'erreur
    $data = file_get_contents('php://input');
    write_log("=== DONNÉES REÇUES ===");
    write_log("Taille des données: " . strlen($data) . " bytes");
    
    if (empty($data)) {
        write_log("⚠️ AUCUNE DONNÉE REÇUE - Peut-être un test de ping Shopify");
        write_log("Headers spéciaux: X-Shopify-Topic=" . ($_SERVER['HTTP_X_SHOPIFY_TOPIC'] ?? 'NON'));
        write_log("Headers spéciaux: X-Shopify-Shop-Domain=" . ($_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'] ?? 'NON'));
        
        // Répondre OK même sans données pour les tests Shopify
        http_response_code(200);
        echo "OK - Webhook test reçu - Pas de données à traiter";
        write_log("✅ Réponse OK envoyée pour test sans données");
        exit;
    }
    
    // Log des premières données pour debug
    write_log("Début des données: " . substr($data, 0, 200) . "...");
    
    // Vérification signature Shopify
    $webhook_signature = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? '';
    write_log("Signature Shopify présente: " . ($webhook_signature ? 'OUI' : 'NON'));
    
    if ($webhook_signature) {
        write_log("Signature reçue: " . substr($webhook_signature, 0, 20) . "...");
    }
    
    // Vérification sécurité UNIQUEMENT pour les vraies requêtes avec signature
    if (!empty($webhook_signature) && !empty($webhook_secret) && $webhook_secret !== 'VOTRE_SHOPIFY_WEBHOOK_SECRET') {
        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, $webhook_secret, true));
        write_log("Signature calculée: " . substr($calculated_hmac, 0, 20) . "...");
        
        if (!hash_equals($webhook_signature, $calculated_hmac)) {
            http_response_code(401);
            write_log("❌ SIGNATURE INVALIDE - Webhook non autorisé");
            exit('Unauthorized - Invalid signature');
        } else {
            write_log("✅ SIGNATURE VALIDE - Webhook Shopify authentifié");
        }
    } else {
        write_log("ℹ️ Pas de vérification de signature (test ou signature manquante)");
    }
    
    // Parsing commande
    $order = json_decode($data, true);
    if (!$order) {
        write_log("❌ ERREUR: Impossible de parser JSON");
        write_log("Données brutes: " . $data);
        throw new Exception("Impossible de parser la commande JSON");
    }
    
    write_log("✅ JSON parsé avec succès");
    
    // Informations client avec protection contre les valeurs manquantes
    $email_client = $order['email'] ?? 'EMAIL_MANQUANT';
    $billing_address = $order['billing_address'] ?? [];
    $nom_client = ($billing_address['first_name'] ?? '') . ' ' . ($billing_address['last_name'] ?? '');
    $order_id = $order['id'] ?? 'ID_MANQUANT';
    $order_number = $order['order_number'] ?? 'NUMBER_MANQUANT';
    
    write_log("=== COMMANDE ANALYSÉE ===");
    write_log("Email: {$email_client}");
    write_log("Nom: {$nom_client}");
    write_log("Order ID: {$order_id}");
    write_log("Order Number: {$order_number}");
    
    // Vérification des produits achetés
    $has_premium = false;
    $has_consultation = false;
    
    write_log("=== ANALYSE PRODUITS ===");
    if (isset($order['line_items']) && is_array($order['line_items'])) {
        write_log("Nombre de produits: " . count($order['line_items']));
        
        foreach ($order['line_items'] as $index => $item) {
            $product_title = $item['title'] ?? 'TITRE_MANQUANT';
            $product_handle = $item['variant_title'] ?? '';
            
            write_log("Produit {$index}: {$product_title}");
            
            // Détection produit Premium - OPTIMISÉE POUR VOTRE PRODUIT EXACT
            $product_lower = strtolower($product_title);
            $product_id = $item['product_id'] ?? '';
            
            // LOG de debug détaillé
            write_log("=== ANALYSE PRODUIT ===");
            write_log("Titre original: '{$product_title}'");
            write_log("Titre lowercase: '{$product_lower}'");
            write_log("Product ID: '{$product_id}'");
            
            // Méthodes de détection premium (ordre de priorité)
            $is_premium = false;
            $detection_method = '';
            
            // 1. Détection par ID exact (le plus précis)
            if ($product_id == '15073025196380') {
                $is_premium = true;
                $detection_method = 'ID exact';
            }
            // 2. Détection par titre exact
            elseif (strpos($product_lower, 'oracle') !== false && 
                    strpos($product_lower, 'miroir de spyrr') !== false && 
                    strpos($product_lower, 'acces premium') !== false) {
                $is_premium = true;
                $detection_method = 'Titre exact complet';
            }
            // 3. Détection par mots-clés critiques
            elseif (strpos($product_lower, 'oracle') !== false && strpos($product_lower, 'spyrr') !== false) {
                $is_premium = true;
                $detection_method = 'Oracle + Spyrr';
            }
            // 4. Détection large pour autres produits premium
            elseif (strpos($product_lower, 'premium') !== false ||
                    strpos($product_lower, 'accès') !== false ||
                    strpos($product_lower, 'acces') !== false) {
                $is_premium = true;
                $detection_method = 'Mots-clés premium';
            }
            
            // LOG du résultat
            write_log("RÉSULTAT: Premium = " . ($is_premium ? 'OUI' : 'NON') . " (Méthode: {$detection_method})");
            
            if ($is_premium) {
                $has_premium = true;
                write_log("✅ PRODUIT PREMIUM DÉTECTÉ : " . $product_title . " (via {$detection_method})");
            } else {
                write_log("❌ Produit non premium : " . $product_title);
            }
            
            // Détection consultation privée
            if (strpos(strtolower($product_title), 'consultation') !== false) {
                $has_consultation = true;
                write_log("✅ CONSULTATION DÉTECTÉE : " . $product_title);
            }
        }
    } else {
        write_log("❌ Aucun line_items trouvé dans la commande");
        write_log("Structure commande: " . print_r(array_keys($order), true));
    }
    
    write_log("=== RÉSULTAT DÉTECTION ===");
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
        write_log("Envoi email à: {$email_client}");
        send_premium_email($email_client, $nom_client, $code_premium, $order_number);
        
        write_log("✅ EMAIL PREMIUM ENVOYÉ : {$code_premium} à {$email_client}");
    } else {
        write_log("❌ Aucun produit premium détecté - pas d'email envoyé");
    }
    
    // Traitement Consultation
    if ($has_consultation) {
        write_log("=== TRAITEMENT CONSULTATION ===");
        send_consultation_email($email_client, $nom_client, $order_number);
        write_log("✅ EMAIL CONSULTATION ENVOYÉ à {$email_client}");
    }
    
    // Réponse succès
    http_response_code(200);
    echo "OK - Commande traitée avec succès";
    write_log("✅ TRAITEMENT TERMINÉ - Réponse OK envoyée");
    
} catch (Exception $e) {
    write_log("❌ ERREUR CRITIQUE : " . $e->getMessage());
    write_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo "Erreur : " . $e->getMessage();
}

write_log("=== FIN TRAITEMENT WEBHOOK ===\n");

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
    write_log("Code sauvegardé: " . json_encode($data));
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
    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    write_log("EmailJS Response - HTTP: {$http_code}, Result: " . substr($result, 0, 100));
    if ($curl_error) {
        write_log("EmailJS CURL Error: {$curl_error}");
    }
    
    if ($http_code !== 200) {
        $error_msg = "Erreur envoi email : HTTP {$http_code} - {$result}";
        if ($curl_error) {
            $error_msg .= " - CURL: {$curl_error}";
        }
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
