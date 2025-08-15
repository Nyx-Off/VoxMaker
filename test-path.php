<?php
// Test des chemins et de la configuration
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test des chemins et configuration</h1>";

// 1. Informations sur ce script
echo "<h2>1. Ce script</h2>";
echo "Fichier : " . __FILE__ . "<br>";
echo "Dossier : " . __DIR__ . "<br>";
echo "Script name : " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI : " . $_SERVER['REQUEST_URI'] . "<br>";

// 2. Vérifier les fichiers PHP
echo "<h2>2. Fichiers PHP dans ce dossier</h2>";
$phpFiles = glob(__DIR__ . '/*.php');
foreach ($phpFiles as $file) {
    echo basename($file) . " - " . filesize($file) . " octets<br>";
}

// 3. Vérifier generate_voice.php
echo "<h2>3. Vérification de generate_voice.php</h2>";
$genFile = __DIR__ . '/generate_voice.php';
if (file_exists($genFile)) {
    echo "✓ generate_voice.php existe<br>";
    echo "Taille : " . filesize($genFile) . " octets<br>";
    echo "Permissions : " . substr(sprintf('%o', fileperms($genFile)), -4) . "<br>";
    
    // Lire les premières lignes
    $lines = file($genFile, FILE_IGNORE_NEW_LINES);
    echo "Premières lignes :<br>";
    echo "<pre>";
    for ($i = 0; $i < min(5, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
} else {
    echo "✗ generate_voice.php INTROUVABLE !<br>";
}

// 4. Test d'appel direct
echo "<h2>4. Test d'appel POST</h2>";
?>

<form method="POST" action="generate_voice.php">
    <input type="hidden" name="text" value="Test">
    <input type="hidden" name="voice" value="fr-FR-VivienneMultilingualNeural">
    <input type="hidden" name="music" value="none">
    <input type="hidden" name="volume" value="30">
    <button type="submit">Test POST direct</button>
</form>

<?php
// 5. Vérifier la configuration
echo "<h2>5. Configuration PHP</h2>";
echo "PHP Version : " . PHP_VERSION . "<br>";
echo "Error reporting : " . error_reporting() . "<br>";
echo "Display errors : " . ini_get('display_errors') . "<br>";
echo "Output buffering : " . ini_get('output_buffering') . "<br>";

// 6. Test include
echo "<h2>6. Test include config.php</h2>";
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    echo "✓ config.php existe<br>";
    
    // Capturer la sortie
    ob_start();
    $result = @include($configFile);
    $output = ob_get_clean();
    
    if ($output) {
        echo "⚠ ATTENTION : config.php génère une sortie :<br>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "✓ config.php ne génère pas de sortie<br>";
    }
    
    // Vérifier les constantes
    if (defined('UPLOAD_PATH')) {
        echo "✓ UPLOAD_PATH défini : " . UPLOAD_PATH . "<br>";
    }
} else {
    echo "✗ config.php introuvable<br>";
}
?>