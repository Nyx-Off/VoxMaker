<?php
// Activer l'affichage des erreurs pour le debug (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher les erreurs directement

// S'assurer que la sortie est en JSON
header('Content-Type: application/json; charset=utf-8');

// Inclure la configuration
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die(json_encode(['success' => false, 'error' => 'Fichier config.php manquant']));
}
require_once $configFile;

// Fonction pour générer la voix avec Edge TTS
function generateVoiceEdgeTTS($text, $voice = 'fr-FR-VivienneMultilingualNeural') {
    // Créer un fichier temporaire pour le texte
    $tempTextFile = TEMP_PATH . 'text_' . uniqid() . '.txt';
    $outputFile = TEMP_PATH . 'voice_' . uniqid() . '.mp3';
    
    // Créer le dossier temp s'il n'existe pas
    if (!file_exists(TEMP_PATH)) {
        if (!@mkdir(TEMP_PATH, 0777, true)) {
            return false;
        }
    }
    
    // Sauvegarder le texte dans un fichier temporaire
    if (!@file_put_contents($tempTextFile, $text)) {
        return false;
    }
    
    // Essayer différentes commandes edge-tts
    $commands = [
        // Commande 1 : edge-tts directement
        sprintf(
            'edge-tts --voice %s --file %s --write-media %s 2>&1',
            escapeshellarg($voice),
            escapeshellarg($tempTextFile),
            escapeshellarg($outputFile)
        ),
        // Commande 2 : avec le chemin complet possible sur Linux
        sprintf(
            '/usr/local/bin/edge-tts --voice %s --file %s --write-media %s 2>&1',
            escapeshellarg($voice),
            escapeshellarg($tempTextFile),
            escapeshellarg($outputFile)
        ),
        // Commande 3 : avec python3
        sprintf(
            'python3 -m edge_tts --voice %s --text %s --write-media %s 2>&1',
            escapeshellarg($voice),
            escapeshellarg($text),
            escapeshellarg($outputFile)
        ),
        // Commande 4 : avec python
        sprintf(
            'python -m edge_tts --voice %s --text %s --write-media %s 2>&1',
            escapeshellarg($voice),
            escapeshellarg($text),
            escapeshellarg($outputFile)
        ),
        // Commande 5 : chemin utilisateur local
        sprintf(
            '$HOME/.local/bin/edge-tts --voice %s --file %s --write-media %s 2>&1',
            escapeshellarg($voice),
            escapeshellarg($tempTextFile),
            escapeshellarg($outputFile)
        ),
        // Commande 6 : avec le chemin Python utilisateur
        sprintf(
            '/usr/bin/python3 -m edge_tts --voice %s --text %s --write-media %s 2>&1',
            escapeshellarg($voice),
            escapeshellarg($text),
            escapeshellarg($outputFile)
        )
    ];
    
    $success = false;
    $lastOutput = '';
    
    foreach ($commands as $command) {
        $output = [];
        $returnCode = 0;
        
        // Vérifier si exec est disponible
        if (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
            @exec($command, $output, $returnCode);
            $lastOutput = implode("\n", $output);
            
            if ($returnCode === 0 && file_exists($outputFile)) {
                $success = true;
                break;
            }
        }
    }
    
    // Nettoyer le fichier texte temporaire
    @unlink($tempTextFile);
    
    if ($success && file_exists($outputFile)) {
        $audioData = @file_get_contents($outputFile);
        @unlink($outputFile);
        return $audioData;
    }
    
    // Log l'erreur pour debug
    if (function_exists('logError')) {
        logError("Edge TTS failed. Last output: " . $lastOutput);
    }
    
    return false;
}

// Fonction de fallback : générer un fichier audio de test
function generateTestAudio($text) {
    // Créer un fichier audio de test très simple (silence)
    // En production, vous pourriez utiliser une API externe ici
    
    // Créer un fichier WAV basique avec du silence
    $duration = min(strlen($text) / 10, 10); // Approximation : 10 caractères par seconde, max 10 secondes
    $sampleRate = 44100;
    $numSamples = $duration * $sampleRate;
    
    // En-tête WAV
    $wav = "RIFF";
    $wav .= pack('V', 36 + $numSamples * 2); // Taille du fichier
    $wav .= "WAVE";
    $wav .= "fmt ";
    $wav .= pack('V', 16); // Taille du chunk fmt
    $wav .= pack('v', 1); // Format audio (PCM)
    $wav .= pack('v', 1); // Nombre de canaux (mono)
    $wav .= pack('V', $sampleRate); // Taux d'échantillonnage
    $wav .= pack('V', $sampleRate * 2); // Débit binaire
    $wav .= pack('v', 2); // Alignement des blocs
    $wav .= pack('v', 16); // Bits par échantillon
    $wav .= "data";
    $wav .= pack('V', $numSamples * 2); // Taille des données
    
    // Générer du silence
    for ($i = 0; $i < $numSamples; $i++) {
        $wav .= pack('v', 0);
    }
    
    return $wav;
}

