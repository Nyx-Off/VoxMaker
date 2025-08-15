document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const form = document.getElementById('voiceForm');
    const textInput = document.getElementById('textInput');
    const charCount = document.getElementById('charCount');
    const volumeSlider = document.getElementById('volumeSlider');
    const volumeValue = document.getElementById('volumeValue');
    const generateBtn = document.getElementById('generateBtn');
    const resultSection = document.getElementById('result');
    const errorSection = document.getElementById('error');
    const audioPlayer = document.getElementById('audioPlayer');
    const downloadBtn = document.getElementById('downloadBtn');
    const downloadLink = document.getElementById('downloadLink');
    
    let currentAudioUrl = null;
    let currentAudioBlob = null;

    // Compteur de caractères
    textInput.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = length;
        
        if (length > 450) {
            charCount.style.color = '#ff6b6b';
        } else {
            charCount.style.color = '#666';
        }
    });

    // Slider de volume
    volumeSlider.addEventListener('input', function() {
        volumeValue.textContent = this.value + '%';
    });

    // Soumission du formulaire
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Masquer les sections précédentes
        resultSection.classList.add('hidden');
        errorSection.classList.add('hidden');
        
        // Afficher le spinner
        generateBtn.classList.add('loading');
        generateBtn.disabled = true;
        
        // Récupérer le mode sélectionné
        const mode = document.querySelector('input[name="mode"]:checked').value;
        
        try {
            if (mode === 'server') {
                // Mode serveur (Edge TTS)
                await generateServerSide();
            } else {
                // Mode client (Web Speech API)
                await generateClientSide();
            }
        } catch (error) {
            console.error('Erreur:', error);
            errorSection.textContent = error.message || 'Une erreur est survenue lors de la génération';
            errorSection.classList.remove('hidden');
        } finally {
            generateBtn.classList.remove('loading');
            generateBtn.disabled = false;
        }
    });

    // Génération côté serveur
    async function generateServerSide() {
        const formData = new FormData();
        formData.append('text', textInput.value);
        formData.append('voice', document.getElementById('voiceSelect').value);
        formData.append('music', document.getElementById('musicSelect').value);
        formData.append('volume', volumeSlider.value);
        
        try {
            const response = await fetch('php/generate_voice.php', {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            let data;
            
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Réponse du serveur:', responseText);
                throw new Error('Erreur du serveur. Edge TTS n\'est probablement pas installé. Utilisez le Mode Client à la place.');
            }
            
            if (data.success) {
                currentAudioUrl = data.audioUrl;
                
                // Vérifier si on doit mixer avec de la musique côté client
                const music = document.getElementById('musicSelect').value;
                if (music !== 'none') {
                    try {
                        // Mixer côté client
                        const mixer = new AudioMixer();
                        const musicUrl = `music/${music}.mp3`;
                        const volume = parseInt(volumeSlider.value);
                        
                        console.log('Mixage audio côté client...');
                        const mixedBlob = await mixer.mixAudioFiles(currentAudioUrl, musicUrl, volume);
                        
                        // Créer une URL pour le blob mixé
                        currentAudioBlob = mixedBlob;
                        currentAudioUrl = URL.createObjectURL(mixedBlob);
                        audioPlayer.src = currentAudioUrl;
                        
                        // Configuration du téléchargement
                        downloadBtn.onclick = function() {
                            const a = document.createElement('a');
                            a.href = currentAudioUrl;
                            a.download = 'repondeur_' + Date.now() + '.wav';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        };
                    } catch (mixError) {
                        console.error('Erreur de mixage, utilisation de la voix seule:', mixError);
                        // En cas d'erreur, utiliser juste la voix
                        audioPlayer.src = currentAudioUrl;
                        
                        downloadBtn.onclick = function() {
                            const a = document.createElement('a');
                            a.href = currentAudioUrl;
                            a.download = 'repondeur_' + Date.now() + '.mp3';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        };
                    }
                } else {
                    // Pas de musique, utiliser directement le fichier
                    audioPlayer.src = currentAudioUrl;
                    
                    downloadBtn.onclick = function() {
                        const a = document.createElement('a');
                        a.href = currentAudioUrl;
                        a.download = 'repondeur_' + Date.now() + '.mp3';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    };
                }
                
                resultSection.classList.remove('hidden');
                
                // Scroll vers le résultat
                resultSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                throw new Error(data.error || 'Une erreur est survenue');
            }
        } catch (error) {
            throw error;
        }
    }

    // Génération côté client
    async function generateClientSide() {
        // Vérifier la compatibilité du navigateur
        if (!window.speechSynthesis) {
            throw new Error('Votre navigateur ne supporte pas la synthèse vocale. Utilisez Chrome, Firefox ou Edge.');
        }
        
        const text = textInput.value;
        const voice = document.getElementById('voiceSelect').value;
        const music = document.getElementById('musicSelect').value;
        
        // Pour l'instant, on va juste faire parler le texte
        // sans enregistrement complexe
        try {
            // Annuler toute synthèse en cours
            window.speechSynthesis.cancel();
            
            // Créer l'utterance
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'fr-FR';
            utterance.rate = 0.95;
            utterance.pitch = 1;
            utterance.volume = 1;
            
            // Sélectionner la voix si disponible
            if (voice !== 'default') {
                const voices = window.speechSynthesis.getVoices();
                const selectedVoice = voices.find(v => v.name === voice);
                if (selectedVoice) {
                    utterance.voice = selectedVoice;
                }
            }
            
            // Afficher le message de succès
            resultSection.classList.remove('hidden');
            
            // Remplacer le lecteur audio par un bouton de lecture
            const audioContainer = audioPlayer.parentElement;
            audioContainer.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <button id="playBtn" style="padding: 12px 30px; background: #4CAF50; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">
                        🔊 Écouter le message
                    </button>
                    <p style="margin-top: 10px; color: #666;">
                        Cliquez pour entendre votre message avec la voix de synthèse du navigateur
                    </p>
                </div>
            `;
            
            // Ajouter l'événement de lecture
            document.getElementById('playBtn').onclick = function() {
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
            };
            
            // Masquer le bouton de téléchargement car on ne peut pas enregistrer facilement
            downloadBtn.style.display = 'none';
            
            // Ajouter une explication
            const explanation = document.createElement('p');
            explanation.style.cssText = 'margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; color: #1976d2;';
            explanation.innerHTML = `
                <strong>Note :</strong> En mode client, le message est lu directement par votre navigateur. 
                Pour enregistrer un fichier audio, utilisez le Mode Serveur (nécessite Edge TTS installé) 
                ou enregistrez l'audio avec un logiciel d'enregistrement.
            `;
            downloadBtn.parentElement.appendChild(explanation);
            
            // Scroll vers le résultat
            resultSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
        } catch (error) {
            throw new Error('Erreur lors de la génération : ' + error.message);
        }
    }

    // Nettoyer les URLs blob quand on quitte la page
    window.addEventListener('beforeunload', function() {
        if (currentAudioUrl && currentAudioUrl.startsWith('blob:')) {
            URL.revokeObjectURL(currentAudioUrl);
        }
    });
});

// Fonction pour vérifier l'installation
function checkInstallation() {
    const statusDiv = document.getElementById('installStatus');
    const messagesDiv = document.getElementById('installMessages');
    
    statusDiv.classList.remove('hidden');
    messagesDiv.innerHTML = '<p>Vérification en cours...</p>';
    
    fetch('php/check_installation.php')
        .then(response => response.json())
        .then(data => {
            let html = '<ul>';
            for (const [key, value] of Object.entries(data)) {
                const status = value ? '✓' : '✗';
                const className = value ? 'success' : 'error';
                html += `<li class="${className}">${status} ${key}</li>`;
            }
            html += '</ul>';
            
            if (!data['Edge TTS installé']) {
                html += '<p><a href="install.php" class="btn">Lancer l\'installation</a></p>';
            }
            
            messagesDiv.innerHTML = html;
        })
        .catch(error => {
            messagesDiv.innerHTML = '<p class="error">Erreur lors de la vérification</p>';
        });
}