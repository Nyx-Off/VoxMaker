<?php
// Script de test pour edge-tts
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Edge-TTS</h1>";

// 1. Vérifier si exec est disponible
echo "<h2>1. Vérification de exec()</h2>";
if (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
    echo "<p style='color: green;'>✓ exec() est disponible</p>";
} else {
    echo "<p style='color: red;'>✗ exec() n'est pas disponible</p>";
    exit;
}

// 2. Tester which edge-tts
echo "<h2>2. Recherche de edge-tts</h2>";
$paths = [];
exec('which edge-tts 2>&1', $paths, $returnCode);
if ($returnCode === 0 && !empty($paths)) {
    echo "<p style='color: green;'>✓ edge-tts trouvé : " . $paths[0] . "</p>";
} else {
    echo "<p style='color: orange;'>⚠ edge-tts non trouvé avec 'which'</p>";
}

// 3. Tester différentes commandes
echo "<h2>3. Test des commandes</h2>";
$commands = [
    'edge-tts --version',
    'python3 -m edge_tts --version',
    'python -m edge_tts --version',
    '/usr/local/bin/edge-tts --version',
    '$HOME/.local/bin/edge-tts --version',
    '~/.local/bin/edge-tts --version'
];

foreach ($commands as $cmd) {
    echo "<h3>Test : $cmd</h3>";
    $output = [];
    $returnCode = 0;
    exec($cmd . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "<p style='color: green;'>✓ Succès</p>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
        break;
    } else {
        echo "<p style='color: red;'>✗ Échec (code: $returnCode)</p>";
        if (!empty($output)) {
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    }
}

// 4. Information sur Python
echo "<h2>4. Information Python</h2>";
$pythonInfo = [];
exec('python3 --version 2>&1', $pythonInfo);
echo "<p>Python3 : " . implode(" ", $pythonInfo) . "</p>";

exec('pip3 show edge-tts 2>&1', $pipInfo);
if (!empty($pipInfo)) {
    echo "<h3>Info pip3 sur edge-tts :</h3>";
    echo "<pre>" . implode("\n", $pipInfo) . "</pre>";
}

// 5. Variables d'environnement
echo "<h2>5. Variables d'environnement</h2>";
echo "<p>PATH : " . getenv('PATH') . "</p>";
echo "<p>HOME : " . getenv('HOME') . "</p>";
echo "<p>USER : " . getenv('USER') . "</p>";

// 6. Test de génération
echo "<h2>6. Test de génération audio</h2>";
$testText = "Ceci est un test";
$outputFile = "test_" . uniqid() . ".mp3";

// Essayer de générer
$testCommands = [
    "edge-tts --voice 'fr-FR-VivienneMultilingualNeural' --text '$testText' --write-media '$outputFile'",
    "python3 -m edge_tts --voice 'fr-FR-VivienneMultilingualNeural' --text '$testText' --write-media '$outputFile'"
];

$success = false;
foreach ($testCommands as $cmd) {
    echo "<p>Essai : $cmd</p>";
    $output = [];
    exec($cmd . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputFile)) {
        echo "<p style='color: green;'>✓ Génération réussie !</p>";
        echo "<audio controls src='$outputFile'></audio>";
        $success = true;
        // Nettoyer après 5 secondes
        register_shutdown_function(function() use ($outputFile) {
            @unlink($outputFile);
        });
        break;
    } else {
        echo "<p style='color: red;'>✗ Échec</p>";
        if (!empty($output)) {
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    }
}

if (!$success) {
    echo "<h2 style='color: red;'>Problème détecté</h2>";
    echo "<p>Edge-TTS ne semble pas accessible depuis PHP. Solutions possibles :</p>";
    echo "<ol>";
    echo "<li>Vérifier que edge-tts est installé globalement : <code>sudo pip3 install edge-tts</code></li>";
    echo "<li>Ajouter le chemin de edge-tts au PATH du serveur web</li>";
    echo "<li>Utiliser le chemin complet vers edge-tts dans le code PHP</li>";
    echo "<li>Vérifier les permissions du serveur web</li>";
    echo "</ol>";
}
?>