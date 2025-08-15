<?php
// Script d'installation automatique
set_time_limit(300); // 5 minutes max

header('Content-Type: text/html; charset=utf-8');

// Vérifier si les fonctions exec sont disponibles
$exec_available = false;
if (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
    $exec_available = true;
}

// Fonction pour exécuter une commande et afficher le résultat
function runCommand($command, $description) {
    global $exec_available;
    
    echo "<div class='command-block'>";
    echo "<h3>$description</h3>";
    
    if (!$exec_available) {
        echo "<div class='error'>✗ La fonction exec() est désactivée sur ce serveur</div>";
        echo "<p>Vous devez installer manuellement sur votre ordinateur local.</p>";
        echo "</div>";
        return false;
    }
    
    echo "<pre>Commande: $command</pre>";
    
    $output = [];
    $returnCode = 0;
    @exec($command . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "<div class='success'>✓ Succès</div>";
    } else {
        echo "<div class='error'>✗ Erreur (code: $returnCode)</div>";
    }
    
    if (!empty($output)) {
        echo "<pre class='output'>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
    }
    echo "</div>";
    
    return $returnCode === 0;
}

// Fonction pour vérifier si une commande existe
function commandExists($command) {
    global $exec_available;
    
    if (!$exec_available) {
        return false;
    }
    
    $output = [];
    $returnCode = 0;
    @exec("which $command 2>&1", $output, $returnCode);
    return $returnCode === 0;
}

