<?php
// Configuration de l'application

// Chemins
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MUSIC_PATH', __DIR__ . '/../music/');
define('TEMP_PATH', __DIR__ . '/../uploads/temp/');

// Configuration TTS
define('TTS_METHOD', 'edge'); // Utilise Edge TTS par défaut
define('DEFAULT_VOICE', 'fr-FR-VivienneMultilingualNeural');

// Limites
define('MAX_TEXT_LENGTH', 500);
define('MAX_FILE_AGE', 3600); // 1 heure en secondes

// Types de musiques disponibles
$MUSIC_FILES = [
    'soft-piano' => 'soft-piano.mp3',
    'corporate' => 'corporate.mp3',
    'ambient' => 'ambient.mp3',
    'classical' => 'classical.mp3',
    'none' => null
];

// Voix disponibles
$VOICES = [
    'fr-FR-DeniseNeural' => 'Denise (Femme - Standard)',
    'fr-FR-EloiseNeural' => 'Eloise (Femme - Jeune)',
    'fr-FR-BrigitteNeural' => 'Brigitte (Femme - Mature)'
];

// Fonction pour créer les dossiers nécessaires
function createDirectories() {
    $dirs = [UPLOAD_PATH, MUSIC_PATH, TEMP_PATH];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// Créer les dossiers au chargement
createDirectories();

// Headers CORS si nécessaire
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Fonction de log des erreurs
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/../error.log');
}
?>