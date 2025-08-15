<?php
/**
 * Générateur de message vocal avec Edge TTS
 * Utilise uniquement la voix Vivienne (fr-FR-VivienneMultilingualNeural)
 */

// Configuration
define('VOICE', 'fr-FR-VivienneMultilingualNeural'); // Voix Vivienne uniquement
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('TEMP_DIR', __DIR__ . '/../temp/');
define('MUSIC_DIR', __DIR__ . '/../music/');
define('MAX_TEXT_LENGTH', 500);

// Créer les dossiers si nécessaire
foreach ([UPLOAD_DIR, TEMP_DIR, MUSIC_DIR] as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// Headers JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * Génère un fichier audio avec Edge TTS
 */
function generateVoice($text) {
    $outputFile = TEMP_DIR . 'voice_' . uniqid() . '.mp3';
    
    // Nettoyer le texte (enlever les caractères problématiques)
    $text = str_replace('"', "'", $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    // PRIORITÉ : python3 -m edge_tts car c'est ce qui fonctionne sur votre serveur
    $commands = [
        // Méthode 1 : python3 -m edge_tts (PRIORITAIRE car fonctionne sur votre serveur)
        sprintf(
            'cd %s && python3 -m edge_tts --voice "fr-FR-VivienneMultilingualNeural" --text %s --write-media %s 2>&1',
            escapeshellarg(TEMP_DIR),
            escapeshellarg($text),
            escapeshellarg(basename($outputFile))
        ),
        // Méthode 2 : Alternative sans cd
        sprintf(
            'python3 -m edge_tts --voice "fr-FR-VivienneMultilingualNeural" --text %s --write-media %s 2>&1',
            escapeshellarg($text),
            escapeshellarg($outputFile)
        ),
        // Méthode 3 : avec python
        sprintf(
            'python -m edge_tts --voice "fr-FR-VivienneMultilingualNeural" --text %s --write-media %s 2>&1',
            escapeshellarg($text),
            escapeshellarg($outputFile)
        )
    ];
    
    $lastError = '';
    foreach ($commands as $index => $cmd) {
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        
        // Log pour debug
        error_log("Trying command $index: $cmd");
        error_log("Return code: $returnCode");
        error_log("Output: " . implode("\n", $output));
        
        if (file_exists($outputFile) && filesize($outputFile) > 100) {
            error_log("Success! File created: $outputFile (" . filesize($outputFile) . " bytes)");
            return $outputFile;
        }
        
        // Vérifier aussi dans le dossier temp au cas où
        $altPath = TEMP_DIR . basename($outputFile);
        if (file_exists($altPath) && filesize($altPath) > 100) {
            error_log("Success! File found at alternate path: $altPath");
            return $altPath;
        }
        
        $lastError = "Command $index failed. Return: $returnCode. Output: " . implode("\n", $output);
    }
    
    // Log l'erreur finale
    error_log("Edge TTS completely failed. Last error: " . $lastError);
    
    return false;
}

/**
 * Mixer la voix avec une musique de fond
 */
function mixWithMusic($voiceFile, $musicName, $volume) {
    if ($musicName === 'none') {
        return $voiceFile;
    }
    
    $musicFile = MUSIC_DIR . $musicName . '.mp3';
    if (!file_exists($musicFile)) {
        return $voiceFile;
    }
    
    $outputFile = TEMP_DIR . 'mixed_' . uniqid() . '.mp3';
    
    // Commande FFmpeg pour mixer
    // -filter_complex pour mixer les deux pistes
    // amix=inputs=2:duration=first pour que la durée finale soit celle de la voix
    $volumeLevel = $volume / 100;
    
    $command = sprintf(
        'ffmpeg -i %s -i %s -filter_complex "[1:a]volume=%f[music];[0:a][music]amix=inputs=2:duration=first:dropout_transition=2" -ac 2 -y %s 2>&1',
        escapeshellarg($voiceFile),
        escapeshellarg($musicFile),
        $volumeLevel,
        escapeshellarg($outputFile)
    );
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputFile)) {
        @unlink($voiceFile); // Supprimer le fichier temporaire
        return $outputFile;
    }
    
    // Si FFmpeg échoue, retourner juste la voix
    return $voiceFile;
}

/**
 * Traitement principal
 */
try {
    // Validation
    if (!isset($_POST['text']) || empty(trim($_POST['text']))) {
        throw new Exception('Le texte est requis');
    }
    
    $text = trim($_POST['text']);
    $music = $_POST['music'] ?? 'none';
    $volume = intval($_POST['volume'] ?? 20);
    
    // Vérifier la longueur
    if (strlen($text) > MAX_TEXT_LENGTH) {
        throw new Exception('Le texte est trop long (maximum ' . MAX_TEXT_LENGTH . ' caractères)');
    }
    
    // Générer la voix avec Edge TTS (Vivienne)
    $voiceFile = generateVoice($text);
    
    if (!$voiceFile) {
        throw new Exception('Edge TTS n\'est pas installé ou accessible. Installez-le avec: pip install edge-tts');
    }
    
    // Mixer avec la musique si demandé
    $finalFile = mixWithMusic($voiceFile, $music, $volume);
    
    // Déplacer vers le dossier uploads
    $filename = 'repondeur_' . date('Y-m-d_His') . '_' . uniqid() . '.mp3';
    $destination = UPLOAD_DIR . $filename;
    
    if (!rename($finalFile, $destination)) {
        copy($finalFile, $destination);
        @unlink($finalFile);
    }
    
    // Nettoyer les vieux fichiers (plus de 1 heure)
    $files = glob(UPLOAD_DIR . '*.mp3');
    $now = time();
    foreach ($files as $file) {
        if ($now - filemtime($file) > 3600) {
            @unlink($file);
        }
    }
    
    // Retourner le résultat
    echo json_encode([
        'success' => true,
        'audioUrl' => 'uploads/' . $filename,
        'filename' => $filename,
        'voice' => 'Vivienne (Multilingue)'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>