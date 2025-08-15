<?php
/**
 * API de vérification du statut du système
 */

header('Content-Type: application/json; charset=utf-8');

function checkCommand($command) {
    if (!function_exists('exec')) {
        return false;
    }
    $output = [];
    $returnCode = 0;
    @exec("which $command 2>&1", $output, $returnCode);
    return $returnCode === 0;
}

function checkEdgeTTS() {
    if (!function_exists('exec')) {
        return false;
    }
    
    $commands = [
        'edge-tts --help',
        'python3 -m edge_tts --help',
        'python -m edge_tts --help'
    ];
    
    foreach ($commands as $cmd) {
        $output = [];
        $returnCode = 0;
        @exec($cmd . ' 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            return true;
        }
    }
    
    return false;
}

function checkVivienneVoice() {
    if (!checkEdgeTTS()) {
        return false;
    }
    
    $output = [];
    @exec('edge-tts --list-voices 2>&1 | grep -i vivienne', $output);
    return !empty($output);
}

// Collecter les informations
$status = [
    'php_version' => PHP_VERSION,
    'exec_available' => function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions')))),
    'python_installed' => checkCommand('python3') || checkCommand('python'),
    'edge_tts_installed' => checkEdgeTTS(),
    'vivienne_voice_available' => checkVivienneVoice(),
    'ffmpeg_installed' => checkCommand('ffmpeg'),
    'uploads_dir_writable' => is_writable(__DIR__ . '/../uploads/'),
    'temp_dir_writable' => is_writable(__DIR__ . '/../temp/'),
    'music_dir_exists' => is_dir(__DIR__ . '/../music/'),
];

// Ajouter les fichiers de musique disponibles
$music_files = [];
if ($status['music_dir_exists']) {
    $files = ['soft-piano.mp3', 'corporate.mp3', 'ambient.mp3', 'classical.mp3'];
    foreach ($files as $file) {
        $music_files[$file] = file_exists(__DIR__ . '/../music/' . $file);
    }
}
$status['music_files'] = $music_files;

// Calculer le statut global
$status['ready'] = $status['exec_available'] && 
                   $status['edge_tts_installed'] && 
                   $status['vivienne_voice_available'] &&
                   $status['uploads_dir_writable'];

// Message de diagnostic
if (!$status['ready']) {
    $issues = [];
    if (!$status['exec_available']) {
        $issues[] = "Les fonctions exec() sont désactivées";
    }
    if (!$status['edge_tts_installed']) {
        $issues[] = "Edge TTS n'est pas installé";
    }
    if (!$status['vivienne_voice_available']) {
        $issues[] = "La voix Vivienne n'est pas disponible";
    }
    if (!$status['uploads_dir_writable']) {
        $issues[] = "Le dossier uploads n'est pas accessible en écriture";
    }
    $status['issues'] = $issues;
}

echo json_encode($status, JSON_PRETTY_PRINT);
?>