// Traitement principal
try {
    // Validation des données
    if (!isset($_POST['text']) || empty(trim($_POST['text']))) {
        throw new Exception('Le texte est requis');
    }
    
    $text = trim($_POST['text']);
    $voice = isset($_POST['voice']) ? $_POST['voice'] : 'fr-FR-VivienneMultilingualNeural';
    $music = isset($_POST['music']) ? $_POST['music'] : 'soft-piano';
    $volume = isset($_POST['volume']) ? intval($_POST['volume']) : 30;
    
    // Vérifier la longueur du texte
    if (strlen($text) > MAX_TEXT_LENGTH) {
        throw new Exception('Le texte est trop long (maximum ' . MAX_TEXT_LENGTH . ' caractères)');
    }
    
    // Générer un nom de fichier unique
    $timestamp = time();
    $uniqueId = uniqid();
    $voiceFile = TEMP_PATH . "voice_{$timestamp}_{$uniqueId}.mp3";
    $outputFile = UPLOAD_PATH . "repondeur_{$timestamp}_{$uniqueId}.mp3";
    
    // Créer les dossiers s'ils n'existent pas
    if (!file_exists(TEMP_PATH)) {
        if (!@mkdir(TEMP_PATH, 0777, true)) {
            throw new Exception('Impossible de créer le dossier temporaire');
        }
    }
    if (!file_exists(UPLOAD_PATH)) {
        if (!@mkdir(UPLOAD_PATH, 0777, true)) {
            throw new Exception('Impossible de créer le dossier uploads');
        }
    }
    
    // Générer la voix avec Edge TTS
    $audioData = generateVoiceEdgeTTS($text, $voice);
    
    if (!$audioData) {
        // Si Edge TTS n'est pas disponible, informer l'utilisateur
        throw new Exception('Edge TTS n\'est pas installé sur ce serveur. Veuillez utiliser le Mode Client ou installer Edge TTS.');
    }
    
    // Sauvegarder le fichier audio
    if (!@file_put_contents($voiceFile, $audioData)) {
        throw new Exception('Impossible de sauvegarder le fichier audio');
    }
    
    // Si pas de musique, utiliser directement le fichier voix
    if ($music === 'none' || !isset($MUSIC_FILES[$music])) {
        if (!@rename($voiceFile, $outputFile)) {
            @copy($voiceFile, $outputFile);
            @unlink($voiceFile);
        }
    } else {
        // Mixer avec la musique de fond
        $mixFile = __DIR__ . '/mix_audio.php';
        if (file_exists($mixFile)) {
            require_once $mixFile;
            $musicFile = MUSIC_PATH . $MUSIC_FILES[$music];
            
            if (!file_exists($musicFile)) {
                // Si le fichier musique n'existe pas, utiliser juste la voix
                if (!@rename($voiceFile, $outputFile)) {
                    @copy($voiceFile, $outputFile);
                    @unlink($voiceFile);
                }
            } else {
                $mixResult = mixAudioFiles($voiceFile, $musicFile, $outputFile, $volume);
                if (!$mixResult) {
                    // En cas d'erreur de mixage, utiliser juste la voix
                    if (!@rename($voiceFile, $outputFile)) {
                        @copy($voiceFile, $outputFile);
                        @unlink($voiceFile);
                    }
                } else {
                    // Supprimer le fichier temporaire
                    @unlink($voiceFile);
                }
            }
        } else {
            // Pas de fichier mix_audio.php, utiliser juste la voix
            if (!@rename($voiceFile, $outputFile)) {
                @copy($voiceFile, $outputFile);
                @unlink($voiceFile);
            }
        }
    }
    
    // Vérifier que le fichier de sortie existe
    if (!file_exists($outputFile)) {
        throw new Exception('Erreur lors de la création du fichier audio');
    }
    
    // Retourner l'URL du fichier
    // Construire l'URL relative depuis la racine du site
    $audioUrl = 'uploads/' . basename($outputFile);
    
    echo json_encode([
        'success' => true,
        'audioUrl' => $audioUrl,
        'filename' => basename($outputFile)
    ]);
    
} catch (Exception $e) {
    // Log l'erreur
    if (function_exists('logError')) {
        logError("Erreur generate_voice.php: " . $e->getMessage());
    }
    
    // Retourner l'erreur en JSON
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// S'assurer qu'aucun autre contenu n'est envoyé
exit();
?>