// Fonction alternative pour créer les dossiers
function createRequiredDirectories() {
    $dirs = [
        'uploads' => 'Dossier pour les fichiers générés',
        'uploads/temp' => 'Dossier temporaire',
        'music' => 'Dossier pour les musiques de fond'
    ];
    
    echo "<div class='command-block'>";
    echo "<h3>Création des dossiers</h3>";
    
    $success = true;
    foreach ($dirs as $dir => $description) {
        if (!file_exists($dir)) {
            if (@mkdir($dir, 0777, true)) {
                echo "<div class='success'>✓ Dossier '$dir' créé - $description</div>";
            } else {
                echo "<div class='error'>✗ Impossible de créer '$dir'</div>";
                echo "<p>Créez manuellement le dossier : <code>$dir</code></p>";
                $success = false;
            }
        } else {
            echo "<div class='success'>✓ Dossier '$dir' existe déjà</div>";
        }
        
        // Vérifier les permissions
        if (file_exists($dir) && !is_writable($dir)) {
            echo "<div class='warning'>⚠ Le dossier '$dir' n'est pas accessible en écriture</div>";
            echo "<p>Changez les permissions : <code>chmod 777 $dir</code></p>";
        }
    }
    echo "</div>";
    
    return $success;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation - Générateur de Répondeur</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
            line-height: 1.6;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .command-block {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .command-block h3 {
            margin-top: 0;
            color: #667eea;
        }
        pre {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            font-size: 13px;
            border: 1px solid #e9ecef;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
            margin: 10px 0;
        }
        .error {
            color: #f44336;
            font-weight: bold;
            margin: 10px 0;
        }
        .warning {
            color: #ff9800;
            font-weight: bold;
            margin: 10px 0;
        }
        .output {
            max-height: 200px;
            overflow-y: auto;
            font-size: 12px;
            background: #f1f3f4;
        }
        .status-summary {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .manual-install {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .step-list {
            counter-reset: step-counter;
            list-style: none;
            padding: 0;
        }
        .step-list li {
            counter-increment: step-counter;
            margin-bottom: 15px;
            padding-left: 40px;
            position: relative;
        }
        .step-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>🎙️ Installation du Générateur de Répondeur</h1>
    
    <?php if (!$exec_available): ?>
    <div class="alert alert-warning">
        <strong>⚠️ Attention :</strong> Les fonctions d'exécution de commandes sont désactivées sur ce serveur.
        Cela est courant sur les hébergements mutualisés. Vous devrez installer edge-tts manuellement.
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <strong>ℹ️ Note :</strong> Cette installation nécessite Python 3 et pip. 
        Si l'installation automatique échoue, suivez les instructions manuelles ci-dessous.
    </div>
    <?php endif; ?>

    <?php
    $allSuccess = true;
    
    // Toujours créer les dossiers nécessaires
    $dirSuccess = createRequiredDirectories();
    if (!$dirSuccess) {
        $allSuccess = false;
    }
    
    if ($exec_available) {
        // 1. Vérifier Python
        echo "<div class='command-block'>";
        echo "<h3>1. Vérification de Python</h3>";
        $pythonExists = false;
        $pythonCommand = 'python';
        
        if (commandExists('python3')) {
            $pythonCommand = 'python3';
            $pythonExists = true;
            echo "<div class='success'>✓ Python3 trouvé</div>";
        } elseif (commandExists('python')) {
            $pythonExists = true;
            echo "<div class='success'>✓ Python trouvé</div>";
        } else {
            echo "<div class='error'>✗ Python non trouvé</div>";
            echo "<p>Python doit être installé sur le serveur pour utiliser edge-tts.</p>";
            $allSuccess = false;
        }
        echo "</div>";
        
        // 2. Vérifier pip
        $pipExists = false;
        if ($pythonExists) {
            $pipCommand = $pythonCommand . ' -m pip';
            $pipExists = runCommand($pipCommand . ' --version', '2. Vérification de pip');
            
            if (!$pipExists) {
                $allSuccess = false;
            }
        }
        
        // 3. Installer edge-tts
        if ($pythonExists && $pipExists) {
            $installSuccess = runCommand(
                $pipCommand . ' install edge-tts --user', 
                '3. Installation de edge-tts'
            );
            
            if (!$installSuccess) {
                // Essayer sans --user
                $installSuccess = runCommand(
                    $pipCommand . ' install edge-tts', 
                    '3. Installation de edge-tts (tentative 2)'
                );
            }
            
            if (!$installSuccess) {
                $allSuccess = false;
            }
        }
        
        // 4. Vérifier l'installation de edge-tts
        if ($pythonExists) {
            runCommand(
                $pythonCommand . ' -m edge_tts --help', 
                '4. Vérification de edge-tts'
            );
        }
        
        // 5. Vérifier FFmpeg (optionnel)
        runCommand('ffmpeg -version | head -n 1', '5. Vérification de FFmpeg (optionnel)');
        
        // 6. Test de génération
        if ($pythonExists && $allSuccess) {
            $testText = "Bonjour, ceci est un test de génération vocale";
            $testFile = "uploads/temp/test_" . uniqid() . ".mp3";
            
            $testCommand = sprintf(
                '%s -m edge_tts --voice "fr-FR-VivienneMultilingualNeural" --text "%s" --write-media "%s"',
                $pythonCommand,
                $testText,
                $testFile
            );
            
            $testSuccess = runCommand($testCommand, '6. Test de génération vocale');
            
            if ($testSuccess && file_exists($testFile)) {
                echo "<div class='command-block'>";
                echo "<h3>✅ Test réussi !</h3>";
                echo "<p>Fichier audio généré avec succès :</p>";
                echo "<audio controls src='$testFile' style='width: 100%;'></audio>";
                echo "</div>";
                @unlink($testFile);
            }
        }
    }
    
    // Informations sur les musiques
    echo "<div class='command-block'>";
    echo "<h3>📁 Fichiers de musique requis</h3>";
    echo "<p>Placez vos fichiers MP3 de musique de fond dans le dossier <code>music/</code> :</p>";
    echo "<ul>";
    $musicFiles = [
        'soft-piano.mp3' => 'Musique de piano douce',
        'corporate.mp3' => 'Musique corporate/professionnelle',
        'ambient.mp3' => 'Musique d\'ambiance',
        'classical.mp3' => 'Musique classique'
    ];
    foreach ($musicFiles as $file => $desc) {
        $exists = file_exists("music/$file");
        $status = $exists ? "✓" : "✗";
        $class = $exists ? "success" : "error";
        echo "<li><span class='$class'>$status music/$file</span> - $desc</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Résumé et instructions
    echo "<div class='status-summary'>";
    if ($allSuccess && $exec_available) {
        echo "<h2 style='color: #4CAF50;'>✅ Installation réussie !</h2>";
        echo "<p>Edge TTS est maintenant installé et prêt à être utilisé.</p>";
        echo "<p>Vous pouvez utiliser le <strong>Mode Serveur</strong> pour une meilleure qualité audio.</p>";
    } else {
        echo "<h2 style='color: #ff9800;'>⚠️ Installation manuelle requise</h2>";
        echo "<p>L'installation automatique n'a pas pu être complétée.</p>";
        echo "<p>Vous pouvez soit :</p>";
        echo "<ul style='text-align: left; display: inline-block;'>";
        echo "<li>Utiliser le <strong>Mode Client</strong> (génération dans le navigateur) - Aucune installation requise</li>";
        echo "<li>Installer edge-tts manuellement (voir instructions ci-dessous)</li>";
        echo "</ul>";
    }
    echo "<a href='index.html' class='btn'>Aller à l'application</a>";
    echo "</div>";
    ?>
    
    <div class="manual-install">
        <h3>📋 Installation manuelle de edge-tts</h3>
        <p>Si l'installation automatique a échoué, voici comment installer edge-tts :</p>
        
        <h4>Sur Windows (avec Python installé) :</h4>
        <pre>
pip install edge-tts
</pre>
        
        <h4>Sur Linux/Mac :</h4>
        <pre>
# Installer Python 3 si nécessaire
sudo apt-get update && sudo apt-get install python3 python3-pip  # Ubuntu/Debian
# ou
brew install python3  # Mac

# Installer edge-tts
pip3 install edge-tts

# Vérifier l'installation
edge-tts --list-voices | grep fr-FR
</pre>
        
        <h4>Test rapide :</h4>
        <pre>
edge-tts --voice "fr-FR-VivienneMultilingualNeural" --text "Test" --write-media test.mp3
</pre>
    </div>
    
    <div class="command-block">
        <h3>💡 Conseils</h3>
        <ol class="step-list">
            <li><strong>Mode Client :</strong> Fonctionne immédiatement sans installation, utilise la synthèse vocale du navigateur.</li>
            <li><strong>Mode Serveur :</strong> Nécessite edge-tts installé, offre une meilleure qualité avec la voix Vivienne.</li>
            <li><strong>Hébergement mutualisé :</strong> Utilisez le Mode Client ou installez sur un VPS avec Python.</li>
            <li><strong>Musiques :</strong> Ajoutez vos propres fichiers MP3 dans le dossier <code>music/</code>.</li>
        </ol>
    </div>
</body>
</html>