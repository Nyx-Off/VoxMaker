<?php
/**
 * Script d'installation pour Edge TTS
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation Edge TTS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
        }
        .step h3 {
            margin-top: 0;
            color: #667eea;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
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
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .status-box {
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .status-box.success {
            border-color: #4CAF50;
            background: #f1f8f4;
        }
        .status-box.error {
            border-color: #f44336;
            background: #fef1f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Installation d'Edge TTS pour le Générateur de Répondeur</h1>
        
        <p>Ce script va vous aider à installer <strong>Edge TTS</strong> pour utiliser la voix <strong>Vivienne</strong>.</p>

        <?php
        // Vérifier le système
        $exec_available = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));
        $python_installed = false;
        $edge_tts_installed = false;
        $ffmpeg_installed = false;
        
        if ($exec_available) {
            // Vérifier Python
            exec('python3 --version 2>&1', $python_output, $python_code);
            if ($python_code === 0) {
                $python_installed = true;
                $python_version = implode(' ', $python_output);
            } else {
                exec('python --version 2>&1', $python_output2, $python_code2);
                if ($python_code2 === 0) {
                    $python_installed = true;
                    $python_version = implode(' ', $python_output2);
                }
            }
            
            // Vérifier Edge TTS
            exec('edge-tts --help 2>&1', $edge_output, $edge_code);
            if ($edge_code === 0) {
                $edge_tts_installed = true;
            } else {
                exec('python3 -m edge_tts --help 2>&1', $edge_output2, $edge_code2);
                if ($edge_code2 === 0) {
                    $edge_tts_installed = true;
                }
            }
            
            // Vérifier FFmpeg
            exec('ffmpeg -version 2>&1', $ffmpeg_output, $ffmpeg_code);
            if ($ffmpeg_code === 0) {
                $ffmpeg_installed = true;
            }
        }
        ?>

        <div class="status-box <?php echo $edge_tts_installed ? 'success' : 'error'; ?>">
            <h2>📊 État actuel du système</h2>
            <ul>
                <li>
                    <?php if ($exec_available): ?>
                        <span class="success">✅</span> Fonctions exec() disponibles
                    <?php else: ?>
                        <span class="error">❌</span> Fonctions exec() désactivées (hébergement mutualisé?)
                    <?php endif; ?>
                </li>
                <li>
                    <?php if ($python_installed): ?>
                        <span class="success">✅</span> Python installé (<?php echo htmlspecialchars($python_version); ?>)
                    <?php else: ?>
                        <span class="error">❌</span> Python non détecté
                    <?php endif; ?>
                </li>
                <li>
                    <?php if ($edge_tts_installed): ?>
                        <span class="success">✅</span> Edge TTS installé et fonctionnel
                    <?php else: ?>
                        <span class="error">❌</span> Edge TTS non installé
                    <?php endif; ?>
                </li>
                <li>
                    <?php if ($ffmpeg_installed): ?>
                        <span class="success">✅</span> FFmpeg installé (pour le mixage audio)
                    <?php else: ?>
                        <span class="warning">⚠️</span> FFmpeg non installé (mixage audio indisponible)
                    <?php endif; ?>
                </li>
            </ul>
        </div>

        <?php if (!$edge_tts_installed): ?>
        
        <h2>📝 Instructions d'installation</h2>

        <div class="step">
            <h3>Option 1 : Installation automatique (si Python est disponible)</h3>
            <?php if ($exec_available && $python_installed): ?>
                <p>Cliquez sur le bouton ci-dessous pour tenter une installation automatique :</p>
                <form method="POST">
                    <button type="submit" name="auto_install" class="btn">🚀 Installer Edge TTS automatiquement</button>
                </form>
                
                <?php
                if (isset($_POST['auto_install'])) {
                    echo '<div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px;">';
                    echo '<h4>Installation en cours...</h4>';
                    
                    // Essayer pip3
                    $install_cmd = 'pip3 install edge-tts --user 2>&1';
                    exec($install_cmd, $install_output, $install_code);
                    
                    if ($install_code !== 0) {
                        // Essayer pip
                        $install_cmd = 'pip install edge-tts --user 2>&1';
                        exec($install_cmd, $install_output, $install_code);
                    }
                    
                    if ($install_code === 0) {
                        echo '<p class="success">✅ Installation réussie !</p>';
                        echo '<p>Edge TTS a été installé avec succès. Actualiser la page pour vérifier.</p>';
                    } else {
                        echo '<p class="error">❌ Échec de l\'installation automatique</p>';
                        echo '<pre>' . htmlspecialchars(implode("\n", $install_output)) . '</pre>';
                        echo '<p>Veuillez suivre les instructions manuelles ci-dessous.</p>';
                    }
                    echo '</div>';
                }
                ?>
            <?php else: ?>
                <p class="warning">⚠️ Installation automatique impossible (Python non disponible ou exec désactivé)</p>
            <?php endif; ?>
        </div>

        <div class="step">
            <h3>Option 2 : Installation manuelle sur votre serveur</h3>
            <p>Connectez-vous à votre serveur via SSH et exécutez ces commandes :</p>
            
            <h4>Sur Linux (Ubuntu/Debian) :</h4>
            <pre>
# Installer Python et pip si nécessaire
sudo apt update
sudo apt install python3 python3-pip

# Installer Edge TTS
pip3 install edge-tts

# Vérifier l'installation
edge-tts --list-voices | grep Vivienne
</pre>

            <h4>Sur Linux (CentOS/RHEL) :</h4>
            <pre>
# Installer Python et pip
sudo yum install python3 python3-pip

# Installer Edge TTS
pip3 install edge-tts

# Vérifier l'installation
edge-tts --list-voices | grep Vivienne
</pre>

            <h4>Installation sans droits sudo :</h4>
            <pre>
# Installer dans le répertoire utilisateur
pip3 install --user edge-tts

# Ajouter au PATH si nécessaire
export PATH=$PATH:~/.local/bin

# Vérifier
~/.local/bin/edge-tts --list-voices | grep Vivienne
</pre>
        </div>

        <div class="step">
            <h3>Option 3 : Installation locale (Windows/Mac)</h3>
            
            <h4>Windows :</h4>
            <pre>
# Ouvrir PowerShell ou CMD
# Installer Python depuis python.org si nécessaire

# Installer Edge TTS
pip install edge-tts

# Tester
edge-tts --voice "fr-FR-VivienneMultilingualNeural" --text "Test" --write-media test.mp3
</pre>

            <h4>macOS :</h4>
            <pre>
# Ouvrir Terminal
# Installer Homebrew si nécessaire : /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Installer Python
brew install python3

# Installer Edge TTS
pip3 install edge-tts

# Tester
edge-tts --voice "fr-FR-VivienneMultilingualNeural" --text "Test" --write-media test.mp3
</pre>
        </div>

        <?php endif; ?>

        <?php if ($edge_tts_installed): ?>
        <div class="step">
            <h3>✅ Edge TTS est installé !</h3>
            <p>Testons la voix Vivienne :</p>
            
            <?php
            $test_file = 'test_vivienne_' . uniqid() . '.mp3';
            $test_cmd = sprintf(
                'edge-tts --voice "fr-FR-VivienneMultilingualNeural" --text "Bonjour, je suis Vivienne. Votre générateur de répondeur est maintenant prêt." --write-media %s 2>&1',
                escapeshellarg($test_file)
            );
            
            exec($test_cmd, $test_output, $test_code);
            
            if ($test_code === 0 && file_exists($test_file)): ?>
                <p class="success">Test réussi ! Écoutez la voix Vivienne :</p>
                <audio controls src="<?php echo htmlspecialchars($test_file); ?>" style="width: 100%;"></audio>
                <?php 
                // Nettoyer après 5 minutes
                register_shutdown_function(function() use ($test_file) {
                    sleep(300);
                    @unlink($test_file);
                });
                ?>
            <?php else: ?>
                <p class="warning">Le test n'a pas pu générer d'audio, mais Edge TTS semble installé.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="step">
            <h3>📦 Installation de FFmpeg (optionnel)</h3>
            <p>FFmpeg est nécessaire pour mixer la voix avec une musique de fond.</p>
            
            <h4>Linux :</h4>
            <pre>
# Ubuntu/Debian
sudo apt install ffmpeg

# CentOS/RHEL
sudo yum install ffmpeg
</pre>

            <h4>Windows :</h4>
            <p>Téléchargez FFmpeg depuis <a href="https://ffmpeg.org/download.html" target="_blank">ffmpeg.org</a></p>

            <h4>macOS :</h4>
            <pre>brew install ffmpeg</pre>
        </div>

        <div class="step">
            <h3>🎵 Fichiers de musique</h3>
            <p>Placez vos fichiers MP3 dans le dossier <code>music/</code> :</p>
            <ul>
                <li><code>soft-piano.mp3</code> - Piano doux</li>
                <li><code>corporate.mp3</code> - Musique corporate</li>
                <li><code>ambient.mp3</code> - Ambiance</li>
                <li><code>classical.mp3</code> - Classique</li>
            </ul>
            
            <?php
            $music_files = [
                'soft-piano.mp3' => 'Piano doux',
                'corporate.mp3' => 'Corporate',
                'ambient.mp3' => 'Ambiance',
                'classical.mp3' => 'Classique'
            ];
            
            $music_dir = __DIR__ . '/music/';
            if (!file_exists($music_dir)) {
                mkdir($music_dir, 0777, true);
            }
            
            echo '<p>État actuel :</p><ul>';
            foreach ($music_files as $file => $name) {
                $exists = file_exists($music_dir . $file);
                $status = $exists ? '✅' : '⚠️';
                $class = $exists ? 'success' : 'warning';
                echo "<li><span class='$class'>$status $name</span> - $file</li>";
            }
            echo '</ul>';
            ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.html" class="btn">🎙️ Retour au générateur</a>
            <a href="api/status.php" class="btn">📊 Vérifier le statut</a>
        </div>
    </div>
</body>
</html>