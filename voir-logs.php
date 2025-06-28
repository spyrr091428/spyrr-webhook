<?php
/**
 * VISUALISEUR DE LOGS WEBHOOK
 * Fichier : voir-logs.php
 * À uploader sur InfinityFree pour voir les logs du webhook
 */

echo "<h1>📊 Logs Webhook Spyrr</h1>";
echo "<p>🕒 Dernière consultation : " . date('Y-m-d H:i:s') . "</p>";

// Chemin du fichier de log (InfinityFree)
$log_files = [
    'error_log',
    'logs/error.log', 
    '../logs/error.log',
    'premium_codes.log'
];

echo "<h2>🔍 Recherche des fichiers de logs...</h2>";

$found_logs = false;

foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        echo "<h3>📁 Fichier trouvé : {$log_file}</h3>";
        $found_logs = true;
        
        $logs = file_get_contents($log_file);
        if ($logs) {
            // Afficher seulement les dernières lignes (50 dernières)
            $lines = explode("\n", $logs);
            $recent_lines = array_slice($lines, -50);
            
            echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; overflow-y: auto;'>";
            echo "<pre style='margin: 0; color: #333;'>";
            
            foreach ($recent_lines as $line) {
                if (trim($line)) {
                    // Colorer selon le contenu
                    if (strpos($line, '===') !== false) {
                        echo "<strong style='color: #0066cc;'>{$line}</strong>\n";
                    } elseif (strpos($line, '✅') !== false) {
                        echo "<span style='color: #22cc22;'>{$line}</span>\n";
                    } elseif (strpos($line, '❌') !== false) {
                        echo "<span style='color: #cc2222;'>{$line}</span>\n";
                    } elseif (strpos($line, 'ERROR') !== false) {
                        echo "<span style='color: #cc2222; font-weight: bold;'>{$line}</span>\n";
                    } else {
                        echo "{$line}\n";
                    }
                }
            }
            
            echo "</pre>";
            echo "</div>";
            echo "<p>📊 Lignes affichées : " . count($recent_lines) . " (50 dernières)</p>";
        } else {
            echo "<p>📭 Fichier vide</p>";
        }
        
        echo "<hr>";
    }
}

if (!$found_logs) {
    echo "<h3>❌ Aucun fichier de log trouvé</h3>";
    echo "<p>Les logs peuvent être dans :</p>";
    echo "<ul>";
    foreach ($log_files as $file) {
        echo "<li>{$file}</li>";
    }
    echo "</ul>";
    
    echo "<h3>🧪 Forcer un log de test</h3>";
    error_log("=== TEST LOG MANUEL ===");
    error_log("Date: " . date('Y-m-d H:i:s'));
    error_log("Test depuis voir-logs.php");
    echo "<p>✅ Log de test ajouté. Rechargez la page.</p>";
}

echo "<h3>🔗 Actions disponibles</h3>";
echo "<p><a href='?'>🔄 Recharger les logs</a></p>";
echo "<p><a href='webhook-shopify-spyrr.php?test=1'>🧪 Test webhook</a></p>";
echo "<p><a href='test-commande-manuelle.php'>📦 Test commande manuelle</a></p>";

// Informations système
echo "<h3>ℹ️ Informations système</h3>";
echo "<p><strong>Répertoire courant :</strong> " . getcwd() . "</p>";
echo "<p><strong>Fichiers présents :</strong></p>";
echo "<ul>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $size = is_file($file) ? filesize($file) . ' bytes' : 'dossier';
        echo "<li>{$file} ({$size})</li>";
    }
}
echo "</ul>";

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    echo "<h3>🗑️ Nettoyage des logs</h3>";
    foreach ($log_files as $log_file) {
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            echo "<p>✅ {$log_file} vidé</p>";
        }
    }
    echo "<p><a href='?'>Retour aux logs</a></p>";
} else {
    echo "<p><a href='?clear=1' onclick='return confirm(\"Vider tous les logs ?\")'>🗑️ Vider les logs</a></p>";
}
?>
