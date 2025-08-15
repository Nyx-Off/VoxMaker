<?php
// Vérifier l'état de l'installation

function checkCommand($command) {
    $output = [];
    $returnCode = 0;
    exec("which $command 2>&1", $output, $returnCode);
    return $returnCode === 0;
}

function checkEdgeTTS() {
    $output = [];
    $returnCode = 0;
    
    // Essayer différentes commandes
    exec("edge-tts --help 2>&1", $output, $returnCode);
    if ($returnCode === 0) return true;
    
    exec("python -m edge_tts --help 2>&1", $output, $returnCode);
    if ($returnCode === 0) return true;
    
    exec("python3 -m edge_tts --help 2>&1", $output, $returnCode);
    return $returnCode === 0;
}

$status = [
    'PHP' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'cURL' => extension_loaded('curl'),
    'Python' => checkCommand('python') || checkCommand('python3'),
    'Edge TTS installé' => checkEdgeTTS(),
    'FFmpeg' => checkCommand('ffmpeg'),
    'Dossier uploads' => is_dir('../uploads') && is_writable('../uploads'),
    'Dossier music' => is_dir('../music'),
    'Dossier temp' => is_dir('../uploads/temp') && is_writable('../uploads/temp')
];

header('Content-Type: application/json');
echo json_encode($status);
?>