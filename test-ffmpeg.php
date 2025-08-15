<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test FFmpeg</h1>";

// Test 1 : Vérifier si FFmpeg est disponible
echo "<h2>1. Vérification de FFmpeg</h2>";
$output = [];
$returnCode = 0;
exec('which ffmpeg 2>&1', $output, $returnCode);

if ($returnCode === 0) {
    echo "<p style='color: green;'>✓ FFmpeg trouvé : " . $output[0] . "</p>";
    
    // Version de FFmpeg
    $version = [];
    exec('ffmpeg -version 2>&1', $version);
    echo "<pre>" . htmlspecialchars($version[0]) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ FFmpeg non trouvé</p>";
    echo "<p>FFmpeg est nécessaire pour mixer la voix avec la musique de fond.</p>";
}

// Test 2 : Vérifier les codecs audio
echo "<h2>2. Codecs audio disponibles</h2>";
$codecs = [];
exec('ffmpeg -codecs 2>&1 | grep -E "mp3|aac|wav"', $codecs);
if (!empty($codecs)) {
    echo "<pre>" . htmlspecialchars(implode("\n", $codecs)) . "</pre>";
}

// Test 3 : Test de mixage simple
echo "<h2>3. Test de mixage</h2>";
echo "<p>Pour installer FFmpeg sur votre serveur :</p>";
echo "<pre>
# Sur Ubuntu/Debian :
sudo apt-get update
sudo apt-get install ffmpeg

# Sur CentOS/RHEL :
sudo yum install ffmpeg

# Ou via votre panel d'hébergement
</pre>";

// Vérifier les fichiers de musique
echo "<h2>4. Fichiers de musique disponibles</h2>";
$musicPath = __DIR__ . '/music/';
if (is_dir($musicPath)) {
    $files = glob($musicPath . '*.mp3');
    if (empty($files)) {
        echo "<p style='color: orange;'>⚠ Aucun fichier MP3 trouvé dans le dossier music/</p>";
        echo "<p>Ajoutez les fichiers suivants :</p>";
        echo "<ul>";
        echo "<li>music/soft-piano.mp3</li>";
        echo "<li>music/corporate.mp3</li>";
        echo "<li>music/ambient.mp3</li>";
        echo "<li>music/classical.mp3</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✓ Fichiers trouvés :</p>";
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . basename($file) . " (" . filesize($file) . " octets)</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>✗ Dossier music/ non trouvé</p>";
}
?>