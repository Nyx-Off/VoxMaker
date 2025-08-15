<?php
/**
 * Script de test pour la voix Vivienne
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Voix Vivienne</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .warning {
            color: #ff9800;
            font-weight: bold;
        }
        audio {
            width: 100%;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎤 Test de la voix Vivienne (fr-FR-VivienneMultilingualNeural)</h1>

        <?php
        // 1. Vérifier les voix disponibles
        echo '<div class="test-section">';
        echo '<h2>📋 Liste des voix françaises disponibles</h2>';
        
        $output = [];
        exec('edge-tts --list-voices 2>&1 | grep "fr-FR"', $output);
        
        if (!empty($output)) {
            echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
            
            // Vérifier spécifiquement Vivienne
            $vivienne_found = false;
            foreach ($output as $line) {
                if (strpos($line, 'fr-FR-VivienneMultilingualNeural') !== false) {
                    $vivienne_found = true;
                    break;
                }
            }
            
            if ($vivienne_found) {
                echo '<p class="success">✅ La voix Vivienne (fr-FR-VivienneMultilingualNeural) est disponible !</p>';
            } else {
                echo '<p class="error">❌ La voix Vivienne n\'apparaît pas dans la liste</p>';
            }
        } else {
            echo '<p class="error">Impossible de récupérer la liste des voix</p>';
        }
        echo '</div>';
        
        // 2. Test de génération avec différentes commandes
        echo '<div class="test-section">';
        echo '<h2>🔧 Tests de génération audio</h2>';
        
        $test_text = "Bonjour, je suis Vivienne. Ceci est un test de génération vocale avec Edge TTS.";
        $test_commands = [
            'edge-tts directement' => sprintf(
                'edge-tts --voice "fr-FR-VivienneMultilingualNeural" --text %s --write-media test1.mp3 2>&1',
                escapeshellarg($test_text)
            ),
            'python3 -m edge_tts' => sprintf(
                'python3 -m edge_tts --voice "fr-FR-VivienneMultilingualNeural" --text %s --write-media test2.mp3 2>&1',
                escapeshellarg($test_text)
            ),
            'Sans guillemets sur la voix' => sprintf(
                'edge-tts --voice fr-FR-VivienneMultilingualNeural --text %s --write-media test3.mp3 2>&1',
                escapeshellarg($test_text)
            )
        ];
        
        $success_count = 0;
        foreach ($test_commands as $name => $cmd) {
            echo "<h3>Test : $name</h3>";
            echo "<pre>Commande : " . htmlspecialchars($cmd) . "</pre>";
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            $filename = 'test' . ($success_count + 1) . '.mp3';
            
            if ($returnCode === 0 && file_exists($filename)) {
                $success_count++;
                echo '<p class="success">✅ Succès ! Fichier généré (' . filesize($filename) . ' octets)</p>';
                echo '<audio controls src="' . $filename . '"></audio>';
                
                // Nettoyer après
                register_shutdown_function(function() use ($filename) {
                    @unlink($filename);
                });
            } else {
                echo '<p class="error">❌ Échec (code retour: ' . $returnCode . ')</p>';
                if (!empty($output)) {
                    echo '<pre>Sortie : ' . htmlspecialchars(implode("\n", $output)) . '</pre>';
                }
            }
        }
        echo '</div>';
        
        // 3. Information sur l'installation
        echo '<div class="test-section">';
        echo '<h2>ℹ️ Informations système</h2>';
        
        // Version d'edge-tts
        $version = [];
        exec('edge-tts --version 2>&1', $version);
        echo '<p><strong>Version Edge-TTS :</strong> ' . htmlspecialchars(implode(' ', $version)) . '</p>';
        
        // Python
        $python = [];
        exec('python3 --version 2>&1', $python);
        echo '<p><strong>Python :</strong> ' . htmlspecialchars(implode(' ', $python)) . '</p>';
        
        // Chemin edge-tts
        $which = [];
        exec('which edge-tts 2>&1', $which);
        echo '<p><strong>Chemin edge-tts :</strong> ' . htmlspecialchars(implode(' ', $which)) . '</p>';
        
        echo '</div>';
        
        // 4. Résumé
        echo '<div class="test-section">';
        if ($success_count > 0) {
            echo '<h2 class="success">✅ Test réussi !</h2>';
            echo '<p>La voix Vivienne fonctionne correctement. ' . $success_count . ' test(s) réussi(s).</p>';
            echo '<p>Vous pouvez maintenant utiliser le générateur de répondeur.</p>';
        } else {
            echo '<h2 class="error">❌ Problème détecté</h2>';
            echo '<p>La génération avec la voix Vivienne ne fonctionne pas.</p>';
            echo '<h3>Solutions possibles :</h3>';
            echo '<ol>';
            echo '<li>Vérifier que edge-tts est bien installé : <code>pip install --upgrade edge-tts</code></li>';
            echo '<li>Mettre à jour edge-tts : <code>pip install --upgrade edge-tts</code></li>';
            echo '<li>Vérifier la connexion Internet (Edge TTS nécessite Internet)</li>';
            echo '<li>Essayer avec une autre voix française pour tester</li>';
            echo '</ol>';
        }
        echo '</div>';
        ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.html" class="btn">🎙️ Retour au générateur</a>
            <a href="install.php" class="btn">🔧 Page d\'installation</a>
            <a href="api/status.php" class="btn">📊 API Status</a>
        </div>
    </div>
</body>
</html>