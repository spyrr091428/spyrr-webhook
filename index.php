<?php
/**
 * PAGE D'ACCUEIL WEBHOOK SPYRR - VERSION CORRIGÉE
 * Fichier : index.php
 */

$server_time = date('Y-m-d H:i:s');
$server_info = $_SERVER['HTTP_HOST'] ?? 'localhost';
$php_version = phpversion();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Spyrr - Interface de Gestion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #223A6D;
            color: #FFFFFF;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #FFD700;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .tool-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .tool-card h3 {
            color: #FFD700;
            margin-bottom: 10px;
        }
        .btn {
            background: #FFD700;
            color: #223A6D;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #F5C842;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .status-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .status-card h3 {
            color: #FFD700;
            margin-bottom: 15px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .status-value {
            font-weight: bold;
            color: #28a745;
        }
        .webhook-url {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Webhook Spyrr</h1>
            <p>Interface de gestion pour Le Miroir de Spyrr</p>
            <p><strong>✅ Service opérationnel sur render.com</strong></p>
        </div>

        <div class="status-card">
            <h3>📊 Statut du Serveur</h3>
            <div class="status-item">
                <span>Heure serveur :</span>
                <span class="status-value"><?php echo $server_time; ?></span>
            </div>
            <div class="status-item">
                <span>Host :</span>
                <span class="status-value"><?php echo $server_info; ?></span>
            </div>
            <div class="status-item">
                <span>PHP Version :</span>
                <span class="status-value"><?php echo $php_version; ?></span>
            </div>
        </div>

        <h2 style="color: #FFD700; text-align: center; margin-bottom: 20px;">🧰 Outils Disponibles</h2>
        
        <div class="tools-grid">
            <div class="tool-card">
                <h3>🧪 Test Webhook</h3>
                <p>Teste le webhook principal avec génération de code premium</p>
                <a href="webhook-shopify-spyrr.php?test=1" class="btn btn-success">Lancer le test</a>
            </div>

            <div class="tool-card">
                <h3>📊 Voir les Logs</h3>
                <p>Consultez les logs détaillés des webhooks</p>
                <a href="voir-logs.php" class="btn btn-info">Voir les logs</a>
            </div>

            <div class="tool-card">
                <h3>📦 Test Commande</h3>
                <p>Simule une commande Shopify complète</p>
                <a href="test-commande-manuelle.php" class="btn">Test complet</a>
            </div>

            <div class="tool-card">
                <h3>🔑 Générateur de Code</h3>
                <p>Teste la génération de codes premium</p>
                <a href="webhook-shopify-spyrr.php?test=1&code=1" class="btn">Générer code</a>
            </div>

            <div class="tool-card">
                <h3>📧 Test EmailJS</h3>
                <p>Vérifie l'envoi d'emails via EmailJS</p>
                <a href="webhook-shopify-spyrr.php?test=1&email=1" class="btn">Test email</a>
            </div>

            <div class="tool-card">
                <h3>🗑️ Nettoyer Logs</h3>
                <p>Vide tous les fichiers de logs</p>
                <a href="voir-logs.php?clear=1" class="btn" onclick="return confirm('Vider tous les logs ?')">Nettoyer</a>
            </div>
        </div>

        <div class="status-card">
            <h3>📡 Configuration Webhook Shopify</h3>
            <p><strong>URL à configurer dans Shopify :</strong></p>
            <div class="webhook-url">
                https://spyrr-webhook.onrender.com/webhook-shopify-spyrr.php
            </div>
            
            <h3 style="margin-top: 20px;">🔧 Fonctionnalités</h3>
            <ul>
                <li>✅ Réception automatique des commandes Shopify</li>
                <li>✅ Génération de codes premium uniques</li>
                <li>✅ Envoi d'emails automatique via EmailJS</li>
                <li>✅ Logs détaillés pour le debug</li>
                <li>✅ Tests complets pour vérification</li>
            </ul>

            <h3 style="margin-top: 20px;">📞 Contact</h3>
            <p><strong>Email :</strong> spyrr@proton.me</p>
            <p><strong>Site :</strong> <a href="https://www.spyrr.net" style="color: #FFD700;">www.spyrr.net</a></p>
        </div>
    </div>
</body>
</html>
