<?php
/**
 * VISUALISEUR DE LOGS WEBHOOK - VERSION CORRIGÉE
 * Fichier : voir-logs.php
 */

echo "<h1>📊 Logs Webhook Spyrr</h1>";
echo "<p>🕒 Dernière consultation : " . date('Y-m-d H:i:s') . "</p>";

// Liste COMPLÈTE des fichiers de logs à rechercher
$log_files = [
    'webhook_debug.log',      // LE PRINCIPAL !
    'all_requests.log',
    'premium_codes.log',
    'error_log',
    'logs/error.log', 
    '../logs/error.log'
];

echo "<h2>🔍 Recherche des fichiers de logs...</h2>";

$found_logs = false;

foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        echo "<h3>📁 Fichier trouvé : {$log_file}</h3>";
        $found_logs = true;
        
        $logs = file_get_contents($log_file);
        if ($logs) {
            // Afficher les dernières lignes selon le type de fichier
            $lines = explode("\n", $logs);
            
            // Pour webhook_debug.log et all_requests.log, montrer plus de lignes
            if (strpos($log_file, 'webhook_debug') !== false || strpos($log_file, 'all_requests') !== false) {
                $recent_lines = array_slice($lines, -100); // 100 dernières lignes
                $title_lines = "100 dernières lignes";
            } else {
                $recent_lines = array_slice($lines, -50);  // 50 dernières lignes
                $title_lines = "50 dernières lignes";
            }
            
            echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 500px; overflow-y: auto;'>";
            echo "<pre style='margin: 0; color: #333; font-size: 12px;'>";
            
            foreach ($recent_lines as $line) {
                if (trim($line)) {
                    // Colorer selon le contenu
                    if (strpos($line, '===') !== false) {
                        echo "<strong style='color: #0066cc; background: #e6f3ff; padding: 2px;'>{$line}</strong>\n";
                    } elseif (strpos($line, '✅') !== false) {
                        echo "<span style='color: #22cc22; font-weight: bold;'>{$line}</span>\n";
                    } elseif (strpos($line, '❌') !== false) {
                        echo "<span style='color: #cc2222; font-weight: bold;'>{$line}</span>\n";
                    } elseif (strpos($line, '⚠️') !== false) {
                        echo "<span style='color: #ff8800; font-weight: bold;'>{$line}</span>\n";
                    } elseif (strpos($line, 'ERROR') !== false) {
                        echo "<span style='color: #cc2222; font-weight: bold;'>{$line}</span>\n";
                    } elseif (strpos($line, 'Shopify') !== false) {
                        echo "<span style='color: #006600; font-weight: bold;'>{$line}</span>\n";
                    } elseif (strpos($line, 'POST') !== false || strpos($line, 'GET') !== false) {
                        echo "<span style='color: #0066cc;'>{$line}</span>\n";
                    } else {
                        echo "{$line}\n";
                    }
                }
            }
            
            echo "</pre>";
            echo "</div>";
            echo "<p>📊 Lignes affichées : " . count($recent_lines) . " ({$title_lines})</p>";
            
            // Taille du fichier
            $file_size = filesize($log_file);
            echo "<p>💾 Taille du fichier : " . number_format($file_size) . " bytes</p>";
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
        $color = '';
        if (strpos($file, '.log') !== false) {
            $color = 'style="color: #22cc22; font-weight: bold;"';
        }
        echo "<li {$color}>{$file} ({$size})</li>";
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

// Bouton pour voir webhook_debug.log en priorité
echo "<h3>🎯 LOGS PRIORITAIRES</h3>";
if (file_exists('webhook_debug.log')) {
    echo "<p><strong>🔥 WEBHOOK DEBUG (9KB de logs détaillés) :</strong></p>";
    echo "<p><a href='#' onclick='document.getElementById(\"webhook-debug\").scrollIntoView(); return false;' style='background: #FFD700; color: #223A6D; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold;'>📊 Voir les logs Shopify détaillés</a></p>";
}
?>

<script>
// Auto-scroll vers webhook_debug.log si disponible
window.addEventListener('load', function() {
    const debugSection = document.querySelector('h3:contains("webhook_debug.log")');
    if (debugSection) {
        debugSection.scrollIntoView({behavior: 'smooth'});
        debugSection.style.background = '#FFD700';
        debugSection.style.color = '#223A6D';
        debugSection.style.padding = '10px';
        debugSection.style.borderRadius = '5px';
    }
});
</script>
