<?php
/**
 * Web Shell pour installer et diagnostiquer Edge TTS
 * IMPORTANT : Supprimez ce fichier après utilisation !
 */

session_start();

// Protection basique par mot de passe
$PASSWORD = 'edgettsinstall2024'; // Changez ce mot de passe !

if (!isset($_SESSION['authenticated'])) {
    if (isset($_POST['password']) && $_POST['password'] === $PASSWORD) {
        $_SESSION['authenticated'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Web Shell - Authentification</title>
            <style>
                body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
                input { background: #000; color: #0f0; border: 1px solid #0f0; padding: 5px; }
                button { background: #0f0; color: #000; border: none; padding: 5px 15px; cursor: pointer; }
            </style>
        </head>
        <body>
            <h2>🔐 Authentification requise</h2>
            <form method="post">
                <input type="password" name="password" placeholder="Mot de passe" autofocus>
                <button type="submit">Entrer</button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}

// Fonction pour exécuter une commande
function runCommand($cmd) {
    $output = [];
    $returnCode = 0;
    exec($cmd . ' 2>&1', $output, $returnCode);
    return [
        'command' => $cmd,
        'output' => implode("\n", $output),
        'return_code' => $returnCode
    ];
}

// Traiter les commandes
$result = null;
if (isset($_POST['command'])) {
    $command = $_POST['command'];
    
    // Commandes prédéfinies sécurisées
    switch($command) {
        case 'install_edge_tts':
            $result = runCommand('python3 -m pip install edge-tts --user');
            break;
        case 'upgrade_edge_tts':
            $result = runCommand('python3 -m pip install --upgrade edge-tts --user');
            break;
        case 'list_voices':
            $result = runCommand('python3 -m edge_tts --list-voices');
            break;
        case 'test_vivienne':
            $testFile = 'test_vivienne_' . uniqid() . '.mp3';
            $result = runCommand('python3 -m edge_tts --voice "fr-FR-VivienneMultilingualNeural" --text "Test de la voix Vivienne" --write-media ' . $testFile);
            if (file_exists($testFile)) {
                $result['audio_file'] = $testFile;
                $result['file_size'] = filesize($testFile);
            }
            break;
        case 'check_python':
            $result = runCommand('python3 --version');
            break;
        case 'check_pip':
            $result = runCommand('python3 -m pip --version');
            break;
        case 'check_edge_location':
            $result = runCommand('python3 -c "import edge_tts; import os; print(os.path.dirname(edge_tts.__file__))"');
            break;
        case 'custom':
            // Commande personnalisée (limitée)
            $customCmd = $_POST['custom_command'] ?? '';
            if (preg_match('/^(python3?|pip3?|edge-tts|ls|pwd|whoami|which)/', $customCmd)) {
                $result = runCommand($customCmd);
            } else {
                $result = ['error' => 'Commande non autorisée'];
            }
            break;
    }
}

// Nettoyer les vieux fichiers de test
$testFiles = glob('test_vivienne_*.mp3');
foreach ($testFiles as $file) {
    if (time() - filemtime($file) > 300) { // 5 minutes
        @unlink($file);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Web Shell - Edge TTS Installation</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #0f0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #0f0;
            border-bottom: 2px solid #0f0;
            padding-bottom: 10px;
        }
        .controls {
            background: #000;
            border: 1px solid #0f0;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        button {
            background: #0f0;
            color: #000;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            font-family: monospace;
            font-weight: bold;
        }
        button:hover {
            background: #00ff00cc;
        }
        .output {
            background: #000;
            border: 1px solid #333;
            padding: 15px;
            margin: 20px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 500px;
            overflow-y: auto;
        }
        .success {
            color: #0f0;
            font-weight: bold;
        }
        .error {
            color: #f00;
            font-weight: bold;
        }
        .warning {
            color: #ff0;
        }
        input[type="text"] {
            background: #000;
            color: #0f0;
            border: 1px solid #0f0;
            padding: 8px;
            width: 400px;
            font-family: monospace;
        }
        .section {
            margin: 30px 0;
        }
        audio {
            margin: 10px 0;
        }
        .logout {
            float: right;
            background: #f00;
            color: #fff;
        }
        .info-box {
            background: #001100;
            border: 1px solid #0f0;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖥️ Web Shell - Edge TTS Installation & Diagnostic</h1>
        
        <form method="post" style="display: inline;">
            <button type="submit" name="logout" class="logout">🚪 Déconnexion</button>
        </form>
        <?php
        if (isset($_POST['logout'])) {
            session_destroy();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        ?>
        
        <div class="info-box">
            <strong>ℹ️ Information système :</strong><br>
            PHP Version: <?php echo PHP_VERSION; ?><br>
            Serveur: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
            OS: <?php echo PHP_OS; ?><br>
            User: <?php echo get_current_user(); ?>
        </div>

        <div class="section">
            <h2>🔧 Installation et diagnostic</h2>
            <div class="controls">
                <form method="post">
                    <h3>Commandes de base :</h3>
                    <button type="submit" name="command" value="check_python">📊 Vérifier Python</button>
                    <button type="submit" name="command" value="check_pip">📦 Vérifier pip</button>
                    <button type="submit" name="command" value="install_edge_tts">💾 Installer Edge TTS</button>
                    <button type="submit" name="command" value="upgrade_edge_tts">⬆️ Mettre à jour Edge TTS</button>
                    
                    <h3>Tests Edge TTS :</h3>
                    <button type="submit" name="command" value="list_voices">📋 Lister les voix</button>
                    <button type="submit" name="command" value="test_vivienne">🎤 Tester Vivienne</button>
                    <button type="submit" name="command" value="check_edge_location">📍 Localiser Edge TTS</button>
                    
                    <h3>Commande personnalisée (limitée) :</h3>
                    <input type="text" name="custom_command" placeholder="python3 -m edge_tts --help">
                    <button type="submit" name="command" value="custom">▶️ Exécuter</button>
                </form>
            </div>
        </div>

        <?php if ($result): ?>
        <div class="section">
            <h2>📤 Résultat</h2>
            <?php if (isset($result['error'])): ?>
                <div class="output error">
                    ❌ Erreur : <?php echo htmlspecialchars($result['error']); ?>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <strong>Commande :</strong> <?php echo htmlspecialchars($result['command']); ?><br>
                    <strong>Code retour :</strong> <?php echo $result['return_code']; ?>
                    <?php if ($result['return_code'] === 0): ?>
                        <span class="success"> ✅ Succès</span>
                    <?php else: ?>
                        <span class="error"> ❌ Échec</span>
                    <?php endif; ?>
                </div>
                
                <div class="output">
<?php echo htmlspecialchars($result['output']); ?>
                </div>
                
                <?php if (isset($result['audio_file']) && file_exists($result['audio_file'])): ?>
                    <div class="info-box">
                        <strong>🔊 Fichier audio généré :</strong> <?php echo $result['audio_file']; ?> 
                        (<?php echo number_format($result['file_size'] / 1024, 2); ?> KB)<br>
                        <audio controls src="<?php echo $result['audio_file']; ?>"></audio>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>📝 Script d'installation automatique</h2>
            <div class="info-box">
                <p>Exécutez ces commandes dans l'ordre :</p>
                <ol>
                    <li>Cliquez sur "📊 Vérifier Python" - doit afficher Python 3.x</li>
                    <li>Cliquez sur "📦 Vérifier pip" - doit afficher pip version</li>
                    <li>Cliquez sur "💾 Installer Edge TTS" - installation du module</li>
                    <li>Cliquez sur "📋 Lister les voix" - doit afficher les voix disponibles</li>
                    <li>Cliquez sur "🎤 Tester Vivienne" - génère un fichier audio de test</li>
                </ol>
            </div>
        </div>

        <div class="warning" style="margin-top: 40px; padding: 20px; border: 2px solid #ff0;">
            ⚠️ <strong>SÉCURITÉ :</strong> Supprimez ce fichier après utilisation !<br>
            Commande : <code>rm <?php echo basename(__FILE__); ?></code>
        </div>
    </div>
</body>
</html>