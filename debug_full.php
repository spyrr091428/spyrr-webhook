<?php
// debug_full.php - A placer à la racine (ex: https://spyrr-webhook.onrender.com/debug_full.php)
// Ce fichier logge TOUTES les requêtes (GET/POST) et affiche les données brutes.

// 1. Logge la requête complète
$logFile = 'full_debug.log';
$logData = [
    'DATE' => date('Y-m-d H:i:s'),
    'METHOD' => $_SERVER['REQUEST_METHOD'],
    'HEADERS' => getallheaders(),
    'POST_DATA' => $_POST,
    'RAW_INPUT' => file_get_contents('php://input'),
    'SERVER_VARS' => [
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    ]
];

file_put_contents($logFile, print_r($logData, true) . PHP_EOL . "==========" . PHP_EOL, FILE_APPEND);

// 2. Affiche un message clair
echo "<h1>Debug Actif</h1>";
echo "<p>Méthode: <strong>" . $_SERVER['REQUEST_METHOD'] . "</strong></p>";
echo "<p>Données brutes reçues:</p><pre>" . htmlspecialchars(file_get_contents('php://input')) . "</pre>";
echo "<p>Log sauvegardé dans <code>full_debug.log</code>.</p>";

// 3. Si c'est un POST, simule le webhook pour voir si ça marche
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>✅ Requête POST détectée !</h2>";
    echo "<p>Le webhook devrait fonctionner. Vérifie <code>premium_codes.log</code>.</p>";
} else {
    echo "<h2>❌ Ce n'est pas un POST !</h2>";
    echo "<p>Payhip n'envoie pas en POST, ou ton hébergement convertit la requête.</p>";
}
?>
