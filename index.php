<?php
/**
 * PAGE D'ACCUEIL WEBHOOK SPYRR
 * Fichier : index.php
 * Déploiement : render.com via Docker
 */

// Informations système
$server_time = date('Y-m-d H:i:s');
$server_info = $_SERVER['HTTP_HOST'] ?? 'localhost';
$php_version = phpversion();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔗 Webhook Spyrr - Interface de Gestion</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #223A6D 0%, #1a2a4f 100%);
            color: #FFFFFF;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #FFD700;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .status-card h3 {
            color: #FFD700;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .status-value {
            font-weight: bold;
            color: #28a745;
        }
        
        .tools-section {
            margin-bottom: 40px;
        }
        
        .tools-section h2 {
            color: #FFD700;
            margin-bottom: 20px;
            font-size: 1.8rem;
            text-align: center;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .tool-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-color: #FFD700;
        }
        
        .tool-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .tool-card h3 {
            color: #FFD700;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        
        .tool-card p {
            margin-bottom: 20px;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        .btn {
            background: #FFD700;
            color: #223A6D;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #F5C842;
            transform: translateY(-2px);
            color: #223A6D;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
            color: white;
        }
        
        .info-section {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            margin-top: 40px;
        }
        
        .info-section h3 {
            color: #FFD700;
            margin-bottom: 15px;
        }
        
        .info-section ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-section li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .info-section li:before {
            content: "✅";
            position: absolute;
            left: 0;
        }
        
        .webhook-url {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            margin: 15px 0;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .tools-grid {
                grid-template-columns: 1fr;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔗 Webhook Spyrr</h1>
            <p>Interface de gestion pour Le Miroir de Spyrr</p>
            <p><strong>✅ Service opérationnel sur render.com</strong></p>
        </div>

        <!-- Status -->
        <div class="status-grid">
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
                <div class="status-item">
                    <span>Docker :</span>
                    <span class="status-value">Actif</span>
                </div>
            </div>

            <div class="status-card">
                <h3>🛠️ Services Intégrés</h3>
                <div class="status-item">
                    <span>EmailJS :</span>
                    <span class="status-value">Configuré</span>
                </div>
                <div class="status-item">
                    <span>Shopify Webhook :</span>
                    <span class="status-value">En attente</span>
                </div>
                <div class="status-item">
                    <span>GitHub :</span>
                    <span class="status-value">Connecté</span>
                </div>
                <div class="status-item">
                    <span>Codes Premium :</span>
                    <span class="status-value">Actif</span>
                </div>
            </div>
        </div>

        <!-- Tools Section -->
        <div class="tools-section">
            <h2>🧰 Outils Disponibles</h2>
            <div class="tools-grid">
                
                <div class="tool-card">
                    <div class="tool-icon">🧪</div>
                    <h3>Test Webhook</h3>
                    <p>Teste le webhook principal avec génération de code premium et envoi d'email</p>
                    <a href="webhook-shopify-spyrr.php?test=1" class="btn btn-success">Lancer le test</a>
                </div>

                <div class="tool-card">
                    <div class="tool-icon">📊</div>
                    <h3>Voir les Logs</h3>
                    <p>Consultez les logs détaillés des webhooks et des erreurs système</p>
                    <a href="voir-logs.php" class="btn btn-info">Voir les logs</a>
                </div>

                <div class="tool-card">
                    <div class="tool-icon">📦</div>
                    <h3>Test Commande</h3>
                    <p>Simule une commande Shopify complète avec envoi d'email automatique</p>
                    <a href="test-commande-manuelle.php" class="btn">Test complet</a>
                </div>

                <div class="tool-card">
                    <div class="tool-icon">🔑</div>
                    <h3>Générateur de Code</h3>
                    <p>Teste uniquement la génération de codes premium sans email</p>
                    <a href="webhook-shopify-spyrr.php?test=1&code=1" class="btn">Générer code</a>
                </div>

                <div class="tool-card">
                    <div class="tool-icon">📧</div>
                    <h3>Test EmailJS</h3>
                    <p>Vérifie la connexion et l'envoi d'emails via le service EmailJS</p>
                    <a href="webhook-shopify-spyrr.php?test=1&email=1" class="btn">Test email</a>
                </div>

                <div class="tool-card">
                    <div class="tool-icon">🗑️</div>
                    <h3>Nettoyer Logs</h3>
                    <p>Vide tous les fichiers de logs pour un nouveau départ</p>
                    <a href="voir-logs.php?clear=1" class="btn" onclick="return confirm('Vider tous les logs ?')">Nettoyer</a>
                </div>

            </div>
        </div>

        <!-- Webhook Information -->
        <div class="info-section">
            <h3>📡 Configuration Webhook Shopify</h3>
            <p><strong>URL à configurer dans Shopify :</strong></p>
            <div class="webhook-url">
                https://spyrr-webhook.onrender.com/webhook-shopify-spyrr.php
            </div>
            
            <h3 style="margin-top: 25px;">🔧 Fonctionnalités</h3>
            <ul>
                <li>Réception automatique des commandes Shopify</li>
                <li>Génération de codes premium uniques</li>
                <li>Envoi d'emails automatique via EmailJS</li>
                <li>Logs détaillés pour le debug</li>
                <li>Tests complets pour vérification</li>
                <li>Sauvegarde des codes générés</li>
            </ul>

            <h3 style="margin-top: 25px;">📞 Contact</h3>
            <p><strong>Email :</strong> spyrr@proton.me</p>
            <p><strong>Site :</strong> <a href="https://www.spyrr.net" style="color: #FFD700;">www.spyrr.net</a></p>
            <p><strong>Application :</strong> <a href="https://www.spyrr.net/TIRAGE_GRATUIT_3_CARTES.i.htm" style="color: #FFD700;">Le Miroir de Spyrr</a></p>
        </div>

    </div>
</body>
</html>
